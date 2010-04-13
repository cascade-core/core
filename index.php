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

/* Define directory and file names.
 * Each DIR_* must be slash-terminated!
 */
define('DIR_ROOT',		dirname(dirname(__FILE__)).'/');
define('DIR_CORE',		DIR_ROOT.'core/');
define('FILE_CORE_CONFIG',	  DIR_CORE.'core.ini.php');
define('DIR_CORE_CLASS',	  DIR_CORE.'class/');
define('DIR_CORE_MODULE',	  DIR_CORE.'module/');
define('DIR_CORE_TEMPLATE',	  DIR_CORE.'template/');
define('DIR_APP',		DIR_ROOT.'app/');
define('FILE_APP_CONFIG',	  DIR_APP.'core.ini.php');
define('DIR_APP_CLASS', 	  DIR_APP.'class/');
define('DIR_APP_MODULE',	  DIR_APP.'module/');
define('DIR_APP_TEMPLATE',	  DIR_APP.'template/');

require(DIR_CORE.'utils.php');


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

/* initialize template engine */
if (isset($core_cfg['template']['engine-class'])) {
	$template = new $core_cfg['template']['engine-class']();
} else {
	$template = new Template();
}

/* set default output type */
if (isset($core_cfg['output']['default_type'])) {
	$template->slot_option_set('root', 'type', $core_cfg['output']['default_type']);
}

/* Start session */
session_start();

/* Initialize default context */
$context_class = empty($core_cfg['core']['context_class']) ? 'Context' : $core_cfg['core']['context_class'];
$default_context = new $context_class();
$default_context->set_locale(DEFAULT_LOCALE);
$default_context->set_template_engine($template);

/* Initialize pipeline controller */
$pipeline = new PipelineController();
$pipeline->set_replacement_table(@$core_cfg['module-map']);

/* Prepare starting modules */
$pipeline->add_modules_from_ini(null, $core_cfg, $default_context);

/* Call app's init file */
if (!empty($core_cfg['core']['app_init_file'])) {
	require(DIR_ROOT.$core_cfg['core']['app_init_file']);
}

/* Execute pipeline */
$pipeline->start();

/* dump namespaces */
//echo '<pre style="text-align: left;">', $pipeline->dump_namespaces(), '</pre>';

/* Visualize executed pipeline */
if (!empty($core_cfg['core']['add_pipeline_graph'])) {
	/* Template object will render & cache image */
	$template->add_object('_pipeline_graph', 'root', 95, 'core/pipeline_graph', array(
			'pipeline' => $pipeline,
			'dot_name' => 'data/graphviz/pipeline-%s.%s',
		));
}

/* End session */
session_write_close();

/* Generate output */
$template->start();


