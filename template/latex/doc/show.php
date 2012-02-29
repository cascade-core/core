<?php
/*
 * Copyright (c) 2012, Josef Kufner  <jk@frozen-doe.net>
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

function TPL_latex__core__doc__show($t, $id, $d, $so)
{
	extract($d);

	$h2 = latex_heading_cmd($heading_level);
	$h3 = latex_heading_cmd($heading_level + 1);

	echo "\n% core/doc/show: $id\n";
	echo "\\newpage\n";
	
	// Header
	echo "\\$h2{";
	printf(_('Block %s'), latex_escape($block));
	echo " \\label{sec:block:", preg_replace('/^[a-z0-9]_\//', '-', $block), "}";
	echo "}\n";

	// Class header
	if (isset($class_header)) {
		echo "\\par\\noindent {\\tt ", trim(latex_escape($class_header)), "}\n";
	}

	// Location
	if ($is_local && isset($filename)) {
		echo "\\par\\noindent {";
		$prefix = latex_escape(DIR_ROOT);
		$prefix_len = strlen($prefix);
		$latex_filename = latex_escape($filename);

		printf(_('Location: {\\tt %s}'),
				trim(strncmp($prefix, $latex_filename, $prefix_len) == 0
					? '\\ldots{}/'.substr($latex_filename, $prefix_len)
					: $latex_filename));
		echo "}\n";
	}

	echo "\n";

	// Description
	echo "\\$h3{", _('Description'), "}\n";
	if (empty($description)) {
		echo "\n{\it ", _('Sorry, no description available.'), "}\n";
	} else if (!is_array($description)) {
		echo "\n", $description, "\n";
	} else foreach ($description as $text) {
		echo latex_escape($text), "\n\n";
	}
	echo "\n";

	// Inputs
	if (!empty($inputs)) {
		echo "\\$h3{", _('Inputs'), "}\n",
			"\\begin{description}\n";
		foreach ($inputs as $input) {
			echo "\item[{\\tt ", latex_escape($input['name']), "}] = {",
				$input['value'] == 'array()' || $input['value'] == 'array( )'
						? '{\\it '._('not connected').'}'
						: latex_escape($input['value']),
				"}";
			if (!empty($input['comment'])) {
				echo " \\hfil\\\\\n", join("\\\\\n", array_map('latex_escape', $input['comment']));
			}
			echo "\n";
		}
		echo	"\\end{description}\n",
			"\n";
	}

	// Outputs
	if (!empty($outputs)) {
		echo "\\$h3{", _('Outputs'), "}\n",
			"\\begin{description}\n";
		foreach ($outputs as $output) {
			echo "\item[{\\tt ", latex_escape($output['name']), "}]";
			if (!empty($output['comment'])) {
				echo " \\hfil\\\\\n", join("\\\\\n", array_map('latex_escape', $input['comment']));
			}
			echo "\n";
		}
		echo	"\\end{description}\n",
			"\n";
	}

	// Force exec
	if (isset($force_exec)) {
		echo	"\\$h3{", _('Force Exec flag'), "}\n",
			"\\begin{verbatim}\n", trim(latex_expand_tabs($force_exec)), "\n\\end{verbatim}\n",
			"\n";
	}

	// Code
	if (isset($code)) {
		echo "\\$h3{", _('Code'), "}\n",
			"\\begin{verbatim}\n", latex_expand_tabs($code, 4), "\n\\end{verbatim}\n",
			"\n";
	}

	echo "% end of core/doc/show\n\n";
}

