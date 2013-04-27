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

/* Get plugin list */
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

/* Get block's file from it's name */
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

/* Get block's class name */
function get_block_class_name($block)
{
	$class_name = 'B_'.str_replace('/', '__', $block);
	if (class_exists($class_name)) {
		return $class_name;
	} else {
		return false;
	}
}

/* Get template's file from it's name */
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


//--------------------------------------------------------------------------


/* Call core's init file */
$core_cfg = require(dirname(__FILE__).'/init.php');

/* Scan plugins */
$plugin_list = array_flip(scandir(DIR_PLUGIN));

/* initialize template engine */
if (isset($core_cfg['template']['engine-class'])) {
	$template = new $core_cfg['template']['engine-class']();
} else {
	$template = new Template();
}

/* set default output type */
if (isset($core_cfg['output']['default_type'])) {
	$template->slotOptionSet('root', 'type', $core_cfg['output']['default_type']);
}

/* Call app's init file(s) */
if (!empty($core_cfg['core']['app_init_file'])) {
	foreach((array) $core_cfg['core']['app_init_file'] as $f) {
		require(DIR_ROOT.$f);
	}
}

/* Start session if not started yet */
if (!isset($_SESSION)) {
	session_start();
}

/* Initialize default context */
$context_class = empty($core_cfg['core']['context_class']) ? 'Context' : $core_cfg['core']['context_class'];
$default_context = new $context_class();
$default_context->setLocale(DEFAULT_LOCALE);
$default_context->setTemplateEngine($template);

/* Initialize auth object (if set) */
if (!empty($core_cfg['core']['auth_class'])) {
	$auth_class = $core_cfg['core']['auth_class'];
	$auth = new $auth_class();
} else {
	$auth = null;
}

/* Initialize cascade controller */
$cascade = new CascadeController($auth, @$core_cfg['block-map']);

/* Initialize block storages */
foreach (empty($core_cfg['block-storage'])
		? array('ClassBlockStorage' => true, 'IniBlockStorage' => true)
		: $core_cfg['block-storage']
	as $storage_class => $storage_opts)
{
	debug_msg('Initializing block storage "%s" ...', $storage_class);
	$s = new $storage_class($storage_opts);
	$cascade->addBlockStorage($s, $storage_class);
}

/* Prepare starting blocks */
$cascade->addBlocksFromIni(null, $core_cfg, $default_context);

/* Execute cascade */
$cascade->start();

/* dump namespaces */
//echo '<pre style="text-align: left;">', $cascade->dumpNamespaces(), '</pre>';

/* Visualize executed cascade */
if (!empty($core_cfg['debug']['add_cascade_graph'])) {
	/* Template object will render & cache image */
	$template->addObject('_cascade_graph', 'root', 95, 'core/cascade_graph', array(
			'cascade' => $cascade,
			'dot_name_tpl' => DEBUG_CASCADE_GRAPH_FILE,
			'dot_url_tpl' => DEBUG_CASCADE_GRAPH_URL,
			'link' => DEBUG_CASCADE_GRAPH_DOC_LINK,
			'animate' => !empty($core_cfg['debug']['animate_cascade']),
			'style' => @$core_cfg['debug']['add_cascade_graph'],
		));
}

/* Log memory usage */
if (!empty($core_cfg['debug']['log_memory_usage'])) {
	extra_msg('Cascade memory usage: %1.3f B', $cascade->getMemoryUsage() / 1024);
}

/* Store profiler statistics */
if (DEBUG_PROFILER_STATS_FILE) {
	$fn = filename_format(DEBUG_PROFILER_STATS_FILE);
	$old_stats = file_exists($fn) ? unserialize(gzuncompress(file_get_contents($fn))) : array();
	file_put_contents($fn, gzcompress(serialize($cascade->getExecutionTimes($old_stats)), 2));
	unset($fn, $old_stats);
}

/* Generate output */
$template->start();

