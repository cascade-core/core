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

function TPL_html5__core__main($t, $id, $d, $so)
{
	echo "<!DOCTYPE html>\n";
	echo "<html>\n";

	echo "<head>\n";
	echo "\t<title>".htmlspecialchars(
				isset($so['page_title_format'])
					? sprintf($so['page_title_format'], @$so['page_title'])
					: @$so['page_title']
				)."</title>\n";
	echo "\t<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">\n";
	if ($d['css_link'] !== null) {
		echo "\t<link rel=\"stylesheet\" href=\"", htmlspecialchars($d['css_link']), "\" type=\"text/css\">\n";
	}
	$t->processSlot('html_head');
	echo "</head>\n";
	
	echo "<body id=\"", htmlspecialchars($id), "\" class=\"slot_html_body\">\n";

	echo "<!-- HTML Body -->\n";
	$t->processSlot('html_body');

	if (!$t->isSlotEmpty('default')) {
		echo "<!-- Default slot fallback -->\n";
		$t->processSlot('default');	// fallback
	}

	echo "<!-- End of HTML Body -->\n";
	echo "</body>\n";
	echo "</html>\n";
}

