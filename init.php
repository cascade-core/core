<?php
/*
 * Copyright (c) 2013, Josef Kufner  <jk@frozen-doe.net>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. Neither the name of the author nor the names of its contributors
 *    may be used to endorse or promote products derived from this software
 *    without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE REGENTS AND CONTRIBUTORS ``AS IS'' AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED.  IN NO EVENT SHALL THE REGENTS OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
 * OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * SUCH DAMAGE.
 */

/* Define directory and file names.
 * Each DIR_* must be slash-terminated!
 */
@define('DIR_ROOT',		dirname(dirname(__FILE__)).'/');
@define('DIR_CORE',		DIR_ROOT.'core/');
@define('DIR_APP',		DIR_ROOT.'app/');
@define('DIR_PLUGIN',		DIR_ROOT.'plugin/');

/* Use with get_block_filename() */
@define('DIR_CLASS',		'class/');
@define('DIR_BLOCK',		'block/');
@define('DIR_TEMPLATE',		'template/');

/* Configuration loader class name */
@define('CLASS_CONFIG_LOADER',	'JsonConfig');

require(DIR_CORE.'utils.php');

/* Add bin directory to $PATH, so bundled tools can be used */
putenv('PATH='.DIR_ROOT.'bin'.(DIRECTORY_SEPARATOR == '/' ? ':' : ';').getenv('PATH'));


/* Scan plugins */
$plugin_list = array_flip(scandir(DIR_PLUGIN));		// FIXME: remove this

/**
 * Get plugin list
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

/* Class autoloader */
spl_autoload_register(function ($class)
{
	global $plugin_list;
	$lc_class = strtolower($class);
	@ list($head, $tail) = explode("\\", $lc_class, 2);

	/* Block */
	if ($tail === null && $class[0] == 'B' && $class[1] == '_') {
		$m = str_replace('__', '/', substr($lc_class, 2));
		$f = get_block_filename($m);
		if (file_exists($f)) {
			include($f);
		}
		return;
	}

	/* Plugin (by namespace) */
	if ($tail !== null && isset($plugin_list[$head])) {
		$f = DIR_PLUGIN.$head.'/'.DIR_CLASS.str_replace("\\", '/', $tail).'.php';
		if (file_exists($f)) {
			include($f);
		}
		return;
	}

	/* Core */
	$f = str_replace("\\", '/', strtolower($class)).'.php';
	$cf = DIR_CORE.DIR_CLASS.$f;
	if (file_exists($cf)) {
		include($cf);
		return;
	}

	/* Application */
	$af = DIR_APP.DIR_CLASS.$f;
	if (file_exists($af)) {
		include($af);
		return;
	}
});


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
@define('DEBUG_CASCADE_GRAPH_FILE',      $core_cfg['debug']['cascade_graph_file']);
@define('DEBUG_CASCADE_GRAPH_URL',       $core_cfg['debug']['cascade_graph_url']);
@define('DEBUG_CASCADE_GRAPH_DOC_LINK',  $core_cfg['debug']['cascade_graph_doc_link']);
@define('DEBUG_PROFILER_STATS_FILE',     $core_cfg['debug']['profiler_stats_file']);

/* Load php.ini options */
foreach($core_cfg['php'] as $k => $v) {
	ini_set($k, $v);
}

/* Show banner in log */
if (CASCADE_MAIN && !empty($core_cfg['debug']['always_log_banner'])) {
	first_msg();
}

/* Default locale */
$lc = $core_cfg['core']['default_locale'];
define('DEFAULT_LOCALE', setlocale(LC_ALL, $lc.'.UTF8', $lc));

/* Define constants */
foreach($core_cfg['define'] as $k => $v) {
	define(strtoupper($k), $v);
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

/* fix $_GET from lighttpd */
if (strncmp(@$_SERVER["SERVER_SOFTWARE"], 'lighttpd', 8) == 0 && strstr($_SERVER['REQUEST_URI'],'?')) {
	$_SERVER['QUERY_STRING'] = preg_replace('#^.*?\?#','',$_SERVER['REQUEST_URI']);
	parse_str($_SERVER['QUERY_STRING'], $_GET);
}

return $core_cfg;

