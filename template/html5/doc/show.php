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

function TPL_html5__core__doc__show($t, $id, $d, $so)
{
	extract($d);

	echo "<div class=\"doc_show\" id=\"", htmlspecialchars($id), "\">\n";
	
	// Header
	echo "<h2>";
	printf(_('Module %s'), htmlspecialchars($module));
	echo "</h2>\n";

	// Class header
	if (isset($class_header)) {
		echo "<div class=\"class_header\"><small><tt>", htmlspecialchars($class_header), "</tt></small></div>\n";
	}

	// Location
	if ($is_local && isset($filename)) {
		echo "<div class=\"location\"><small>";
		printf(_('Location: <a href="open://%s"><tt>%s</tt></a>'), htmlspecialchars($filename), htmlspecialchars($filename));
		echo "</small></div>\n";
	}

	// Description
	echo "<div class=\"description\">\n",
		"<h3>", _('Description'), "</h3>\n";
	if (empty($description)) {
		echo "<p>", _('Sorry, no description available.'), "</p>\n";
	} else if (!is_array($description)) {
		echo "<p>", htmlspecialchars($description), "</p>";
	} else foreach ($description as $text) {
		echo "<pre>", htmlspecialchars($text), "</pre>";
	}
	echo "</div>\n";

	// Force exec
	if (isset($force_exec)) {
		echo "<div class=\"force_exec\">\n",
			"<h3>", _('Force Exec flag'), "</h3>\n",
			"<pre>", htmlspecialchars($force_exec), "</pre>\n",
			"</div>\n";
	}

	// Inputs
	if (isset($inputs)) {
		echo "<div class=\"inputs\">\n",
			"<h3>", _('Inputs'), "</h3>\n",
			"<pre>", htmlspecialchars($inputs), "</pre>\n",
			"</div>\n";
	}

	// Outputs
	if (isset($outputs)) {
		echo "<div class=\"outputs\">\n",
			"<h3>", _('Outputs'), "</h3>\n",
			"<pre>", htmlspecialchars($outputs), "</pre>\n",
			"</div>\n";
	}

	// Code
	if (isset($code)) {
		echo "<div class=\"code\">\n",
			"<h3>", _('Code'), "</h3>\n",
			"<pre style=\"overflow: auto;\">";
		highlight_string($code);
		echo	"</pre>\n",
			"</div>\n";
	}

	echo "</div>\n";
}

