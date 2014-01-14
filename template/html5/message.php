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

function TPL_html5__core__message($t, $id, $d, $so)
{
	extract($d);

	if (isset($msg_id)) {
		unset($_SESSION['message_queue'][$msg_id]);
	}

	echo "<div id=\"", htmlspecialchars($id), "\" class=\"message message_", $type, "\">\n",
		"\t<h2>", htmlspecialchars($title), "</h2>\n";

	if (is_array($text)) {
		foreach ($text as $t) {
			echo "\t<p>", htmlspecialchars($t), "</p>\n";
		}
	} else if ($text != '') {
		echo "\t<p>", htmlspecialchars($text), "</p>\n";
	}

	echo "</div>\n\n";
}

