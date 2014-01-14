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

function latex_escape($text)
{
	return addcslashes($text, '\\{}_^#&$%~');
}


function latex_heading_cmd($level)
{
	$heading = array(
		0 => 'part',
		1 => 'chapter',
		2 => 'section',
		3 => 'subsection',
		4 => 'subsubsection',
		5 => 'paragraph',

		'0*' => 'part*',
		'1*' => 'chapter*',
		'2*' => 'section*',
		'3*' => 'subsection*',
		'4*' => 'subsubsection*',
		'5*' => 'paragraph*',
	);

	return $heading[$level];
}


function latex_expand_tabs($text, $spaces = 8)
{
	$lines = explode("\n", $text);
	foreach ($lines as $line) {
		while (false !== $tab_pos = strpos($line, "\t")) {
			$line = substr($line, 0, $tab_pos)
				.str_repeat(' ', $spaces - $tab_pos % $spaces)
				.substr($line, $tab_pos + 1);
		}
		$result[] = $line;
	}
	return implode("\n", $result);
}

