#!/usr/bin/env php
<?php
/*
 * Copyright (c) 2012, Josef Kufner  <jk@frozen-doe.net>
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

if (count($argv) != 2) {
	die('Usage: '.$argv[0].' file');
}

var_export(unserialize(gzuncompress((file_get_contents($argv[1])))));
echo "\n";
