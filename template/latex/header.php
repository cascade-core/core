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


function TPL_latex__core__header($t, $id, $d, $so)
{
	extract($d);

	if ($option !== null && isset($so[$option])) {
		$str = $so[$option];
	} else if ($text !== null) {
		$str = $text;
	} else {
		return;
	}

	$cmd = latex_heading_cmd($level);
	$latex_text = trim(latex_escape($str));

	echo	"\n% core/header: ", $id, "\n",
		"\\", $cmd, "{", $latex_text,
		"\\label{", trim($cmd, '*'), ":", preg_replace('/-?([:\/])-?/', '\1', preg_replace('/[^A-Za-z0-9:\/]+/', '-', $latex_text)), "}}\n\n";
}

