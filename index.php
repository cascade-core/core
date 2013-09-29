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

define('CASCADE_MAIN', true);

/* Call core's init file */
list($config_loader, $core_cfg) = require(dirname(__FILE__).'/init.php');

/* initialize template engine */
$template = new $core_cfg['output']['template_engine_class']();

/* set default output type */
if (!empty($core_cfg['output']['default_type'])) {
	$template->slotOptionSet('root', 'type', $core_cfg['output']['default_type']);
}

/* Start session if not started yet */
if (!isset($_SESSION)) {
	session_start();
}

/* Initialize default context */
$context_class = $core_cfg['core']['context_class'];
$default_context = new $context_class();
$default_context->setConfigLoader($config_loader);
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
$cascade = new CascadeController($auth, @$core_cfg['block_map']);

/* Initialize block storages */
uasort($core_cfg['block_storage'], function($a, $b) { return $a['storage_weight'] - $b['storage_weight']; });
foreach ($core_cfg['block_storage'] as $storage_name => $storage_opts) {
	if ($storage_opts == null) {
		continue;
	}
	$storage_class = $storage_opts['storage_class'];
	debug_msg('Initializing block storage "%s" (class %s) ...', $storage_name, $storage_class);
	$s = new $storage_class($storage_opts, $default_context);
	$cascade->addBlockStorage($s, $storage_name);
}

/* Prepare starting blocks */
$cascade->addBlocksFromIni(null, $core_cfg, $default_context);

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

	/* Store dot file, it will be rendered later */
	file_put_contents($dot_file, $dot);

	/* Prepare dot files for animation, but do not render them, becouse core/animate-cascade.sh will do */
	if (!empty($core_cfg['debug']['animate_cascade'])) {
		$steps = $cascade->currentStep(false) + 1;
		for ($t = 0; $t <= $steps; $t++) {
			$f = sprintf($movie_file, $t);
			file_put_contents($f, gzencode($cascade->exportGraphvizDot($link, array(), $t), 2));
		}
	}

	/* Template object will render & cache image */
	$template->addObject('_cascade_graph', 'root', 95, 'core/cascade_graph', array(
			'hash' => $hash,
			'link' => $core_cfg['graphviz']['renderer']['link'],
			'profile' => 'cascade',
			'style' => $core_cfg['debug']['add_cascade_graph'],
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

/* Store profiler statistics */
if (DEBUG_PROFILER_STATS_FILE) {
	$fn = filename_format(DEBUG_PROFILER_STATS_FILE);
	$old_stats = file_exists($fn) ? unserialize(gzuncompress(file_get_contents($fn))) : array();
	file_put_contents($fn, gzcompress(serialize($cascade->getExecutionTimes($old_stats)), 2));
	unset($fn, $old_stats);
}

/* Generate output */
$template->start();

