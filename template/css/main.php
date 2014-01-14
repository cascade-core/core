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

function TPL_css__core__main($t, $id, $d, $so)
{
	header('Content-Type: text/css');
	$expires = 60*60*24;
	header("Pragma: public");
	header("Cache-Control: maxage=".$expires);
	header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');

	echo "/*\n",
		" * ", htmlspecialchars(
				isset($so['page_title_format'])
					? sprintf($so['page_title_format'], @$so['page_title'])
					: @$so['page_title']
				), "\n",
		" *\n",
		" * Generated file - do NOT edit!\n",
		" *\n",
		" */\n\n";

	$t->processSlot('default');
}

