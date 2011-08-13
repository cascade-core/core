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

function TPL_html5__core__version($t, $id, $d, $so)
{
	extract($d);

	echo "<div id=\"", htmlspecialchars($id), "\" class=\"version_info\">\n";

	switch ($format) {
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

		case 'details':
			echo "<table>\n";
			echo "<col width=\"20%\">";
			foreach ($version as $part => $ver) {
				echo "<tr><th colspan=\"2\">";
				if ($part == 'app') {
					echo _('Application');
				} else if ($part == 'core') {
					echo _('Core');
				} else if (strncmp($part, 'plugin:', 7) == 0) {
					printf(_('Plugin %s'), substr($part, 7));
				}
				echo "</th></tr>\n";

				foreach ($ver as $k => $v) {
					if ($v == '') {
						continue;
					}
					echo "<tr>";
					echo "<td align=\"right\">";
					printf(_('%s%s:'), htmlspecialchars(strtoupper($k[0])), htmlspecialchars(substr($k, 1)));
					echo "</td><td>", htmlspecialchars($v), "</td>";
					echo "</tr>\n";
				}
			}
			echo "</table>\n";
			break;
	}

	echo "</div>\n";
}

