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
define('DIR_ROOT',		dirname(dirname(__FILE__)).'/');
define('DIR_CORE',		DIR_ROOT.'core/');
define('DIR_APP',		DIR_ROOT.'app/');
define('DIR_PLUGIN',		DIR_ROOT.'plugin/');

/* Config files */
define('FILE_CORE_CONFIG',	DIR_CORE.'core.ini.php');
define('FILE_APP_CONFIG',	DIR_APP.'core.ini.php');
define('FILE_DEVEL_CONFIG',	DIR_ROOT.'core.devel.ini.php');

/* Use with get_block_filename() */
define('DIR_CLASS',		'class/');
define('DIR_BLOCK',		'block/');
define('DIR_TEMPLATE',		'template/');

/* Check if this is development environment */
define('DEVELOPMENT_ENVIRONMENT', !! getenv('DEVELOPMENT_ENVIRONMENT'));


require(DIR_CORE.'utils.php');

/* Add bin directory to $PATH, so bundled tools can be used */
putenv('PATH='.DIR_ROOT.'bin'.(DIRECTORY_SEPARATOR == '/' ? ':' : ';').getenv('PATH'));

/* Load core configuration */
if (is_readable(FILE_APP_CONFIG)) {
	$core_cfg = parse_ini_file(FILE_APP_CONFIG, true);
} else {
	$core_cfg = parse_ini_file(FILE_CORE_CONFIG, true);
}

/* Load debugging overrides */
if (DEVELOPMENT_ENVIRONMENT && is_readable(FILE_DEVEL_CONFIG) && function_exists('array_replace_recursive')) {
	$core_cfg = array_replace_recursive($core_cfg, parse_ini_file(FILE_DEVEL_CONFIG, true));
}

/* Load php.ini options */
if (isset($core_cfg['php'])) {
	foreach($core_cfg['php'] as $k => $v) {
		ini_set($k, $v);
	}
}

/* Enable debug logging -- a lot of messages from debug_msg() */
define('DEBUG_LOGGING_ENABLED',  !empty($core_cfg['debug']['debug_logging_enabled']));
define('DEBUG_VERBOSE_BANNER',   !empty($core_cfg['debug']['verbose_banner']));
define('DEBUG_CASCADE_GRAPH_FILE',     @$core_cfg['debug']['cascade_graph_file']);
define('DEBUG_CASCADE_GRAPH_URL',      @$core_cfg['debug']['cascade_graph_url']);
define('DEBUG_CASCADE_GRAPH_DOC_LINK', @$core_cfg['debug']['cascade_graph_doc_link']);
define('DEBUG_PROFILER_STATS_FILE',    @$core_cfg['debug']['profiler_stats_file']);

/* Show banner in log */
if (!empty($core_cfg['debug']['always_log_banner'])) {
	first_msg();
}

/* Default locale */
$lc = empty($core_cfg['core']['default_locale']) ? 'cs_CZ' : $core_cfg['core']['default_locale'];
define('DEFAULT_LOCALE', setlocale(LC_ALL, $lc.'.UTF8', $lc));

/* Define constants */
if (isset($core_cfg['define'])) {
	foreach($core_cfg['define'] as $k => $v) {
		define(strtoupper($k), $v);
	}
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
if (strncmp($_SERVER["SERVER_SOFTWARE"], 'lighttpd', 8) == 0 && strstr($_SERVER['REQUEST_URI'],'?')) {
	$_SERVER['QUERY_STRING'] = preg_replace('#^.*?\?#','',$_SERVER['REQUEST_URI']);
	parse_str($_SERVER['QUERY_STRING'], $_GET);
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

return $core_cfg;
