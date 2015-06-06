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

/**
 * Show error or success message.
 *
 * If redirect is requested, message can be displayed using core/out/message_queue.
 */
class B_core__out__message extends \Cascade\Core\Block
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
		$in_vals = $this->collectNumericInputs();

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
		if (($text = $this->in($type.'_text')) == '') {
			$text = $this->in('text');
		}
		if (!empty($in_vals)) {
			if (is_array($text)) {
				foreach ($text as & $t) {
					$t = vsprintf($t, $in_vals);
				}
			} else {
				$text = vsprintf($text, $in_vals);
			}
		}

		/* http status */
		$http_status_code = (int) $this->in('http_status_code');
		if ($http_status_code) {
			$this->templateOptionSet('root', 'http_status_code', $http_status_code);
			$http_status_message = $this->in('http_status_message');
			if ($http_status_message) {
				$this->templateOptionSet('root', 'http_status_message', $http_status_message);
			}
		}

		/* show message */
		$msg_data = array(
			'type' => $type,
			'title' => $title,
			'text' => $text,
		);
		$this->templateAdd(null, 'core/message', $msg_data);

		/* redirect if success */
		if ($type == 'success' && $this->in('allow_redirect')) {
			$redirect_url = vsprintf($this->in('redirect_url'), $in_vals);
			if ($redirect_url != '') {
				$redirect_anchor = vsprintf($this->in('redirect_anchor'), $in_vals);
				$this->templateOptionSet('root', 'redirect_url',
					$redirect_anchor ? $redirect_url.'#'.$redirect_anchor : $redirect_url);

				if (!$this->in('quiet_redirect')) {
					debug_msg('Storing message for later use.');
					if (!isset($_SESSION)) {
						session_start();
					}
					$_SESSION['message_queue'][] = $msg_data;
				}
			}
		}
	}
}

