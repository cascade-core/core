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

/**
 * Show error or success message.
 *
 * If redirect is requested, message can be displayed using core/out/message_queue.
 */
class B_core__out__message extends Block
{
	const force_exec = true;

	protected $inputs = array(
		'type' => null,			// Type of message: error, success, warning, info.
		'is_error' => null,		// Mesage is 'error'. Overrides 'type' input.
		'is_success' => null,		// Mesage is 'success'. Overrides 'type' input.
		'is_warning' => null,		// Mesage is 'warning'. Overrides 'type' input.
		'is_info' => null,		// Mesage is 'info'. Overrides 'type' input.

		'title' => null,		// Default message title.
		'text' => null,			// Default message description.

		// override when error
		'error_title' => null,		// Error message title.
		'error_text' => null,		// Error message description.

		// override when warning
		'warning_title' => null,	// Warning message title.
		'warning_text' => null,		// Warning message description.

		// override when success
		'success_title' => null,	// Success message title.
		'success_text' => null,		// Success message description.

		// override when info
		'info_title' => null,		// Info message title.
		'info_text' => null,		// Info message description.

		'redirect_url' => null,		// Redirect to this URI, when type is 'success'.
		'allow_redirect' => true,	// Is redirect allowed?
		'quiet_redirect' => false,	// Do not show message, only do redirect.

		// http-status
		'http_status_code' => null,	// Redirect with this HTTP status code.
		'http_status_message' => null,	// Redirect with this HTTP status message.

		'hide' => false,		// Do not show the message.

		'slot' => 'default',
		'slot_weight' => 20,

		'*' => null,
	);

	protected $outputs = array(
	);


	public function main()
	{
		/* hide */
		if ($this->in('hide')) {
			return;
		}

		/* resolve message type */
		if ($this->in('is_error')) {
			$type = 'error';
		} else if ($this->in('is_success')) {
			$type = 'success';
		} else if ($this->in('is_warning')) {
			$type = 'warning';
		} else if ($this->in('is_info')) {
			$type = 'info';
		} else if (!($type = $this->in('type')) || !in_array($type, array('error', 'success', 'warn', 'info'))) {
			return;
		}

		/* get numeric inputs */
		$in_vals = $this->collect_numeric_inputs();

		/* get title */
		if (($title = (string) $this->in($type.'_title')) == '') {
			$title = $this->in('title');
		}
		if (!empty($in_vals)) {
			$title = vsprintf($title, $in_vals);
		}

		if ($title == '' || !preg_match('/^[a-z][a-z0-9_]*$/', $type)) {
			return;
		}

		/* get text */
		if (($text = (string) $this->in($type.'_text')) == '') {
			$text = $this->in('text');
		}
		if (!empty($in_vals)) {
			$text = vsprintf($text, $in_vals);
		}

		/* http status */
		$http_status_code = (int) $this->in('http_status_code');
		if ($http_status_code) {
			$this->template_option_set('root', 'http_status_code', $http_status_code);
			$http_status_message = $this->in('http_status_message');
			if ($http_status_message) {
				$this->template_option_set('root', 'http_status_message', $http_status_message);
			}
		}

		/* show message */
		$msg_data = array(
			'type' => $type,
			'title' => $title,
			'text' => $text,
		);
		$this->template_add(null, 'core/message', $msg_data);

		/* redirect if success */
		if ($type == 'success' && $this->in('allow_redirect')) {
			$redirect_url = vsprintf($this->in('redirect_url'), $in_vals);
			if ($redirect_url != '') {
				$redirect_anchor = vsprintf($this->in('redirect_anchor'), $in_vals);
				$this->template_option_set('root', 'redirect_url',
					$redirect_anchor ? $redirect_url.'#'.$redirect_anchor : $redirect_url);

				if (!$this->in('quiet_redirect')) {
					debug_msg('Storing message for later use.');
					$_SESSION['message_queue'][] = $msg_data;
				}
			}
		}
	}
}

