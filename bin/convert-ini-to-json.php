#!/usr/bin/env php
<?php
/*
 * Copyright (c) 2013, Josef Kufner  <jk@frozen-doe.net>
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

require dirname(dirname(__FILE__)).'/utils.php';

array_shift($argv);

if (count($argv) == 0) {
	die("Usage: ".$argv[0]." source-file.ini\n");
}

foreach ($argv as $src) {
	$dst = preg_replace('/\.ini(\.php)?$/', '.json\1', $src);

	if (file_exists($dst)) {
		echo "File exists - skipping: ", $dst, "\n";
	}

	$data = parse_ini_file($src, TRUE);
	if ($data === FALSE) {
		die('Load error: '.$src."\n");
	}
	write_json_file($dst, $data);
}

