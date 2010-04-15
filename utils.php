<?php
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

$_utils_php__first_msg = true;


function first_msg()
{
	global $_utils_php__first_msg;

	$_utils_php__first_msg = false;
	debug_msg('New client from %s:%d at %s.', $_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT'], strftime('%F %T', $_SERVER['REQUEST_TIME']));
}

function debug_msg($msg)
{
	global $_utils_php__first_msg;

	if (!DEBUG_LOGGING_ENABLED) {
		return;
	}

	if ($_utils_php__first_msg) {
		first_msg();
	}

	$args = func_get_args();
	unset($args[0]);

	$trace = debug_backtrace();

	if (isset($trace[1])) {
		$t = & $trace[1];
		error_log(@$t['class'].'::'.$t['function'].'(): Debug: '.vsprintf($msg, $args));
	} else {
		error_log(vsprintf($msg, $args));
	}
}


function error_msg($msg)
{
	global $_utils_php__first_msg;

	if ($_utils_php__first_msg) {
		first_msg();
	}

	$args = func_get_args();
	unset($args[0]);

	$trace = debug_backtrace();

	if (isset($trace[1])) {
		$t = & $trace[1];
		error_log($t['class'].'::'.$t['function'].'(): Error: '.vsprintf($msg, $args));
	} else {
		error_log(vsprintf($msg, $args));
	}
}


function log_msg($msg)
{
	global $_utils_php__first_msg;

	if ($_utils_php__first_msg) {
		first_msg();
	}

	$args = func_get_args();
	unset($args[0]);

	error_log(vsprintf($msg, $args));
}


function get_ident($name)
{
	if ((string) $name === '') {
		return '';
	} else {
		// TODO: je potreba zachovat unikatnost
		return preg_replace('/[^A-Za-z0-9_]/', '_', $name);
	}
}


// vim:encoding=utf8:

