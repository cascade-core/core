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

/**
 * Show nice error message and die
 */
function fail($code, $msg, $description = null)
{
	$msg = preg_replace('/[\s]/', ' ', $msg);
	header(sprintf('Status: %d %s', $code, $msg));
	header('Content-Type: text/plain; encoding=UTF-8');
	echo $msg, "\n";
	if ($description !== null) {
		echo "\n", $description, "\n";
	}
	die();
}


/**
 * Render graphviz file
 */
function render_graphviz($src_file, $dst_file, $format)
{
	// Check cache
	$src_mtime = file_exists($src_file) ? filemtime($src_file) : null;
	$dst_mtime = file_exists($dst_file) ? filemtime($dst_file) : null;
	if ($dst_mtime !== null && $src_mtime <= $dst_mtime) {
		// Cache hit, we are done.
		return true;
	}

	// Create dir if does not exist
	$dir = dirname($dst_file);
	if (!is_dir($dir)) {
		mkdir($dir, 0777, true);
	}

	// Execute dot
	$src = file_get_contents($src_file);
	$result = exec_dot($src, $format);
	if (!empty($result)) {
		return file_put_contents($dst_file, $result);
	} else {
		return false;
	}
}


/**
 * Execute dot (copy of CascadeController::execDot)
 */
function exec_dot($dot_source, $out_type, $out_file = null)
{
	$descriptorspec = array(
		0 => array('pipe', 'r'),
		1 => ($out_file == null ? array('pipe', 'w') : array('file', $out_file, 'w')),
	);
	$pipe = null;

	$proc = proc_open('dot -T '.escapeshellarg($out_type), $descriptorspec, $pipe);

	if (is_resource($proc)) {

		/* send dot source */
		fwrite($pipe[0], $dot_source);
		fclose($pipe[0]);

		if ($out_file == null) {
			/* load result */
			$result = stream_get_contents($pipe[1]);
			fclose($pipe[1]);

			$ret_code = proc_close($proc);
			return ($ret_code == 0 ? $result : false);
		} else {
			$ret_code = proc_close($proc);
			return ($ret_code == 0 ? true : false);
		}
	} else {
		return false;
	}
}



/* Generate link suitable for use in HTML attribute */
function url($format = null, $hash = null)
{
	$q = $_GET;
	if ($hash !== null) {
		$q['hash'] = $hash;
	}
	if ($format !== null) {
		$q['format'] = $format;
	}
	return '?'.http_build_query($q, '', '&amp;');
}


//--------------------------------------------------------------------------

// Call core's init file
list($config_loader, $core_cfg) = require(dirname(__FILE__).'/init.php');

// Get parameters
$profile = isset($_GET['cfg']   ) ? $_GET['cfg']    : null;
$hash    = isset($_GET['hash']  ) ? $_GET['hash']   : null;
$format  = isset($_GET['format']) ? $_GET['format'] : null;

// Default format
if (empty($format)) {
	$format = 'html';
}

// Check config
if (empty($core_cfg['graphviz'][$profile]) || $profile == 'renderer') {
	fail(500, 'Configuration not found.');
}
$cfg = $core_cfg['graphviz'][$profile];

// Check parameters
if (!preg_match('/^[0-9a-z]{32}$/', $hash) || !preg_match('/^[a-z0-9][a-z0-9-]{1,10}$/', $format)) {
	fail(400, 'Insufficient or invalid parameters received.');
}

$src_file = filename_format($cfg['src_file'], array('hash' => $hash, 'ext' => 'dot'));


// Debug: Dump parameters
/*
fail(200, 'Configuration',
	 "Hash:        ".$hash."\n"
	."Format:      ".$format."\n"
	."Source file: ".$src_file."\n"
	."Cache file:  ".$dst_file."\n"
);
// */

// Generate result
switch ($format) {
	case 'dot':
		// nothing to do, source == cache
		header('Content-Type: text/plain; encoding=UTF-8');
		readfile($src_file);
		break;

	case 'html':
		$map_format = 'cmapx';
		$map_file = filename_format($cfg['cache_file'], array('hash' => $hash, 'ext' => $map_format));

		// Very simple HTML page that links few other files
		header('Content-Type: text/html; encoding=UTF-8');
		echo	"<!DOCTYPE html>\n",
			"<html>\n",
			"<head>\n",
			"<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">\n",
			"<title>", template_format($cfg['title'], array('hash' => $hash)), "</title>\n",
			"<style type=\"text/css\">\n",
				"body { text-align: center; background: #fff; color: #000; margin: 1em; }\n",
				"div { display: block; border: none; margin: 1em auto; padding: 0em 1em; font-size: 0.85em; }\n",
				"img { display: block; border: none; margin: 1em auto; padding: 0em 1em; }\n",
			"</style>\n",
			"</head>\n";
		echo "<body>\n";
		echo "<div>[ ",
				"<a href=\"", url('png'), "\" target=\"_blank\">png</a>",
				" | <a href=\"", url('pdf'), "\" target=\"_blank\">pdf</a>",
				" | <a href=\"", url('dot'), "\" target=\"_blank\">dot</a>",
				" | ", $hash,
			" ]</div>\n";

		render_graphviz($src_file, $map_file, $map_format);
		$map_html_name = 'cascade_graph_map__'.htmlspecialchars($hash);
		$map_needle = array('<map id="structs" name="structs">', ' title="&lt;TABLE&gt;" alt=""');
		$map_replacement = array('<map id="'.$map_html_name.'" name="'.$map_html_name.'">', '');
		echo str_replace($map_needle, $map_replacement, file_get_contents($map_file));

		echo "<img src=\"", url('png'), "\" usemap=\"#cascade_graph_map__", htmlspecialchars($hash), "\">\n";

		echo "</body>\n";
		echo "</html>\n";
		break;

	default:
		$dst_file = filename_format($cfg['cache_file'], array('hash' => $hash, 'ext' => $format));

		// Call graphviz
		render_graphviz($src_file, $dst_file, $format);
		
		// Send file from cache
		$content_type_map = array(
			'dot'   => 'text/plain; encoding=UTF-8',
			'xdot'  => 'text/plain; encoding=UTF-8',
			'plain' => 'text/plain; encoding=UTF-8',
			'cmapx' => 'text/html; encoding=UTF-8',
			'png'   => 'image/png',
			'svg'   => 'image/svg+xml',
			'pdf'   => 'application/pdf',
			'eps'   => 'application/postscript',
		);
		header('Content-Type: '.(isset($content_type_map[$format]) ? $content_type_map[$format] : 'application/octet-stream'));
		if ($format != 'dot' && $format != 'cmapx') {
			header('Content-Disposition: inline; filename="'.basename($dst_file).'"');
		}
		readfile($dst_file);
		break;
}

