#!/usr/bin/env php
<?php
/*
 * Copyright (c) 2012, Josef Kufner  <jk@frozen-doe.net>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. Neither the name of the author nor the names of its contributors
 *    may be used to endorse or promote products derived from this software
 *    without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE REGENTS AND CONTRIBUTORS ``AS IS'' AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED.  IN NO EVENT SHALL THE REGENTS OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
 * OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * SUCH DAMAGE.
 */

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


