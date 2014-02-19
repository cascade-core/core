<?php
/*
 * Copyright (c) 2011, Josef Kufner  <jk@frozen-doe.net>
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
				"\t<iframe src=\"", htmlspecialchars($url), "\" seamless frameborder=\"0\" width=\"100%\" height=\"85%\"></iframe>\n",
				"</div>\n";
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

