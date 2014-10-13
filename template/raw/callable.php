<?php
/*
 * Copyright (c) 2011-2014, Josef Kufner  <jk@frozen-doe.net>
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


function TPL_raw__core__callable($t, $id, $d, $so)
{
	extract($d);

	// Content type header
	if (isset($content_type)) {
		header('Content-Type: '.$content_type);
	}

	// Expire header
	if (!empty($expires)) {
		header('Expires: '.gmdate('D, d M Y H:i:s', strtotime($expires)).' GMT');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');
		header('Cache-Control: public');
		header('Pragma: cache');
	}

	// Execute custom callable
	$callable();
}

