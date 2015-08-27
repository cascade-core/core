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

$_utils_php__first_msg = true;


function first_msg()
{
	global $_utils_php__first_msg;

	$_utils_php__first_msg = false;
	if (php_sapi_name() == "cli") {
		return;
	}
	if (DEBUG_VERBOSE_BANNER) {
		error_log('--');
		error_log(sprintf('New client:  %s:%d  at  %s',
				$_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT'],
				strftime('%Y-%m-%d %H:%M:%S', $_SERVER['REQUEST_TIME'])));
		error_log(sprintf('Request:     %s %s',
				$_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']));
		error_log(sprintf('User Agent:  %s', $_SERVER['HTTP_USER_AGENT']));
		error_log('--');
	} else {
		error_log(sprintf('New client from %s:%d at %s: %s "%s"',
				$_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT'],
				strftime('%Y-%m-%d %H:%M:%S', $_SERVER['REQUEST_TIME']),
				$_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']));
	}
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


function extra_msg($msg)
{
	global $_utils_php__first_msg;

	if ($_utils_php__first_msg) {
		return;
	}

	$args = func_get_args();
	unset($args[0]);

	error_log(vsprintf($msg, $args));
}


/**
 * Simple function for quick and dirty debugging. This prints variable in nice 
 * and readable way. Do not forget calls of this anywhere in final code.
 */
function debug_dump($var, $label = null, $use_var_dump = false, $top_level_only = false)
{
	if (php_sapi_name() == "cli") {
		if ($label != '') {
			echo $label, ': ';
		}
		if ($use_var_dump) {
			var_dump($var);
		} else {
			var_export($var);
		}
		echo "\n";
		return;
	}

	$div = $use_var_dump ? 'pre':'div';

	echo "<$div style='",
		"display: block;",
		"text-align: left;",
		"font-size: 8pt;",
		"margin: 1em; padding: 1ex 1em;",
		"border: 1px solid #aaa; border-width: 2px 1px;",
		"background: #fff; color: #000;",
		"'>";

	if ($label !== null) {
		echo "<big><b>", htmlspecialchars($label), "</b></big> = ";
	}

	if ($top_level_only && (is_object($var) || is_array($var))) {
		echo "<pre>";
		foreach ($var as $k => $v) {
			echo "    ", htmlspecialchars(var_export($k, true)), " => ";
			if (is_object($v)) {
				echo htmlspecialchars(get_class($v));
			} else if (is_array($v)) {
				echo 'array(', count($v), ' items)';
			} else {
				echo htmlspecialchars(var_export($v, true));
			}
			echo ",\n";
		}
		echo "</pre>\n";
	} else {
		if ($use_var_dump) {
			ob_start();
			var_dump($var);
			echo htmlspecialchars(ob_get_clean());
		} else {
			echo preg_replace('|^([^/]*)&lt;\?php<br />|Um', '\1', highlight_string("<?php\n".preg_replace('/=> \n\s*/', "=> ", var_export($var, true)), true));
		}
	}

	// Location line
	echo "<div style='",
		"display: block;",
		"margin: 1ex -1em -1ex -1em;",
		"padding: 0.5ex 1em;",
		"background: #eee;",
		"color: #666;",
		"border-top: 1px solid #aaa;",
		"'>";
	list ($callee, $caller, ) = debug_backtrace();
	if (isset($caller['class'])) {
		echo @htmlspecialchars($caller['class']), "::";
	}
	echo htmlspecialchars($caller['function']);
	echo "(", join(', ', array_map(function($a) {
			if (is_object($a)) {
				return get_class($a);
			} else if (is_array($a)) {
				return 'array['.count($a).']';
			} else {
				return var_export($a, true);
			}
		}, $caller['args'])), "), ";
	echo "<span style=\"white-space: nowrap;\">",
		htmlspecialchars(defined('DIR_ROOT') ? str_replace(DIR_ROOT, '', $callee['file']) : $callee['file']),
		", line ", htmlspecialchars($callee['line']),
		"</span>";
	echo "</div>";
	echo "</$div>";

	return $var;
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


/**
 * Returns decimal part of float.
 *
 * frac(+12.34) == 0.34
 * frac(-12.34) == 0.34
 */
function frac($number)
{
	return $number >= 0 ? $number - (int) $number : - $number + (int) $number;
}


/**
 * Returns decimal part as string without leading '0.'.
 */
function frac_str($number)
{
	return substr(sprintf('%F', frac($number)), 2);
}


function template_format($template, $values, $escaping_function = 'htmlspecialchars')
{
	$available_functions = array(
		'sprintf'	=> 'sprintf',
		'strftime'	=> function($fmt, $t) { return strftime($fmt, strtotime($t)); },
		'floor'		=> 'sprintf',
		'ceil'		=> 'sprintf',
		'frac'		=> 'sprintf',
		'frac_str'	=> 'sprintf',
		'intval'	=> 'sprintf',
		'floatval'	=> 'sprintf',
	);

	$tokens = preg_split('/(?:({)'
				."(\\/?[a-zA-Z0-9_.-]+)"			// symbol name
				.'(?:'
					.'([:%])([^:}\s]*)'			// function name
					."(?:([:])((?:[^}\\\\]|\\\\.)*))?"	// format string
				.')?'
				.'(})'
				.'|(\\\\[{}\\\\]))/',
			$template, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

	$status = 0;		// Current status of parser
	$append = 0;		// Append value to result after token is processed ?
	$result = array();
	$process_function = null;
	$format_function = null;

	$raw_values = array_slice(func_get_args(), 3);

	foreach($tokens as $token) {
		switch ($status) {
			// text around
			case 0:
				if ($token === '{') {
					$status = 10;
					$process_function = null;
					$format_function  = null;
					$fmt = null;
				} else if ($token[0] === '\\') {
					$result[] = substr($token, 1);
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
					$process_function = null;
					$format_function  = 'sprintf';
					$status = 51;
				} else if ($token === ':') {
					$status = 30;
				} else {
					return FALSE;
				}
				break;

			// format function
			case 30:
				if (isset($available_functions[$token])) {
					$process_function = ($token != $available_functions[$token] ? $token : null);
					$format_function  = $available_functions[$token];
				} else {
					$process_function = null;
					$format_function  = null;
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
			$raw = null;

			// get value
			foreach ($raw_values as $rv) {
				if (isset($rv[$key])) {
					$v = $rv[$key];
					$raw = true;
					break;
				}
			}
			if ($raw === null) {
				if (isset($values[$key])) {
					$v = $values[$key];
					$raw = false;
				} else {
					// key not found, do not append it
					$result[] = '{?'.$key.'?}';
					continue;
				}
			}

			// apply $process_function
			if ($process_function !== null) {
				$v = $process_function($v);
			}

			// apply $format_function
			if ($format_function !== null && $fmt !== null) {
				$v = $format_function($fmt, $v);
			}

			// apply $escaping_function
			if ($escaping_function && !$raw) {
				$v = $escaping_function($v);
			}

			$result[] = $v;
		}
	}
	return join('', $result);
}


function filename_format($template, $values) {
	static $constants = false;
	if ($constants === false) {
		// Fixme: How to invalidate this cache?
		$constants = get_defined_constants();
	}

	$args = func_get_args();
	array_splice($args, 2, 0, array(null, $constants));

	//return template_format($template, $values, null, $constants);
	return call_user_func_array('template_format', $args);
}


function write_ini_file_row($f, $k, $v, $quotes) {
	fwrite($f, $k);
	fwrite($f, ' = ');

	if (is_null($v)) {
		fwrite($f, 'null');
	} else if (is_bool($v)) {
		fwrite($f, $v ? 'true':'false');
	} else if (is_int($v)) {
		fwrite($f, $v);
	} else if ($quotes) {
		fwrite($f, '"');
		fwrite($f, $v);
		fwrite($f, '"');
	} else {
		fwrite($f, $v);
	}

	fwrite($f, "\n");
}


function write_ini_file($filename, $array, $sections = FALSE,
			$header = ";\074?php exit(); __HALT_COMPILER; ?\076\n",
			$footer = "; vim\072filetype=dosini:",
			$quotes = true)
{
	$f = fopen($filename, 'w');
	if ($f === FALSE) {
		return FALSE;
	}

	if (!flock($f, LOCK_EX)) {
		return FALSE;
	}

	if ($header != '') {
		fwrite($f, $header);
		fwrite($f, "\n");
	}

	if ($sections) {
		foreach($array as $section => $section_content) {
			fwrite($f, "\n[");
			fwrite($f, $section);
			fwrite($f, "]\n");
			foreach($section_content as $k => $v) {
				if (is_array($v)) {
					foreach ($v as $vk => $vv)
					write_ini_file_row($f, $k.'[]', $vv, $quotes);
				} else {
					write_ini_file_row($f, $k, $v, $quotes);
				}
			}
		}
	} else {
		foreach($array as $k => $v) {
			if (is_array($v)) {
				foreach ($v as $vk => $vv)
				write_ini_file_row($f, $k.'[]', $vv, $quotes);
			} else {
				write_ini_file_row($f, $k, $v, $quotes);
			}
		}
	}

	fwrite($f, "\n");
	if ($footer !== FALSE) {
		fwrite($f, "\n");
		fwrite($f, $footer);
		fwrite($f, "\n");
	}

	flock($f, LOCK_UN);
	return fclose($f);
}


/**
 * Encode array to JSON using json_encode, but insert PHP snippet to protect 
 * sensitive data.
 *
 * If $filename is set, JSON will be written to given file. Otherwise you are 
 * expected to store returned string into *.json.php file. 
 *
 * Stop snippet: When JSON file is evaluated as PHP, stop snippet will 
 * interrupt evaluation without breaking JSON syntax, only underscore 
 * key is appended (and overwritten if exists).
 *
 * To make sure that whitelisted keys does not contain PHP tags, all 
 * occurrences of '<?' are replaced with '<_?' in whitelisted values.
 *
 * Default $json_options are:
 *  - PHP >= 5.4: JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
 *  - PHP <  5.4: JSON_NUMERIC_CHECK
 *
 * Options JSON_HEX_TAG and JSON_HEX_APOS are disabled, becouse they break 
 * PHP snippet.
 */
function write_json_file($filename, $json_array, array $whitelist = null, $json_options = null)
{
        $stop_snippet = "<?php printf('_%c%c}%c',34,10,10);__halt_compiler();?>";

        if ($whitelist === null) {
                // Put stop snippet on begin.
                $result = array_merge(array('_' => null), $json_array);
        } else {
                // Whitelisted keys first (if they exist in $json_array), then stop snippet, then rest.
                $header = array_intersect_key(array_flip($whitelist), $json_array);
                $header['_'] = null;
                $result = array_merge($header, $json_array);
                // Replace '<?' with '<_?' in all whitelisted values, so injected PHP will not execute.
                foreach ($whitelist as $k) {
                        if (array_key_exists($k, $result) && is_string($result[$k])) {
                                $result[$k] = str_replace('<?', '<_?', $result[$k]);
                        }
                }
        }

        // Put stop snipped at marked position (it is here to prevent 
        // overwriting from $json_array).
        $result['_'] = $stop_snippet;

	$json_str = json_encode($result, $json_options === null
			? (defined('JSON_PRETTY_PRINT')
				? JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
				: JSON_NUMERIC_CHECK)
			: $json_flags & ~(JSON_HEX_TAG | JSON_HEX_APOS));

	if ($filename === null) {
		return $json_str;
	} else {
		return file_put_contents($filename, $json_str);
	}
}


/**
 * JSON version of parse_ini_file().
 *
 * Throws JsonException on error.
 */
function parse_json_file($filename)
{
	$json_str = file_get_contents($filename);
	if ($json_str === FALSE) {
		// FIXME: Use different exception ?
		throw new \Cascade\Core\JsonException("Failed to read file: ".$filename);
	}

	$data = json_decode($json_str, TRUE, 512, JSON_BIGINT_AS_STRING);
	$error = json_last_error();

	if ($error !== JSON_ERROR_NONE) {
		switch ($error) {
			case JSON_ERROR_NONE:           $e = 'No errors'; break;
			case JSON_ERROR_DEPTH:          $e = 'Maximum stack depth exceeded'; break;
			case JSON_ERROR_STATE_MISMATCH: $e = 'Underflow or the modes mismatch'; break;
			case JSON_ERROR_CTRL_CHAR:      $e = 'Unexpected control character found'; break;
			case JSON_ERROR_SYNTAX:         $e = 'Syntax error, malformed JSON'; break;
			case JSON_ERROR_UTF8:           $e = 'Malformed UTF-8 characters, possibly incorrectly encoded'; break;
			default:                        $e = 'Unknown error'; break;
		}
		throw new \Cascade\Core\JsonException($e.': '.json_last_error_msg().' ('.$filename.')', $error);
	}

	return $data;
}


if (!function_exists('json_last_error_msg')) {
/**
 * json_last_error_msg() implementation for PHP < 5.5
 */
function json_last_error_msg()
{
	switch (json_last_error()) {
		default:
			// Other errors are only in PHP >=5.5, where native 
			// implementation of this function is used.
			return null;
		case JSON_ERROR_DEPTH:
			$msg = 'Maximum stack depth exceeded';
			break;
		case JSON_ERROR_STATE_MISMATCH:
			$msg = 'Underflow or the modes mismatch';
			break;
		case JSON_ERROR_CTRL_CHAR:
			$msg = 'Unexpected control character found';
			break;
		case JSON_ERROR_SYNTAX:
			$msg = 'Syntax error, malformed JSON';
			break;
		case JSON_ERROR_UTF8:
			$msg = 'Malformed UTF-8 characters, possibly incorrectly encoded';
			break;
	}
}}


/****************************************************************************
 *
 *	Gettext functions with contexts
 *
 *
 * To extract strings with contexts, use:
 *
 *	xgettext \
 *		-kpgettext:1c,2 \
 *		-kdpgettext:2c,3 \
 *		-kdcpgettext:2c,3 \
 *		-knpgettext:1c,2,3 \
 *		-kdnpgettext:2c,3,4 \
 *		-kdcnpgettext:2c,3,4
 *
 */

if (!function_exists('pgettext')) {
/**
 * Missing gettext function: gettext with context.
 *
 * See http://www.gnu.org/software/gettext/manual/gettext.html#Contexts
 *
 * Thanks to https://bugs.php.net/bug.php?id=51285
 */
function pgettext($context, $message)
{
	$actual_message = $context . "\04" . $message;
	return gettext($actual_message);
}}


if (!function_exists('dpgettext')) {
/**
 * Missing gettext function: dgettext with context.
 *
 * See http://www.gnu.org/software/gettext/manual/gettext.html#Contexts
 *
 * Thanks to https://bugs.php.net/bug.php?id=51285
 */
function dpgettext($domain, $context, $message)
{
	$actual_message = $context . "\04" . $message;
	return dgettext($domain, $actual_message);
}}


if (!function_exists('dcpgettext')) {
/**
 * Missing gettext function: dpgettext with context.
 *
 * See http://www.gnu.org/software/gettext/manual/gettext.html#Contexts
 *
 * Thanks to https://bugs.php.net/bug.php?id=51285
 */
function dcpgettext($domain, $context, $message, $category)
{
	$actual_message = $context . "\04" . $message;
	return dcgettext($domain, $actual_message, $category);
}}


if (!function_exists('npgettext')) {
/**
 * Missing gettext function: npgettext with context.
 *
 * See http://www.gnu.org/software/gettext/manual/gettext.html#Contexts
 *
 * Thanks to https://bugs.php.net/bug.php?id=51285
 */
function npgettext($context, $msgid1, $msgid2, $n)
{
	$actual_msgid1 = $context . "\04" . $msgid1;
	$actual_msgid2 = $context . "\04" . $msgid2;
	return ngettext($actual_msgid1, $actual_msgid2, $n);
}}


if (!function_exists('dnpgettext')) {
/**
 * Missing gettext function: dngettext with context.
 *
 * See http://www.gnu.org/software/gettext/manual/gettext.html#Contexts
 *
 * Thanks to https://bugs.php.net/bug.php?id=51285
 */
function dnpgettext($domain, $context, $msgid1, $msgid2, $n)
{
	$actual_msgid1 = $context . "\04" . $msgid1;
	$actual_msgid2 = $context . "\04" . $msgid2;
	return dngettext($domain, $actual_msgid1, $actual_msgid2, $n);
}}


if (!function_exists('dcnpgettext')) {
/**
 * Missing gettext function: dcngettext with context.
 *
 * See http://www.gnu.org/software/gettext/manual/gettext.html#Contexts
 *
 * Thanks to https://bugs.php.net/bug.php?id=51285
 */
function dcnpgettext($domain, $context, $msgid1, $msgid2, $n, $category)
{
	$actual_msgid1 = $context . "\04" . $msgid1;
	$actual_msgid2 = $context . "\04" . $msgid2;
	return dcngettext($domain, $actual_msgid1, $actual_msgid2, $n, $category);
}}

