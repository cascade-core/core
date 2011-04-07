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

class M_core__out__message extends Module {

	protected $inputs = array(
		'type' => null,
		'is-error' => null,
		'is-success' => null,
		'is-warning' => null,
		'is-info' => null,

		// defaults
		'title' => null,
		'text' => null,

		// override when error
		'error-title' => null,
		'error-text' => null,

		// override when warning
		'warning-title' => null,
		'warning-text' => null,

		// override when success
		'success-title' => null,
		'success-text' => null,

		// override when info
		'info-title' => null,
		'info-text' => null,

		// redirect - only if success
		'redirect-url' => null,
		'allow-redirect' => true,

		'hide' => false,

		'slot' => 'default',
		'slot-weight' => 20,

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
		if ($this->in('is-error')) {
			$type = 'error';
		} else if ($this->in('is-success')) {
			$type = 'success';
		} else if ($this->in('is-warning')) {
			$type = 'warning';
		} else if ($this->in('is-info')) {
			$type = 'info';
		} else if (!($type = $this->in('type')) || !in_array($type, array('error', 'success', 'warn', 'info'))) {
			return;
		}

		/* get numeric inputs */
		$in_vals = $this->collect_numeric_inputs();

		/* get title */
		if (($title = (string) $this->in($type.'-title')) == '') {
			$title = $this->in('title');
		}
		if (!empty($in_vals)) {
			$title = vsprintf($title, $in_vals);
		}

		/* get text */
		if (($text = (string) $this->in($type.'-text')) == '') {
			$text = $this->in('text');
		}
		if (!empty($in_vals)) {
			$text = vsprintf($text, $in_vals);
		}

		/* show message */
		if ($title !== '' && preg_match('/^[a-z][a-z0-9_]*$/', $type)) {
			$msg_data = array(
				'type' => $type,
				'title' => $title,
				'text' => $text,
			);

			$this->template_add(null, 'core/message', $msg_data);

			/* redirect if success */
			if ($type == 'success' && $this->in('allow-redirect')) {
				$redirect_url = vsprintf($this->in('redirect-url'), $in_vals);
				if ($redirect_url != '') {
					$redirect_anchor = vsprintf($this->in('redirect-anchor'), $in_vals);
					$this->template_option_set('root', 'redirect_url',
						$redirect_anchor ? $redirect_url.'#'.$redirect_anchor : $redirect_url);
					$_SESSION['message_queue'][] = $msg_data;
				}
			}
		}
	}
}


// vim:encoding=utf8:

