<?php
/*
 * Copyright (c) 2010, Josef Kufner  <jk@frozen-doe.net>
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

define('CASCADE_MAIN', true);

/* Call core's init file */
list($default_context, $core_cfg) = require(dirname(__FILE__).'/init.php');

/* Start session if not started yet */
// TODO: Remove PHP session (required by message queue)
if (!isset($_SESSION)) {
	session_start();
}

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

