<?php
/*
 * Copyright (c) 2012, Josef Kufner  <jk@frozen-doe.net>
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

function TPL_latex__core__doc__show($t, $id, $d, $so)
{
	extract($d);

	$h2 = latex_heading_cmd($heading_level);
	$h3 = latex_heading_cmd($heading_level + 1);

	echo "\n% core/doc/show: $id\n";
	echo "\\newpage\n";
	echo "\\begin{samepage}\n";
	
	// Header
	echo "\\$h2{";
	printf(_('Block %s'), latex_escape($block));
	echo " \\label{sec:block:", preg_replace('/^[a-z0-9]_\//', '-', $block), "}";
	echo "}\n";

	// Class header
	if (isset($class_header)) {
		echo "\\noindent {\\tt ", trim(latex_escape($class_header)), "}";
	}

	// Location
	if ($is_local && isset($filename)) {
		if (isset($class_header)) {
			echo "\\\\\n";
		} else {
			echo "\\noindent ";
		}
		echo "{";
		$prefix = latex_escape(DIR_ROOT);
		$prefix_len = strlen($prefix);
		$latex_filename = latex_escape($filename);

		printf(_('Location: {\\tt %s}'),
				trim(strncmp($prefix, $latex_filename, $prefix_len) == 0
					? '\\ldots{}/'.substr($latex_filename, $prefix_len)
					: $latex_filename));
		echo "}";
	}

	echo "\n\n";

	// Description
	echo "\\$h3{", _('Description'), "}\n";
	echo "\\end{samepage}\n";
	if (empty($description)) {
		echo "\n{\it ", _('Sorry, no description available.'), "}\n";
		if (DEVELOPMENT_ENVIRONMENT) {
			echo "\n\\TODO{Add description.}\n";
		}
	} else if (!is_array($description)) {
		echo "\n", latex_escape($description), "\n";
	} else foreach ($description as $text) {
		echo latex_escape($text), "\n";
	}
	echo "\\bigskip{}\n\n";

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
				echo " \\hfil\\\\\n", join("\\\\\n", array_map('latex_escape', $output['comment']));
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

	echo "\\pagebreak[3]\n";
	echo "% end of core/doc/show\n\n";
}

