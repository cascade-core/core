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
	debug_msg('New client from %s:%d at %s, requesting "%s"',
			$_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT'],
			strftime('%Y-%m-%d %H:%M:%S', $_SERVER['REQUEST_TIME']),
			$_SERVER['REQUEST_URI']);
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
		error_log(@$t['class'].'::'.$t['function'].'(): Error: '.vsprintf($msg, $args));
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


function format_bytes($bytes)
{
	static $units = array(
		array( ' B', 1),
		array(' KB', 1024.),
		array(' MB', 1048576.),
		array(' GB', 1073741824.),
		array(' TB', 1099511627776.),
	);
	$u = & $units[(int) log($bytes, 2) / 10];
	return round($bytes / $u[1], 2).$u[0];
}


function template_format($template, array $values, $escaping_function = htmlspecialchars)
{
	$available_functions = array(
		'sprintf' => sprintf,
		'strftime' => strftime,
		'date' => strftime,
		'time' => strftime,
	);

	$tokens = preg_split('/({)'
				.'([a-zA-Z0-9_.-]+)'				// symbol name
				.'(?:'
					.'([:%])([a-zA-Z0-9_}]*)'		// function name
					."(?:([:])((?:[^}\\\\]|\\\\.)*))?"	// format string
				.')?'
				.'(})/',
			$template, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

	$status = 0;		// Current status of parser
	$append = 0;		// Append value to result after token is processed ?
	$result = array();

	foreach($tokens as $token) {
		switch ($status) {
			// text around
			case 0:
				if ($token === '{') {
					$status = 10;
					$function = null;
					$fmt = null;
				} else {
					$result[] = $token;
				}
				break;

			// first part
			case 10:
				$key = $token;
				$status = 20;
				break;

			// first separator
			case 20:
				if ($token === '}') {
					// end
					$append = true;
					$status = 0;
				} else if ($token === '%') {
					$function = sprintf;
					$status = 51;
				} else if ($token === ':') {
					$status = 30;
				} else {
					return FALSE;
				}
				break;

			// format function
			case 30:
				if (array_key_exists($token, $available_functions)) {
					$function = $available_functions[$token];
				} else {
					$function = null;
				}
				$status = 40;
				break;

			// second separator
			case 40:
				if ($token === ':') {
					$status = 50;
				} else if ($token === '}') {
					$append = true;
					$status = 0;
				} else {
					return FALSE;
				}
				break;

			// format string
			case 50:
				$fmt = preg_replace("/\\\\(.)/", "\\1", $token);
				$status = 90;
				break;

			// format string, prepend %
			case 51:
				$fmt = '%'.str_replace(array('\\\\', '\:', '\}'), array('\\', ':', '}'), $token);
				$status = 90;
				break;

			// end
			case 90:
				if ($token === '}') {
					$append = true;
					$status = 0;
				} else {
					return FALSE;
				}
				break;
		}

		if ($append) {
			$append = false;
			if (!array_key_exists($key, $values)) {
				$v = '{?'.$key.'?}';
			} else if ($function !== null) {
				$v = $function($fmt, $values[$key]);
			} else {
				$v = $values[$key];
			}
			if ($escaping_function) {
				$result[] = $escaping_function($v);
			} else {
				$result[] = $v;
			}
		}
	}
	return join('', $result);
}

