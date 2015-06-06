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
 * Simple success message.
 */
class B_core__out__message_success extends \Cascade\Core\Block
{
	const force_exec = true;

	protected $inputs = array(
		// override when success
		'title' => null,			// Success message title.
		'text' => null,				// Success message description.

		// redirect
		'redirect_url' => null,			// Redirect to this URI, when type is 'success'.
		'redirect_anchor' => null,		// Anchor part of the redirect_url.
		'redirect_enabled' => true,		// Redirect is enabled.

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
		$title = filename_format((string) $this->in('title'), $inputs);
		$quiet_redirect = ($title == '');

		// Get text
		$text = $this->in('text');
		if (is_array($text)) {
			$text = array_map(function($text) use ($inputs) { return filename_format($text, $inputs); }, $text);
		} else {
			$text = filename_format($text, $inputs);
		}

		// Show message
		$msg_data = array(
			'type' => 'success',
			'title' => $title,
			'text' => $text,
		);
		$this->templateAdd(null, 'core/message', $msg_data);

		// Redirect after post
		$redirect_url = filename_format($this->in('redirect_url'), $inputs);
		if ($this->in('redirect_enabled') && $redirect_url != '') {
			$redirect_anchor = filename_format($this->in('redirect_anchor'), $inputs);
			$this->templateOptionSet('root', 'redirect_url',
				$redirect_anchor ? $redirect_url.'#'.$redirect_anchor : $redirect_url);

			if (!$quiet_redirect) {
				debug_msg('Storing message for later use.');
				if (!isset($_SESSION)) {
					session_start();
				}
				$_SESSION['message_queue'][] = $msg_data;
			}
		}
	}
}

