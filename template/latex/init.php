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

function latex_escape($text)
{
	return addcslashes($text, '$_\\');
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

