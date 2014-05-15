<?php
/*
 * Copyright (c) 2013, Josef Kufner  <jk@frozen-doe.net>
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

/* Define directory and file names.
 * Each DIR_* must be slash-terminated!
 */
@define('DIR_ROOT',		dirname(dirname(__FILE__)).'/');
@define('DIR_CORE',		DIR_ROOT.'core/');
@define('DIR_APP',		DIR_ROOT.'app/');
@define('DIR_PLUGIN',		DIR_ROOT.'plugin/');
@define('DIR_VAR',		DIR_ROOT.'var/');

/* Use with get_block_filename() */
@define('DIR_CLASS',		'class/');
@define('DIR_BLOCK',		'block/');
@define('DIR_TEMPLATE',		'template/');

/* Configuration loader class name */
@define('CLASS_CONFIG_LOADER',	'Cascade\Core\JsonConfig');

chdir(DIR_ROOT);

require(DIR_CORE.'utils.php');

/* Add bin directory to $PATH, so bundled tools can be used */
putenv('PATH='.DIR_ROOT.'bin'.(DIRECTORY_SEPARATOR == '/' ? ':' : ';').getenv('PATH'));


/* Scan plugins */
$plugin_list = array_flip(scandir(DIR_PLUGIN));		// FIXME: remove this

/**
 * Get plugin list
 *
 * TODO: Move this into some nice class.
 */
function get_plugin_list()
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
function get_block_filename($block, $extension = '.php')
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
function get_block_class_name($block)
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
function get_template_filename($output_type, $template_name, $extension = '.php')
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


/* Composer autoloader */
require DIR_ROOT.'lib/autoload.php';

/* Initialize config loader and load core config */
$config_loader_class = CLASS_CONFIG_LOADER;
$config_loader = new $config_loader_class();
$core_cfg = $config_loader->load('core');

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

/* Use exceptions instead of errors */
if ($core_cfg['debug']['throw_errors']) {
	set_error_handler(function ($errno, $errstr, $errfile, $errline ) {
		if (error_reporting()) {
			throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
		}
	});
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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST)
	&& @ ($_SERVER['CONTENT_TYPE'] == 'text/json' || $_SERVER['CONTENT_TYPE'] == 'application/json;charset=UTF-8'))
{
	$_POST = (array) json_decode(file_get_contents('php://input'), TRUE, 512, JSON_BIGINT_AS_STRING);
}

/* Call app's init file(s) */
if (!empty($core_cfg['core']['app_init_file'])) {
	foreach((array) $core_cfg['core']['app_init_file'] as $f) {
		require(DIR_ROOT.$f);
	}
}

return array($default_context, $core_cfg);

