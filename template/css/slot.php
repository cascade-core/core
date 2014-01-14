<?php
/*
 * Copyright (c) 2010, Josef Kufner  <jk@frozen-doe.net>
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

function TPL_css__core__slot($t, $id, $d, $so)
{
	extract($d);

	echo "#", $id, " {\n";

	if (isset($float)) {
		echo "\tfloat: $float;\n";
		echo "\twidth: ", isset($width) ? $width : '100%', ";\n";
	} else if (isset($width)) {
		echo "\twidth: ", $width, ";\n";
	}

	if (isset($clear)) {
		echo "\tclear: $clear;\n";
	}

	echo "}\n\n";
	$t->processSlot($name);
}

