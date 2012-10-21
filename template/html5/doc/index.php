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


