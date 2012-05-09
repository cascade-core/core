<?php
/*
 * Copyright (c) 2011, Josef Kufner  <jk@frozen-doe.net>
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

function TPL_html5__core__cascade_graph($t, $id, $d, $so)
{
	extract($d);

	// FIXME: this should not be done here

	if (!is_dir(dirname($dot_name))) {
		@mkdir(dirname($dot_name));
	}

	if (!isset($whitelist)) {
		$whitelist = array();
	}

	$dot = $cascade->export_graphviz_dot($link, $whitelist);
	$hash = md5($dot);

	$dot_file = sprintf($dot_name, $hash, 'dot');
	$movie_file = sprintf($dot_name, $hash, '%06d.dot.gz');
	$png_file = sprintf($dot_name, $hash, 'png');
	$map_file = sprintf($dot_name, $hash, 'map');
	debug_msg('Cascade graph file: %s', $png_file);

	$dot_mtime = @filemtime($dot_file);
	$png_mtime = @filemtime($png_file);
	$map_mtime = @filemtime($png_file);

	if (!$dot_mtime || !$png_mtime || !$map_mtime
			|| $dot_mtime > $png_mtime || $dot_mtime > $map_mtime
			|| $png_mtime <= filemtime(__FILE__) || $map_mtime <= filemtime(__FILE__)
			|| ($animate && !file_exists(sprintf($movie_file, 0))))
	{
		// store dot file, it will be rendered later
		file_put_contents($dot_file, $dot);

		// prepare dot files for animation, but do not render them, becouse core/animate-cascade.sh will do
		if ($animate) {
			$steps = $cascade->current_step(false) + 1;
			for ($t = 0; $t <= $steps; $t++) {
				$f = sprintf($movie_file, $t);
				file_put_contents($f, gzencode($cascade->export_graphviz_dot($link, $whitelist, $t), 2));
			}
		}
		
		// render graph
		$cascade->exec_dot($dot, 'png', $png_file);
		$cascade->exec_dot($dot, 'cmapx', $map_file);
	}


	// autodetect graph style
	if ((int) $d['style'] === 1) {
		$style = class_exists('NDebug') && NDebug::isEnabled() ? 'nette' : 'image';
	} else {
		$style = $d['style'];
	}

	switch ($style) {
		case false:
			// no link
			break;

		case 'link':
			echo "<div id=\"", htmlspecialchars($id), "\" class=\"cascade_link\">",
				"<a target=\"_blank\" href=\"", htmlspecialchars('/'.$png_file), "\">Cascade graph</a>",
				"</div>\n";
			break;

		default:
		case 'image':
		case 'page_content':
			if ($style == 'image') {
				echo "<div id=\"", htmlspecialchars($id), "\" class=\"cascade_dump\" style=\"clear: both;\">\n",
					"\t<hr>\n",
					"\t<h2>Cascade</h2>\n",
					"\t<div><small>[ ",
						"<a href=\"", htmlspecialchars('/'.$png_file), "\">png</a>",
						" | <a href=\"", htmlspecialchars('/'.$dot_file), "\">dot</a>",
						" | ", $hash,
					" ]</small></div>\n";
			} else {
				echo "<div id=\"", htmlspecialchars($id), "\" class=\"cascade_dump\">\n";
			}
			$map_html_name = 'cascade_graph_map__'.htmlspecialchars($id);
			$map_needle = array('<map id="structs" name="structs">', ' title="&lt;TABLE&gt;" alt=""');
			$map_replacement = array('<map id="'.$map_html_name.'" name="'.$map_html_name.'">', '');
			if (!empty($preview)) {
				$map_needle[] = ' target="_blank"';
				$map_replacement[] = '';
			}
			echo str_replace($map_needle, $map_replacement, file_get_contents(DIR_ROOT.$map_file)),
				'<img src="', htmlspecialchars('/'.$png_file), '" usemap="cascade_graph_map__'.htmlspecialchars($id).'">',
				"</div>\n";
			//echo "<pre>", htmlspecialchars($dot), "</pre>\n";
			if ($style == 'page_content' && !empty($errors)) {
				echo "<b>Errors:</b>\n<ul>\n";
				foreach($errors as $e) {
					printf("<li><b>%s</b> (<i>%s</i>): %s</li>\n",
							htmlspecialchars($e['id']),
							htmlspecialchars($e['block']),
							htmlspecialchars($e['error']));
				}
				echo "</ul>\n";
			}
			break;

		case 'nette':
			class CascadeGraphPanelWidget implements IDebugPanel
			{
				var $id;
				var $dot_file;
				var $png_file;
				var $map_file;

				function __construct($id, $hash, $dot_file, $png_file, $map_file)
				{
					$this->id = $id;
					$this->hash = $hash;
					$this->dot_file = $dot_file;
					$this->png_file = $png_file;
					$this->map_file = $map_file;
				}

				function getTab() {
					return '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQAgMAAABinRfyAAAABGdBTUEAALGPC/xhBQAAAAxQTFRFAAAAREREzP+q////JSI+0AAAAAF0Uk5TAEDm2GYAAAABYktHRACIBR1IAAAAN0lEQVQI12MIDWBgYMiaACPqrzoAiQtAIjTAMRQoELoSSDCCib8g7l+IRNYEBwYIAdUBkgMaBQDJKhInhoRorQAAAABJRU5ErkJggg==" alt=""> Cascade';
				}

				function getPanel() {
					return '<h1>Cascade Graph</h1><div class="nette-inner">'
							."\t<div><small>[ "
							.	"<a href=\"".htmlspecialchars('/'.$this->png_file)."\">png</a>"
							.	" | <a href=\"".htmlspecialchars('/'.$this->dot_file)."\">dot</a>"
							.	" | ".$this->hash
							." ]</small></div>\n"
							.str_replace(array('<map id="structs" name="structs">', ' title="&lt;TABLE&gt;" alt=""'),
								array('<map id="cascade_graph_map" name="cascade_graph_map">', ''),
								file_get_contents(DIR_ROOT.$this->map_file))
							.'<img src="'.htmlspecialchars('/'.$this->png_file).'" usemap="cascade_graph_map">'
							.'</div>';
				}

				function getId() {
					return $this->id;
				}
			}
			$plgpw = new CascadeGraphPanelWidget($id, $hash, $dot_file, $png_file, $map_file);
			NDebug::addPanel($plgpw);
			break;
	}
}

