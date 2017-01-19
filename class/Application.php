<?php
/*
 * Copyright (c) 2016, Josef Kufner  <josef@kufner.cz>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

namespace Cascade\Core;

class Application
{

	/**
	 * Application primary entry point
	 *
	 * Call this method from your `/index.php`:
	 *
	 *     require __DIR__."/vendor/autoload.php";
	 *     \Cascade\Core\Application::execute(__DIR__);
	 *
	 */
	public static function main($root_directory)
	{
		define('CASCADE_MAIN', true);
		list($default_context, $core_cfg) = static::initialize($root_directory);
		return static::frontController($core_cfg, $default_context);
	}


	/**
	 * Initialize PHP and the core stuff
	 */
	public static function initialize($root_directory)
	{
		// Define directory and file names. Each DIR_* must be slash-terminated!
		@define('DIR_ROOT',		$root_directory.'/');
		@define('DIR_CORE',		dirname(__DIR__).'/');
		@define('DIR_APP',		DIR_ROOT.'app/');
		@define('DIR_VAR',		DIR_ROOT.'var/');
		@define('DIR_VENDOR',		dirname(dirname(DIR_CORE)));

		// Use with get_block_filename()
		@define('DIR_CLASS',		'class/');
		@define('DIR_BLOCK',		'block/');
		@define('DIR_TEMPLATE',		'template/');

		// Configuration loader class name
		@define('CLASS_CONFIG_LOADER',	'Cascade\Core\JsonConfig');

		// Use exceptions instead of errors
		set_error_handler(function ($errno, $errstr, $errfile, $errline) {
			if (error_reporting()) {
				throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
			}
		});

		// Go to root directory
		chdir(DIR_ROOT);

		// Add bin directory to $PATH, so bundled tools can be used
		putenv('PATH='.DIR_ROOT.'bin'.(DIRECTORY_SEPARATOR == '/' ? ':' : ';').(getenv('PATH') ? : '/usr/bin:/bin'));

		// Initialize config loader and load core config
		$config_loader_class = CLASS_CONFIG_LOADER;
		$config_loader = new $config_loader_class();

		// Load plugins
		$plugin_manager = new PluginManager($root_directory, $config_loader);
		$core_cfg = $plugin_manager->loadCoreConfig();

		// Load remaining configuration

		/* If true, this is main program -- this is defined in index.php */
		@define('CASCADE_MAIN', false);

		/* Setup debugging tools -- define few constants used all over this thing */
		@define('DEVELOPMENT_ENVIRONMENT',       $core_cfg['debug']['development_environment']);
		@define('DEBUG_LOGGING_ENABLED',  !empty($core_cfg['debug']['debug_logging_enabled']));
		@define('DEBUG_VERBOSE_BANNER',   !empty($core_cfg['debug']['verbose_banner']));
		@define('DEBUG_PROFILER_STATS_FILE',     $core_cfg['debug']['profiler_stats_file']);

		/* Load php.ini options */
		foreach($core_cfg['php'] as $k => $v) {
			ini_set($k, $v);
		}

		/* Set log file, use filename_format with $_SERVER to generate filename.
		 * (You can have one log file per request.) */
		if ($core_cfg['debug']['error_log'] !== null) {
			ini_set('error_log', filename_format($core_cfg['debug']['error_log'], $_SERVER));
		}

		/* Show banner in log */
		if (CASCADE_MAIN && !empty($core_cfg['debug']['always_log_banner'])) {
			first_msg();
		}

		/* Define constants */
		foreach($core_cfg['define'] as $k => $v) {
			define(strtoupper($k), $v);
		}

		/* Set umask */
		if ($core_cfg['core']['umask'] !== null) {
			umask($core_cfg['core']['umask']);
		}

		/* UTF-8 initializations for PHP older than 5.6 */
		if (PHP_VERSION_ID < 50600) {

			/* Initialize iconv */
			if (function_exists('iconv_set_encoding')) {
				iconv_set_encoding('input_encoding',    'UTF-8');
				iconv_set_encoding('output_encoding',   'UTF-8');
				iconv_set_encoding('internal_encoding', 'UTF-8');
			}

			/* Initialize mb */
			if (function_exists('mb_internal_encoding')) {
				mb_internal_encoding('UTF-8');
			}

		}

		/* Initialize default context */
		$context_cfg = $core_cfg['context'];
		$context_class = $context_cfg['class'];
		$default_context = new $context_class($context_cfg['resources']);
		$default_context->config_loader = $config_loader;
		$default_context->setDefaultLocale($context_cfg['default_locale']);
		$default_context->setLocale($context_cfg['default_locale']);
		$default_context->updateEnviroment();

		/* fix $_GET from lighttpd */
		if (strncmp(@$_SERVER["SERVER_SOFTWARE"], 'lighttpd', 8) == 0 && strstr($_SERVER['REQUEST_URI'],'?')) {
			$_SERVER['QUERY_STRING'] = preg_replace('#^.*?\?#','',$_SERVER['REQUEST_URI']);
			parse_str($_SERVER['QUERY_STRING'], $_GET);
		}

		/* Retrieve $_POST if received Content-Type is text/json */
		if (@$_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST)
			&& @ ($_SERVER['CONTENT_TYPE'] == 'text/json' || $_SERVER['CONTENT_TYPE'] == 'application/json;charset=UTF-8'))
		{
			$_POST = (array) json_decode(file_get_contents('php://input'), TRUE, 512, JSON_BIGINT_AS_STRING);
		}

		return array($default_context, $core_cfg);
	}

	/**
	 * Front controller
	 */
	public static function frontController($core_cfg, $default_context)
	{
		/* Initialize cascade controller */
		$cascade_controller_class = $core_cfg['core']['cascade_controller_class'];
		$cascade = new $cascade_controller_class($default_context->auth, @$core_cfg['block_map'], @$core_cfg['shebangs']);

		/* Initialize block storages */
		uasort($core_cfg['block_storage'], function($a, $b) { return $a['storage_weight'] - $b['storage_weight']; });
		$block_storage_write_allowed = !empty($core_cfg['block_storage_write_allowed']);
		foreach ($core_cfg['block_storage'] as $storage_name => $storage_opts) {
			if ($storage_opts == null) {
				continue;
			}

			// Resolve resources from context
			$resources = @ $storage_opts['_resources'];
			if ($resources) {
				unset($storage_opts['_resources']);
				foreach ($resources as $k => $v) {
					$storage_opts[$k] = $default_context->$v;
				}
			}

			// Create storage
			$storage_class = $storage_opts['storage_class'];
			debug_msg('Initializing block storage "%s" (class %s) ...', $storage_name, $storage_class);
			$s = new $storage_class($storage_opts, $default_context, $storage_name, $block_storage_write_allowed);
			$cascade->addBlockStorage($s, $storage_name);
		}

		/* Prepare starting blocks */
		if (empty($core_cfg['blocks'])) {
			die('Please configure initial set of blocks (app/core.json.php, "blocks" section).');
		} else {
			$cascade->addBlocksFromArray(null, $core_cfg['blocks'], $default_context);
		}

		/* Execute cascade */
		$cascade->start();

		/* dump namespaces */
		//echo '<pre style="text-align: left;">', $cascade->dumpNamespaces(), '</pre>';

		/* Visualize executed cascade */
		if (!empty($core_cfg['debug']['add_cascade_graph'])) {
			/* Dump cascade to DOT */
			$dot = $cascade->exportGraphvizDot($core_cfg['graphviz']['cascade']['doc_link']);
			$hash = md5($dot);
			$link = $core_cfg['graphviz']['cascade']['src_file'];
			$dot_file   = filename_format($link, array('hash' => $hash, 'ext' => 'dot'));
			$movie_file = filename_format($link, array('hash' => $hash, 'ext' => '%06d.dot.gz'));
			$ex_html_file = filename_format($link, array('hash' => $hash, 'ext' => 'exceptions.html.gz'));

			/* Store dot file, it will be rendered later */
			@ mkdir(dirname($dot_file), 0777, true);
			if (!file_exists($dot_file)) {
				file_put_contents($dot_file, $dot);
			}

			/* Prepare dot files for animation, but do not render them, becouse core/animate-cascade.sh will do */
			if (!empty($core_cfg['debug']['animate_cascade'])) {
				$steps = $cascade->currentStep(false) + 1;
				for ($t = 0; $t <= $steps; $t++) {
					$f = sprintf($movie_file, $t);
					if (!file_exists($f)) {
						file_put_contents($f, gzencode($cascade->exportGraphvizDot($link, array(), $t), 2));
					}
				}
			}

			/* Export exceptions to HTML, so they can be displayed with cascade */
			if (!empty($core_cfg['debug']['export_exceptions'])) {
				$exceptions_html = $cascade->exportFailedBlocksExceptionsHtml($core_cfg['graphviz']['cascade']['doc_link']);
				file_put_contents($ex_html_file, gzencode($exceptions_html, 2));
			}

			/* Template object will render & cache image */
			$default_context->template_engine->addObject(null, '_cascade_graph', $core_cfg['debug']['cascade_graph_slot'], 95, 'core/cascade_graph', array(
					'hash' => $hash,
					'link' => $core_cfg['graphviz']['renderer']['link'],
					'profile' => 'cascade',
					'style' => $core_cfg['debug']['add_cascade_graph'],
					'error_count' => $cascade->getFailedBlockCount(),
				));

			/* Store hash to HTTP header */
			header('X-Cascade-Hash: '.$hash);

			/* Log hash to make messages complete */
			extra_msg('Cascade hash: %s', $hash);
		}

		/* Log memory usage */
		if (!empty($core_cfg['debug']['log_memory_usage'])) {
			extra_msg('Cascade memory usage: %s', format_bytes($cascade->getMemoryUsage()));
		}

		/* Generate output */
		$default_context->template_engine->start();

		/* Store profiler statistics */
		if (DEBUG_PROFILER_STATS_FILE) {
			// don't let client wait
			ob_flush();
			flush();

			// open & lock, then update content
			$fn = filename_format(DEBUG_PROFILER_STATS_FILE, array());
			$f = fopen($fn, "c+");
			if ($f) {
				flock($f, LOCK_EX);
				$old_data = stream_get_contents($f);
				if ($old_data) {
					$old_stats = unserialize(gzuncompress($old_data));
				} 
				if (empty($old_stats)) {
					// if missing or corrupted, just start over
					$old_stats = array();
				}
				ftruncate($f, 0);
				rewind($f);
				fwrite($f, gzcompress(serialize($cascade->getExecutionTimes($old_stats)), 2));
				fflush($f);
				flock($f, LOCK_UN);
				fclose($f);
			}
		}
	}

	/**
	 * Get plugin list
	 *
	 * TODO: Move this into some nice class.
	 */
	public static function get_plugin_list()
	{
		global $plugin_list;

		/* $plugin_list contains everything in plugin directory. It is not
		 * filtered becouse CascadeController will not allow ugly block names
		 * to be loaded. */

		return array_filter(array_keys($plugin_list), function($block) {
				/* Same as block name check in CascadeController */
				return !(!is_string($block) || strpos($block, '.') !== FALSE || !ctype_graph($block));
			});
	}

	/**
	 * Get block's file from it's name
	 *
	 * TODO: Move this into some nice class.
	 */
	public static function get_block_filename($block, $extension = '.php')
	{
		global $plugin_list;

		@ list($head, $tail) = explode('/', $block, 2);

		/* Core */
		if ($head == 'core') {
			return DIR_CORE.DIR_BLOCK.$tail.$extension;
		}

		/* Plugins */
		if ($tail !== null && isset($plugin_list[$head])) {
			return DIR_PLUGIN.$head.'/'.DIR_BLOCK.$tail.$extension;
		}

		/* Application */
		return DIR_APP.DIR_BLOCK.$block.$extension;
	}

	/**
	 * Get block's class name
	 *
	 * TODO: Move this into some nice class.
	 */
	public static function get_block_class_name($block)
	{
		$class_name = 'B_'.str_replace('/', '__', $block);
		if (class_exists($class_name)) {
			return $class_name;
		} else {
			return false;
		}
	}

	/**
	 * Get template's file from it's name
	 *
	 * TODO: Move this into some nice class.
	 */
	public static function get_template_filename($output_type, $template_name, $extension = '.php')
	{
		global $plugin_list;

		@ list($head, $tail) = explode('/', $template_name, 2);

		/* Core */
		if ($head == 'core') {
			return DIR_CORE.DIR_TEMPLATE.$output_type.'/'.$tail.$extension;
		}

		/* Plugins */
		if ($tail !== null && isset($plugin_list[$head])) {
			return DIR_PLUGIN.$head.'/'.DIR_TEMPLATE.$output_type.'/'.$tail.$extension;
		}

		/* Application */
		return DIR_APP.DIR_TEMPLATE.$output_type.'/'.$template_name.$extension;
	}

}

