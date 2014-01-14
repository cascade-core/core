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

function TPL_html5__core__print_r($t, $id, $d, $so)
{
	extract($d);

	ob_start();
	print_r($data);
	$str = ob_get_clean();

	echo "<div id=\"", htmlspecialchars($id), "\" class=\"print_r\">\n";
	if ($title != '') {
		$h = 'h'.intval($header_level);
		echo "<$h>", htmlspecialchars($title), "</$h>\n";
	}
	echo "<pre>", htmlspecialchars($str), "</pre>\n",
		"</div>\n";
}

