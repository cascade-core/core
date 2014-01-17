#!/usr/bin/env php
<?
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


if (isset($_SERVER['REMOTE_ADDR'])) {
	die('Please execute this from your command line!');
}

$dirs = array(
	'app',
	'app/block',
	'app/style',
	'app/template',
	'app/class',
	'app/font',
	'app/icon',
	'data',
	'lib',
	'plugin',
	'var',
);

chdir(dirname(dirname(dirname(__FILE__))));

$err = false;
foreach ($dirs as $d) {
	if (!is_dir($d) && !mkdir($d)) {
		$err = true;
	}
}

if ($err) {
	echo "Something gone wrong while creating directories.\n";
} else {
	echo "Directory hiearchy created (or already existed).\n";
	echo "Do not forget to allow read-write access to 'data/' and 'var/'.\n";
}

if (!file_exists('./index.php') && copy('./core/examples/index.php', './index.php')) {
	echo "Bootstrap index.php created.\n";
}

if (!file_exists('./composer.json') && copy('./core/examples/composer.app.json', './composer.json')) {
	echo "Initial composer.json created.\n";
}

if (!file_exists('./.gitignore')) {
	file_put_contents('./.gitignore', "./data\n./lib\n./var\n./*.local.json.php\n");
}

if (!file_exists('./favicon.ico')) {
	file_put_contents('./favicon.ico', base64_decode(
		 'AAABAAEAEBAQAAEABAAoAQAAFgAAACgAAAAQAAAAIAAAAAEABAAAAAAAAAAAAAAAAAAAAAAAAAAA'
		.'AAAAAABVVVUAqv/MAP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'
		.'AAAAAAAAAAAAAAAAMAAAMzMzMzMwIiAzMzMzMzAiIAAzMzMzMCIgMwMwAAAwERAzAzAiIDAREDMw'
		.'ACIgMAAAMzMwIiAzMzMzMAAiIDAAADMDMCIgMCIgMwMwERAwIiAAMzAREDAiIDMzMAAAMBEQMzMz'
		.'MzMwERAzMzMzMzAAADMzMzMzMzMzMzMzMzOD/wAAg/8AAID/AACDYAAAg2AAAIOAAACD4AAA/4AA'
		.'AINgAACDYAAAgOAAAIPgAACD/wAAg/8AAIP/AAD//wAA'));
}

