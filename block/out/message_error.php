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
 * Simple error message.
 */
class B_core__out__message_error extends \Cascade\Core\Block
{
	const force_exec = true;

	protected $inputs = array(
		// override when error
		'title' => null,		// Message title.
		'text' => null,			// Message description.

		// http-status
		'http_status_code' => 400,	// HTTP status code used when this is an error message

		'slot' => 'default',
		'slot_weight' => 20,

		'*' => null,
	);

	protected $outputs = array(
	);


	public function main()
	{
		$inputs = $this->inAll();

		// Get title
		$title = filename_format((string) $this->in('title'));
		$quiet_redirect = ($title == '');

		// Get text
		$text = (string) $this->in('text');
		if (is_array($text)) {
			$text = array_map(function($text) use ($inputs) { return filename_format($text, $inputs); }, $text);
		} else {
			$text = filename_format($text, $inputs);
		}

		// Set HTTP status
		$error_http_status_code = (int) $this->in('error_http_status_code');
		if ($error_http_status_code) {
			$this->templateOptionSet('root', 'http_status_code', $error_http_status_code);
			$this->templateOptionSet('root', 'http_status_message', $title);
		}

		// Show message
		$msg_data = array(
			'type' => 'error',
			'title' => $title,
			'text' => $text,
		);
		$this->templateAdd(null, 'core/message', $msg_data);
	}
}

