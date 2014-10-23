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
 * Simplified error or success message.
 *
 * If redirect is requested, message can be displayed using core/out/message_queue.
 *
 */
class B_core__out__message_simple extends \Cascade\Core\Block
{
	const force_exec = true;

	protected $inputs = array(
		'is_error' => null,			// Mesage is 'error'.
		'is_success' => null,			// Mesage is 'success'.

		// override when error
		'error_title' => null,			// Error message title.
		'error_text' => null,			// Error message description.

		// override when success
		'success_title' => null,		// Success message title.
		'success_text' => null,			// Success message description.

		// redirect
		'redirect_url' => null,			// Redirect to this URI, when type is 'success'.
		'redirect_anchor' => null,		// Anchor part of the redirect_url.

		// http-status
		'error_http_status_code' => 400,	// HTTP status code used when this is an error message

		'slot' => 'default',
		'slot_weight' => 20,

		'*' => null,
	);

	protected $outputs = array(
	);


	public function main()
	{
		$inputs = $this->inAll();

		// Resolve message type (success by default)
		if ($this->in('is_success')) {
			$type = 'success';
		} else if ($this->in('is_error')) {
			$type = 'error';
		} else {
			$type = 'success';
		}

		// Get title
		$title = filename_format((string) $this->in($type.'_title'), $inputs);
		$quiet_redirect = ($title == '');

		// Get text
		$text = (string) $this->in($type.'_text');
		if (is_array($text)) {
			$text = array_map(function($text) use ($inputs) { return filename_format($text, $inputs); }, $text);
		} else {
			$text = filename_format($text, $inputs);
		}

		// Set HTTP status
		if ($type == 'error') {
			$error_http_status_code = (int) $this->in('error_http_status_code');
			if ($error_http_status_code) {
				$this->templateOptionSet('root', 'http_status_code', $error_http_status_code);
				$this->templateOptionSet('root', 'http_status_message', $title);
			}
		}

		// Show message
		$msg_data = array(
			'type' => $type,
			'title' => $title,
			'text' => $text,
		);
		$this->templateAdd(null, 'core/message', $msg_data);

		// Redirect if success
		if ($type == 'success') {
			$redirect_url = filename_format($this->in('redirect_url'), $inputs);
			if ($redirect_url != '') {
				$redirect_anchor = filename_format($this->in('redirect_anchor'), $inputs);
				$this->templateOptionSet('root', 'redirect_url',
					$redirect_anchor ? $redirect_url.'#'.$redirect_anchor : $redirect_url);

				if (!$quiet_redirect) {
					debug_msg('Storing message for later use.');
					$_SESSION['message_queue'][] = $msg_data;
				}
			}
		}
	}
}

