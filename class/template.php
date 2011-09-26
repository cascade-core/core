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

class Template {

	private $objects = array();
	private $slot_options = array();
	private $current_slot_depth = 0;


	function add_object($id, $slot, $weight, $template, $data = array(), $context = null)
	{
		if (array_key_exists($id, $this->objects)) {
			error_msg('Duplicate ID "%s"!', $id);
			return false;
		} else {
			$this->objects[$id] = array($weight, $slot, $id, $template, $data, $context);
			$this->slot_content[$slot][] = & $this->objects[$id];
			return true;
		}
	}


	// set slot option (no arrays allowed)
	function slot_option_set($slot, $option, $value)
	{
		if (is_array($value)) {
			error_msg('Slot option must not be array!');
		} else {
			debug_msg('Setting slot option "%s" of slot "%s" = "%s"', $option, $slot, $value);
			$this->slot_options[$slot][$option] = $value;
		}
	}


	// append slot option value to list
	function slot_option_append($slot, $option, $value)
	{
		if (!is_array(@$this->slot_options[$slot][$option])) {
			$this->slot_options[$slot][$option] = array();
		}
		if (is_array($value)) {
			$this->slot_options[$slot][$option] += $value;
		} else {
			$this->slot_options[$slot][$option][] = $value;
		}
	}


	function load_template($output_type, $template_name, $function_name, $indent = '')
	{
		$f = get_template_filename($output_type, $template_name);

		if (is_readable($f)) {
			debug_msg('%s Loading "%s"', $indent, substr($f, strlen(DIR_ROOT)));
			include $f;
		} else {
			debug_msg('%s Can\'t load "%s" - file "%s" not found.', $indent, substr($f, strlen(DIR_ROOT)), str_replace(DIR_ROOT, '', $f));
		}
		return function_exists($function_name);
	}


	function process_slot($slot_name)
	{
		static $options = array();
		static $output_type = 'xhtml';

		$indent = str_repeat(' .', $this->current_slot_depth);
		$this->current_slot_depth++;
		$last_options = $options;

		if (!array_key_exists($slot_name, $this->slot_content)) {
			debug_msg(' %s Slot "%s" is empty.', $indent, $slot_name);
		} else if ($this->slot_content[$slot_name] === false) {
			debug_msg(' %s Slot "%s" is already processed.', $indent, $slot_name);
		} else {
			debug_msg(' %s Processing slot "%s" ...', $indent, $slot_name);

			/* get slot content */
			$content = $this->slot_content[$slot_name];
			$this->slot_content[$slot_name] = false;
			sort($content);		// sort by weight (this is why weight is first)

			/* get slot options & merge with parent slot options */
			$options = array_merge($options, (array) @$this->slot_options[$slot_name]);

			/* special option 'type' sets output type */
			if (isset($options['type'])) {
				$new_output_type = trim(preg_replace('/[^a-z0-9_]+/', '_', strtolower($options['type'])), '_');
				if ($new_output_type != '' && $new_output_type != $output_type) {
					debug_msg(' %s Output type changed to "%s" ...', $indent, $new_output_type);
					$output_type = $new_output_type;
				}
			}

			/* process slot content */
			foreach($content as $obj) {
				list($weight, $slot, $id, $template, $data, $context) = $obj;
				
				$tpl_fn = 'TPL_'.$output_type.'__'.str_replace('/', '__', $template);

				if (function_exists($tpl_fn) || $this->load_template($output_type, $template, $tpl_fn, $indent)) {
					debug_msg(' %s Executing "%s" ...', $indent, $template);
					if ($context !== null) {
						$context->update_enviroment();
					}

					/* call template (can recursively call process_slot()) */
					$tpl_fn($this, $id, $data, $options);
				} else {
					error_msg('Failed to load template "%s"! Object ID is "%s".', $template, $id);
				}
			}

			debug_msg(' %s Processing slot "%s" done.', $indent, $slot_name);
		}

		$options = $last_options;
		$this->current_slot_depth--;
	}


	function start($return_output = false)
	{
		/*
		header('Content-Type: text/plain');
		print_r($this);
		return;
		// */

		$redirect_url = @$this->slot_options['root']['redirect_url'];

		/* Show core's name in header */
		header('X-Powered-By: Dynamic Pipeline', TRUE);		// FIXME

		/* Send custom status code & message */
		if ($redirect_url) {
			$code    = @$this->slot_options['root']['redirect_code'];
			$message = @$this->slot_options['root']['redirect_message'];
			$code    = $code >= 300 && $code < 400 ? $code : 301;
		} else {
			$code    = @$this->slot_options['root']['http_status_code'];
			$message = @$this->slot_options['root']['http_status_message'];
			$code    = $code >= 100 && $code < 600 ? $code : 200;
		}
		$message = $message ? $message : $this->get_http_status_message($code);
		header(sprintf('HTTP/1.1 '.$code.' '.$message));

		/* process redirect (no output, headers only) */
		if ($redirect_url) {
			debug_msg('Redirecting to "%s" (%d %s)', $redirect_url, $code, $message);
			header('Location: '.$redirect_url, TRUE, $code != 200 ? $code : 301);
			return;
		}

		/* process root slot */
		ob_start();
		$this->process_slot('root');

		if ($return_output) {
			$out = ob_get_contents();
			ob_end_clean();
		} else {
			ob_end_flush();
		}
	}


	function get_http_status_message($code)
	{
		switch ($code) {
			case 100: return 'Continue';
			case 101: return 'Switching Protocols';
			case 200: return 'OK';
			case 201: return 'Created';
			case 202: return 'Accepted';
			case 203: return 'Non-Authoritative Information';
			case 204: return 'No Content';
			case 205: return 'Reset Content';
			case 206: return 'Partial Content';
			case 300: return 'Multiple Choices';
			case 301: return 'Moved Permanently';
			case 302: return 'Found';
			case 303: return 'See Other';
			case 304: return 'Not Modified';
			case 305: return 'Use Proxy';
			case 307: return 'Temporary Redirect';
			case 400: return 'Bad Request';
			case 401: return 'Unauthorized';
			case 402: return 'Payment Required';
			case 403: return 'Forbidden';
			case 404: return 'Not Found';
			case 405: return 'Method Not Allowed';
			case 406: return 'Not Acceptable';
			case 407: return 'Proxy Authentication Required';
			case 408: return 'Request Timeout';
			case 409: return 'Conflict';
			case 410: return 'Gone';
			case 411: return 'Length Required';
			case 412: return 'Precondition Failed';
			case 413: return 'Request Entity Too Large';
			case 414: return 'Request-URI Too Long';
			case 415: return 'Unsupported Media Type';
			case 416: return 'Requested Range Not Satisfiable';
			case 417: return 'Expectation Failed';
			case 500: return 'Internal Server Error';
			case 501: return 'Not Implemented';
			case 502: return 'Bad Gateway';
			case 503: return 'Service Unavailable';
			case 504: return 'Gateway Timeout';
			case 505: return 'HTTP Version Not Supported';
			default: return 'WTF';
		}
	}
}

