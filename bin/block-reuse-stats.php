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


chdir('..');

echo
	"\n",
	"\tBlock reuse statistics:\n",
	"\n";

function show($label, $value) {
	printf("%-25s %s\n", $label.':', $value);
}

$routes = parse_ini_file('app/routes.ini.php', true);

if (isset($routes['#'])) {
	$defaults = $routes['#'];
	unset($routes['#']);
} else {
	$defaults = array();
}

$route_count = 0;
$usage = array();

foreach ($routes as $r) {
	if (isset($r['content'])) {
		$c_name = $r['content'];
	} else {
		$c_name = @ $defaults['content'];
	}
	if ($c_name == '') {
		continue;
	}

	$route_count++;

	$content = parse_ini_file('app/block/page/'.$c_name.'.ini.php', true);

	foreach ($content as $k => $c) {
		if (preg_match('/^block:/', $k)) {
			@ $usage[$c['.block']]++;
		}
	}
}
arsort($usage);

show("Route count", $route_count);
show("Block instances used", array_sum($usage));
show("Average block usage", array_sum($usage) / $route_count);

$vals = array_values($usage);
show("Median", $vals[round(count($usage) / 2)]);

echo "\n";

foreach ($usage as $m => $c) {
	show($m, $c);
}
echo "\n";

$max = max($usage) + 1;
$hist = array_pad(array(), $max + 1, 0);

foreach ($usage as $v) {
	$hist[$v]++;
}

echo "Histogram:\n";
show('Use count', 'Block count');
for ($i = 1; $i <= $max; $i++) {
	show($i, $hist[$i]);
}


