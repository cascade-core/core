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

class B_core__out__message extends Block
{
	const force_exec = true;

	protected $inputs = array(
		'type' => null,
		'is_error' => null,
		'is_success' => null,
		'is_warning' => null,
		'is_info' => null,

		// defaults
		'title' => null,
		'text' => null,

		// override when error
		'error_title' => null,
		'error_text' => null,

		// override when warning
		'warning_title' => null,
		'warning_text' => null,

		// override when success
		'success_title' => null,
		'success_text' => null,

		// override when info
		'info_title' => null,
		'info_text' => null,

		// redirect - only if success
		'redirect_url' => null,
		'allow_redirect' => true,
		'quiet_redirect' => false,

		// http-status
		'http_status_code' => null,
		'http_status_message' => null,

		'hide' => false,

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
					$_SESSION['message_queue'][] = $msg_data;
				}
			}
		}
	}
}

