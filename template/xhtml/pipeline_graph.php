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

function TPL_html5__core__pipeline_graph($t, $id, $d, $so)
{
	extract($d);

	// FIXME: this should not be done here

	if (!is_dir(dirname($dot_name))) {
		@mkdir(dirname($dot_name));
	}

	$dot = $pipeline->export_graphviz_dot();
	$hash = md5($dot);

	$dot_file = sprintf($dot_name, $hash, 'dot');
	$png_file = sprintf($dot_name, $hash, 'png');

	$dot_mtime = @filemtime($dot_file);
	$png_mtime = @filemtime($png_file);

	if (!$dot_mtime || !$png_mtime || $dot_mtime > $png_mtime || $png_mtime <= filemtime(__FILE__)) {
		file_put_contents($dot_file, $dot);
		$pipeline->exec_dot($dot, 'png', $png_file);
	}


	// autodetect graph style
	if ((int) $d['style'] === 1) {
		$style = class_exists('NDebug') ? 'nette' : 'image';
	} else {
		$style = $d['style'];
	}

	switch ($style) {
		case false:
			// no link
			break;

		case 'link':
			echo "<div id=\"", htmlspecialchars($id), "\" class=\"pipeline_link\">",
				"<a target=\"_blank\" href=\"", htmlspecialchars('/'.$png_file), "\">Pipeline graph</a>",
				"</div>\n";
			break;

		default:
		case 'image':
			echo "<div id=\"", htmlspecialchars($id), "\" class=\"pipeline_dump\" style=\"clear: both;\">\n",
				"\t<hr/>\n",
				"\t<h2>Pipeline</h2>\n",
				"\t<div><small>[ ",
					"<a href=\"", htmlspecialchars('/'.$png_file), "\">png</a>",
					" | <a href=\"", htmlspecialchars('/'.$dot_file), "\">dot</a>",
					" ]</small></div>\n",
				"\t<img src=\"", htmlspecialchars('/'.$png_file), "\" />\n",
				"</div>\n";
			//echo "<pre>", htmlspecialchars($dot), "</pre>\n";
			break;

		case 'nette':
			class PipelineGraphPanelWidget implements IDebugPanel
			{
				var $id;
				var $png_file;

				function __construct($id, $png_file)
				{
					$this->id = $id;
					$this->png_file = $png_file;
				}

				function getTab() {
					return '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQAgMAAABinRfyAAAABGdBTUEAALGPC/xhBQAAAAxQTFRFAAAAREREzP+q////JSI+0AAAAAF0Uk5TAEDm2GYAAAABYktHRACIBR1IAAAAN0lEQVQI12MIDWBgYMiaACPqrzoAiQtAIjTAMRQoELoSSDCCib8g7l+IRNYEBwYIAdUBkgMaBQDJKhInhoRorQAAAABJRU5ErkJggg==" alt="" /> Pipeline';
				}

				function getPanel() {
					return '<h1>Pipeline Graph</h1><div class="nette-inner"><img src="'.htmlspecialchars('/'.$this->png_file).'"></div>';
				}

				function getId() {
					return $this->id;
				}
			}
			$plgpw = new PipelineGraphPanelWidget($id, $png_file);
			NDebug::addPanel($plgpw);
			break;
	}
}

