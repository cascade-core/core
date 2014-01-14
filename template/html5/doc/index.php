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

function TPL_html5__core__doc__index($t, $id, $d, $so)
{
	extract($d);

	$h2 = 'h'.$heading_level;
	$h3 = 'h'.($heading_level + 1);

	echo "<div class=\"doc_index\" id=\"", htmlspecialchars($id), "\">\n";
	
	// Header
	echo "<$h2>", _('Blocks'), "</$h2>\n";

	foreach ($blocks as $prefix => $pack) {
		if (empty($pack)) {
			continue;
		}
		echo "<$h3>", isset($titles[$prefix]) ? $titles[$prefix] : sprintf(_('Plugin: %s'), $prefix), "</$h3>\n";
		echo "<ul>\n";
		foreach ($pack as $m) {
			echo "<li><a href=\"", htmlspecialchars(filename_format($link, array('block' => str_replace('_', '-', $m)))), "\">",
			       	htmlspecialchars($m), "</a></li>";
		}
		echo "</ul>\n";
	}

	echo "</div>\n";
}


