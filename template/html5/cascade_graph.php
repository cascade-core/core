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

	// autodetect graph style
	if ((int) $style === 1) {
		$style = class_exists('NDebugger') && NDebugger::isEnabled() ? 'nette' : 'include';
	} else {
		$style = $style;
	}

	switch ($style) {
		case false:
			// no link
			break;

		case 'link':
			$url = filename_format($link, array('profile' => $profile, 'hash' => $hash, 'ext' => 'html'));

			echo "<div id=\"", htmlspecialchars($id), "\" class=\"cascade_link\">",
				"<a target=\"_blank\" href=\"", htmlspecialchars($url), "\">Cascade graph</a>",
				"</div>\n";
			break;

		default:
		case 'include':
			$url = filename_format($link, array('profile' => $profile, 'hash' => $hash, 'ext' => 'html'));

			echo "<div id=\"", htmlspecialchars($id), "\" class=\"cascade_iframe\" style=\"clear: both;\">\n";
			echo	"\t<hr>\n",
				"\t<h2><a target=\"_blank\" href=\"", htmlspecialchars($url), "\">Cascade</a></h2>\n",
				"\t<iframe src=\"", htmlspecialchars($url), "\" seamless frameborder=\"0\"></iframe>\n",
				"</div>";
			break;

		case 'image':
		case 'page_content':
			$png_url = filename_format($link, array('profile' => $profile, 'hash' => $hash, 'ext' => 'png'));
			$dot_url = filename_format($link, array('profile' => $profile, 'hash' => $hash, 'ext' => 'dot'));

			if ($style == 'image') {
				echo "<div id=\"", htmlspecialchars($id), "\" class=\"cascade_dump\" style=\"clear: both;\">\n";
				echo	"\t<hr>\n",
					"\t<h2>Cascade</h2>\n",
					"\t<div>\n",
					"\t<small>[ ",
						"<a href=\"", htmlspecialchars($png_url), "\">png</a>",
						" | <a href=\"", htmlspecialchars($dot_url), "\">dot</a>",
						" | ", $hash,
					" ]</small>\n";
			} else {
				echo "<div id=\"", htmlspecialchars($id), "\" class=\"cascade_dump\">\n";
			}

			echo '<img src="', htmlspecialchars($png_url), "\">\n";

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
			if ($style == 'image') {
				echo "</div>\n";
			}
			echo "</div>\n";
			break;

		case 'nette':
			class CascadeGraphPanelWidget implements IBarPanel
			{
				var $id;
				var $url;

				function __construct($id, $url)
				{
					$this->id = $id;
					$this->url = $url;
				}

				function getTab() {
					return '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQAgMAAABinRfyAAAABGdBTUEAALGPC/xhBQAAAAxQTFRFAAAAREREzP+q////JSI+0AAAAAF0Uk5TAEDm2GYAAAABYktHRACIBR1IAAAAN0lEQVQI12MIDWBgYMiaACPqrzoAiQtAIjTAMRQoELoSSDCCib8g7l+IRNYEBwYIAdUBkgMaBQDJKhInhoRorQAAAABJRU5ErkJggg==" alt=""> Cascade';
				}

				function getPanel() {
					ob_start();
					echo "<h1>Cascade Graph</h1><div class=\"nette-inner\">\n",
						"\t<iframe src=\"", htmlspecialchars($this->url), "\" seamless frameborder=\"0\"
							onLoad=\"",
								"this.height = (this.contentWindow.document.body.scrollHeight) + 'px';",
								"this.width  = (this.contentWindow.document.body.scrollWidth)  + 'px';",
							"\"></iframe>\n",
						"</div>\n";
					return ob_get_clean();
				}

				function getId() {
					return $this->id;
				}
			}

			$plgpw = new CascadeGraphPanelWidget($id, filename_format($link, array('profile' => $profile, 'hash' => $hash, 'ext' => 'html')));
			NDebugger::addPanel($plgpw);
			break;
	}
}

