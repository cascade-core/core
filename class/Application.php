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
		list($plugin_manager, $default_context) = static::initialize($root_directory);
		return static::frontController($plugin_manager, $default_context);
	}


	/**
	 * Initialize PHP and the core stuff
	 */
	public static function initialize($root_directory)
	{
	 	// TODO: Get rid of define() calls.

		// Define directory and file names. Each DIR_* must be slash-terminated!
		if(!defined('DIR_ROOT'))     define('DIR_ROOT',     $root_directory.'/');
		if(!defined('DIR_CORE'))     define('DIR_CORE',     dirname(__DIR__).'/');
		if(!defined('DIR_APP'))      define('DIR_APP',      DIR_ROOT.'app/');
		if(!defined('DIR_VAR'))      define('DIR_VAR',      DIR_ROOT.'var/');
		if(!defined('DIR_VENDOR'))   define('DIR_VENDOR',   dirname(dirname(DIR_CORE)));

		// Configuration loader class name
		if(!defined('CLASS_CONFIG_LOADER')) define('CLASS_CONFIG_LOADER', 'Cascade\Core\JsonConfig');

		// If true, this is main program -- this is defined in index.php
		if(!defined('CASCADE_MAIN')) define('CASCADE_MAIN', false);

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

		/* Setup debugging tools -- define few constants used all over this thing */
		if(!defined('DEVELOPMENT_ENVIRONMENT'))   define('DEVELOPMENT_ENVIRONMENT',       $core_cfg['debug']['development_environment']);
		if(!defined('DEBUG_LOGGING_ENABLED'))     define('DEBUG_LOGGING_ENABLED',  !empty($core_cfg['debug']['debug_logging_enabled']));
		if(!defined('DEBUG_VERBOSE_BANNER'))      define('DEBUG_VERBOSE_BANNER',   !empty($core_cfg['debug']['verbose_banner']));
		if(!defined('DEBUG_PROFILER_STATS_FILE')) define('DEBUG_PROFILER_STATS_FILE',     $core_cfg['debug']['profiler_stats_file']);

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

		return array($plugin_manager, $default_context);
	}

	/**
	 * Front controller
	 */
	public static function frontController(PluginManager $plugin_manager, $default_context)
	{
		$core_cfg = $plugin_manager->loadCoreConfig();

		/* Initialize cascade controller */
		$cascade_controller_class = $core_cfg['core']['cascade_controller_class'];
		$cascade = new $cascade_controller_class($plugin_manager, $default_context->auth, @$core_cfg['block_map'], @$core_cfg['shebangs']);

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
			$s = new $storage_class($storage_opts, $plugin_manager, $default_context, $storage_name, $block_storage_write_allowed);
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
		$template_engine = $default_context->template_engine;
		$template_engine->setPluginManager($plugin_manager);
		$template_engine->start();

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

}

