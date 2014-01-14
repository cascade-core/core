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

function TPL_latex__core__version($t, $id, $d, $so)
{
	extract($d);

	echo "\n% core/version: ", $id, "\n";

	switch ($format) {
		// show only version string
		default:
		case 'short':
			echo latex_escape($prefix);
			echo "{\tt ", latex_escape($version['app']['version']), "}";
			echo latex_escape($suffix), "\n";
			break;

		// show selected fields in table
		case 'details':
			$fields = array('version', 'date', 'note', 'error');

		// show everything in table
		case 'full':

			echo "\\begin{tabular}{ll}\n";
			foreach ($version as $part => $ver) {
				echo "\\multicolumn{2}{l}{\bf ";
				if ($part == 'app') {
					echo _('Application');
				} else if ($part == 'core') {
					echo _('Core');
				} else if (strncmp($part, 'plugin:', 7) == 0) {
					printf(_('Plugin: %s'), latex_escape(substr($part, 7)));
				}
				echo "} \\\\\n";

				foreach (isset($fields) ? $fields : array_keys($ver) as $k) {
					if (@$ver[$k] == '') {
						continue;
					}
					echo "\t\hspace{2em} ";
					printf(_('%s%s:'), latex_escape(strtoupper($k[0])), latex_escape(substr($k, 1)));
					echo " & ";
					if ($k == 'version') {
						echo "{\\tt ", latex_escape($ver[$k]), "}";
					} else if ($k == 'error' || $k == 'note') {
						echo "{\\it ", latex_escape($ver[$k]), "}";
					} else {
						echo latex_escape($ver[$k]);
					}
					echo " \\\\\n";
				}
			}
			echo "\n\\end{tabular}\n";
			break;
	}

	echo "\n% end of core/version\n";
}

