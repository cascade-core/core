#!/usr/bin/env php
<?
/*
 * Copyright (c) 2010, Josef Kufner  <jk@frozen-doe.net>
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


if (isset($_SERVER['REMOTE_ADDR'])) {
	die('Please execute this from your command line!');
}

$dirs = array(
	'app',
	'app/module',
	'app/style',
	'app/template',
	'app/default-config',
	'app/lib',
	'app/class',
	'app/font',
	'app/icon',
	'data',
	'var',
);

chdir(dirname(dirname(realpath($argv[0]))));

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

if (!file_exists('./index.php') && copy('./core/skeleton-index.php', './index.php')) {
	echo "Bootstrap index.php created\n";
}

if (!file_exists('./.gitignore')) {
	file_put_contents('./.gitignore', "./data\n./var\n");
}


// vim:encoding=utf8:

