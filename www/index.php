<?php
/*
 * Copyright (c) 2010, Josef Kufner  <jk@frozen-doe.net>
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

/* Define basic constants. Each DIR_* must be terminated by slash! */
define('DIR_ROOT',		dirname(__FILE__).'/');
define('DIR_CORE_CLASS',	DIR_ROOT.'core/class/');
define('DIR_CORE_MODULE',	DIR_ROOT.'core/module/');
define('DIR_CORE_TEMPLATE',	DIR_ROOT.'core/template/');
define('DIR_APP_CLASS', 	DIR_ROOT.'app/class/');
define('DIR_APP_MODULE',	DIR_ROOT.'app/module/');
define('DIR_APP_TEMPLATE',	DIR_ROOT.'app/template/');
define('FILE_APP_CONFIG',	DIR_ROOT.'app/core.ini.php');
define('FILE_CORE_CONFIG',	DIR_ROOT.'core/core.ini.php');

require(DIR_ROOT.'core/utils.php');


/* Class autoloader */
function __autoload($class)
{
	if ($class[0] == 'M' && $class[1] == '_') {
		$m = strtolower(str_replace('__', '/', substr($class, 2)));
		include(strncmp($m, 'core/', 5) == 0 ? DIR_CORE_MODULE.substr($m, 5).'.php' : DIR_APP_MODULE.$m.'.php');
	} else {
		$f = strtolower($class).'.php';
		$cf = DIR_CORE_CLASS.$f;
		$af = DIR_APP_CLASS.$f;

		if (is_readable($cf)) {
			include($cf);
		} else if (is_readable($af)) {
			include($af);
		}
	}
}

/* Load core configuration */
if (is_readable(FILE_APP_CONFIG)) {
	$core_cfg = parse_ini_file(FILE_APP_CONFIG, true);
} else {
	$core_cfg = parse_ini_file(FILE_CORE_CONFIG, true);
}

/* Load php.ini options */
if (isset($core_cfg['php'])) {
	foreach($core_cfg['php'] as $k => $v) {
		ini_set($k, $v);
	}
}

/* Enable debug logging -- a lot of messages from debug_msg() */
define('DEBUG_LOGGING_ENABLED', !empty($core_cfg['core']['debug_logging_enabled']));

/* Show banner in log */
if (!empty($core_cfg['core']['always_log_banner'])) {
	first_msg();
}

/* Initialize iconv & mb */
if (function_exists('iconv_set_encoding')) {
	iconv_set_encoding('input_encoding',    'UTF-8');
	iconv_set_encoding('output_encoding',   'UTF-8');
	iconv_set_encoding('internal_encoding', 'UTF-8');
}
if (function_exists('mb_internal_encoding')) {
	mb_internal_encoding('UTF-8');
}

/* Call app's init file */
if (!empty($core_cfg['core']['app_init_file'])) {
	require(DIR_ROOT.$core_cfg['core']['app_init_file']);
}

/* initialize template engine */
// todo: move this to context
if (isset($core_cfg['template']['engine-class'])) {
	$Template = new $core_cfg['template']['engine-class']();
} else {
	$Template = new Template();
}

/* Initialize pipeline controller */
$Pipeline = new PipelineController();
$Pipeline->set_replacement_table(@$core_cfg['module-map']);

/* Prepare starting set */
foreach ($core_cfg as $section => $opts) {
	@list($keyword, $id) = explode(':', $section, 2);
	if ($keyword == 'module' && isset($id) && @($module = $opts['.module']) !== null) {
		$force_exec = !empty($opts['.force-exec']);

		/* drop module options and keep only connections */
		unset($opts['.module']);
		unset($opts['.force-exec']);

		/* parse connections */
		foreach($opts as & $out) {
			if (is_array($out) && count($out) == 1) {
				$out = explode(':', $out[0], 2);
			}
		}

		$Pipeline->add_module($id, $module, $force_exec, $opts);
	}
}

/* Execute */
$Pipeline->start();

/* Create/update graphviz cookie */
// TODO: udelat toto jen na pozadani a vyrazne lepe
if (empty($_COOKIE['graphviz-id'])) {
	$gv_id = md5(rand().time().serialize($_SERVER['HTTP_USER_AGENT']));
	setcookie('graphviz-id', $gv_id, time() + 315360000, '/');
	$gv_id = $_SERVER['REMOTE_ADDR'].'-'.$gv_id;
} else {
	$gv_id = $_SERVER['REMOTE_ADDR'].'-'.$_COOKIE['graphviz-id'];
	setcookie('graphviz-id', $_COOKIE['graphviz-id'], time() + 315360000, '/');
}

/* Output */
$Template->start();

/* Visualize executed pipeline */
// TODO: udelat toto jen na pozadani a vyrazne lepe
$dot = $Pipeline->export_graphviz_dot();
$dot_name = 'data/graphviz/pipeline-'.$gv_id;
file_put_contents($dot_name.'.dot', $dot);				// FIXME
$Pipeline->exec_dot($dot, 'png', $dot_name.'.png');			// FIXME
printf('<div style="text-align: center; clear: both; margin: 2em; background: #fff; border: 1px solid #aaa;"><img src="%s" /></div>',
		'/'.$dot_name.'.png');					// FIXME

