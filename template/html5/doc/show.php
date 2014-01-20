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

function TPL_html5__core__doc__show($t, $id, $d, $so)
{
	extract($d['block_description']);
	extract($d);


	$h2 = 'h'.$heading_level;
	$h3 = 'h'.($heading_level + 1);

	echo "<div class=\"doc_show\" id=\"", htmlspecialchars($id), "\">\n";
	
	// Header
	echo "<$h2>";
	printf(_('Block %s'), htmlspecialchars($block));
	echo "</$h2>\n";

	// Class header
	if (isset($class_header)) {
		echo "<div class=\"class_header\"><small><tt>", htmlspecialchars($class_header), "</tt></small></div>\n";
	}

	// Location
	if ($is_local && isset($filename)) {
		echo "<div class=\"location\"><small>";
		$prefix = htmlspecialchars(DIR_ROOT);
		$prefix_len = strlen($prefix);
		$html_filename = htmlspecialchars($filename);

		printf(_('Location: <a href="open://%s"><tt>%s</tt></a>'),
				htmlspecialchars($filename),
				strncmp($prefix, $html_filename, $prefix_len) == 0 ? '&hellip;/'.substr($html_filename, $prefix_len) : $html_filename);
		echo "</small></div>\n";
	}

	// Description
	echo "<div class=\"description\">\n",
		"<$h3>", _('Description'), "</$h3>\n";
	if (empty($description)) {
		echo "<p>", _('Sorry, no description available.'), "</p>\n";
	} else if (!is_array($description)) {
		echo "<p>", htmlspecialchars($description), "</p>";
	} else foreach ($description as $text) {
		echo "<pre>", htmlspecialchars($text), "</pre>";
	}
	echo "</div>\n";

	// Inputs
	if (!empty($inputs)) {
		echo "<div class=\"inputs\">\n",
			"<$h3>", _('Inputs'), "</$h3>\n",
			"<table class=\"table\">\n",
			"<tr><th>", _('Input'), "</th><th>", _('Default value'), "</th><th>", _('Comment'), "</th></tr>\n";
		foreach ($inputs as $input) {
			echo "<tr>",
				"<td>", htmlspecialchars($input['name']), "</td>",
				"<td>", $input['value'] == 'array()' || $input['value'] == 'array( )'
						? '<i>'._('not connected').'</i>'
						: htmlspecialchars($input['value']), "</td>",
				"<td>", join("\n", array_map('htmlspecialchars', $input['comment'])), "</td>",
				"</tr>";
		}
		echo	"</table>\n",
			"</div>\n";
	}

	// Outputs
	if (!empty($outputs)) {
		echo "<div class=\"outputs\">\n",
			"<$h3>", _('Outputs'), "</$h3>\n",
			"<table class=\"table\">\n",
			"<tr><th>", _('Output'), "</th><th>", _('Comment'), "</th></tr>\n";
		foreach ($outputs as $output) {
			echo "<tr>",
				"<td>", htmlspecialchars($output['name']), "</td>",
				"<td>", join("\n", array_map('htmlspecialchars', $output['comment'])), "</td>",
				"</tr>";
		}
		echo	"</table>\n",
			"</div>\n";
	}

	// Force exec
	if (isset($force_exec)) {
		echo "<div class=\"force_exec\">\n",
			"<$h3>", _('Force Exec flag'), "</$h3>\n",
			"<pre>", htmlspecialchars(trim($force_exec)), "</pre>\n",
			"</div>\n";
	}

	// Code
	if (isset($code)) {
		echo "<div class=\"code\">\n",
			"<$h3>", _('Code'), "</$h3>\n",
			"<pre style=\"overflow: auto;\">";
		highlight_string($code);
		echo	"</pre>\n",
			"</div>\n";
	}

	// Block composition
	if (!$t->isSlotEmpty($composition_slot)) {
		echo "<div class=\"block_composition\">\n",
			"<$h3>", _('Block composition'), "</$h3>\n";
		$t->processSlot($composition_slot);
		echo "</div>\n";
	}

	echo "</div>\n";
}

