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
					printf(_('Plugin: %s'), substr($part, 7));
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

