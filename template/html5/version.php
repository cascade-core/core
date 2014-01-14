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

function TPL_html5__core__version($t, $id, $d, $so)
{
	extract($d);

	echo "<div id=\"", htmlspecialchars($id), "\" class=\"version_info\">\n";

	switch ($format) {
		// show only version string
		default:
		case 'short':
			echo htmlspecialchars($prefix);
			if ($link) {
				echo "<a href=\"", htmlspecialchars($link), "\">";
			}
			echo "<code title=\"", htmlspecialchars($version['app']['date']), "\">",
					htmlspecialchars($version['app']['version']), "</code>";
			if ($link) {
				echo "</a>";
			}
			echo htmlspecialchars($suffix), "\n";
			break;

		// show selected fields in table
		case 'details':
			$fields = array('version', 'date', 'note', 'error');

		// show everything in table
		case 'full':

			echo "<table class=\"table\">\n";
			echo "<col width=\"20%\">";
			foreach ($version as $part => $ver) {
				echo "<tr><th colspan=\"2\">";
				if ($part == 'app') {
					echo _('Application');
				} else if ($part == 'core') {
					echo _('Core');
				} else if (strncmp($part, 'plugin:', 7) == 0) {
					printf(_('Plugin: %s'), substr($part, 7));
				}
				echo "</th></tr>\n";

				foreach (isset($fields) ? $fields : array_keys($ver) as $k) {
					if (@$ver[$k] == '') {
						continue;
					}
					echo "<tr class=\"", $k, "\">";
					echo "<td align=\"right\">";
					printf(_('%s%s:'), htmlspecialchars(strtoupper($k[0])), htmlspecialchars(substr($k, 1)));
					echo "</td><td>";
					if ($k == 'version') {
						echo "<code>", htmlspecialchars($ver[$k]), "</code>";
					} else if ($k == 'error' || $k == 'note') {
						echo "<i>", htmlspecialchars($ver[$k]), "</i>";
					} else {
						echo htmlspecialchars($ver[$k]);
					}
					echo "</td></tr>\n";
				}
			}
			echo "</table>\n";
			break;
	}

	echo "</div>\n";
}

