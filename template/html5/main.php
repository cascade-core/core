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

	$t->processSlot('html_body');

	if (!$t->isSlotEmpty('default')) {
		$t->processSlot('default');	// fallback
	}

	echo "</body>\n";
	echo "</html>\n";
}

