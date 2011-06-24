<?php
/*
 * Copyright (c) 2011, Josef Kufner  <jk@frozen-doe.net>
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
 * Load and show module documentation stored in its own source code.
 *
 */

class M_core__devel__doc__show extends Module {

	protected $inputs = array(
		'module' => array(),
		'show-code' => false,
		'slot' => 'default',
		'slot-weight' => 50,
	);

	protected $outputs = array(
		'title' => true,
		'done' => true,
	);

	private $tokens = null;
	private $data = array();
	private $expected_class = null;

	public function main()
	{
		$module = $this->in('module');
		if (is_array($module)) {
			$module = join('/', $module);
		}
		$this->expected_class = 'M_'.str_replace('/', '__', $module);

		$filename = get_module_filename($module);

		debug_msg("%s: Loading module %s from file %s", $this->full_id(), $module, $filename);

		if (is_readable($filename)) {

			$code = file_get_contents($filename);
			$this->tokens = token_get_all($code);

			$this->start();

			NDebug::barDump($this->data, 'Loaded data');
			NDebug::barDump(array_map(function($t) { if (is_array($t)) { $t[0] = token_name($t[0]); } return $t; }, $this->tokens), 'Tokens');

			$this->template_add(null, 'core/doc/show', array(
					'module' => $module,
					'filename' => $filename,
					'class_header' => $this->data['class_header'],
					'inputs' => $this->data['inputs'],
					'outputs' => $this->data['outputs'],
					'description' => $this->data['description'],
					'code' => $this->in('show-code') ? $code : null,
					'is_local' => in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1', 'localhost')),
				));

			$this->out('title', $module);
			$this->out('done', true);
		} else {
			error_msg("%s: Can't read file %s", $this->full_id(), $filename);
			$this->out('done', false);
		}

		unset($this->tokens);
		unset($this->data);
	}


	private function start()
	{
		reset($this->tokens);

		while (($t = current($this->tokens))) {
			next($this->tokens);

			if ($t[0] == T_OPEN_TAG) {
				$this->outter_code();
			}
		}
	}


	private function outter_code()
	{
		while (($t = current($this->tokens))) {
			next($this->tokens);
			switch ($t[0]) {
				case T_CLOSE_TAG:
					return;

				case T_DOC_COMMENT:
					$this->data['description'][] = $this->strip_doc_comment($t[1]);
					break;

				case T_CLASS:
					// get class name
					while (($t = current($this->tokens))) {
						next($this->tokens);
						if ($t[0] == T_STRING) {
							$class_name = $t[1];
							break;
						}
					}

					if ($class_name != $this->expected_class) {
						$this->skip_class();
					} else {
						$this->read_class_header();
					}
					break;
			}
		}
	}


	private function skip_class()
	{
		$depth = 0;

		while (($t = current($this->tokens))) {
			next($this->tokens);

			if ($t == '{') {
				$depth++;
			} else if ($t == '}') {
				$depth--;
				if ($depth == 0) {
					return;
				}
			}
		}
	}


	private function read_class_header()
	{
		$str = 'class '.$this->expected_class;

		while (($t = current($this->tokens))) {
			next($this->tokens);

			if ($t == '{') {
				$this->read_class();
				break;
			} else {
				$str .= is_array($t) ? $t[1] : $t;
			}
		}

		$this->data['class_header'] = $str;
	}


	private function read_class()
	{
		$depth = 1;

		while (($t = current($this->tokens))) {
			next($this->tokens);

			if ($t == '{') {
				$depth++;
			} else if ($t == '}') {
				$depth--;
				if ($depth == 0) {
					return;
				}
			} else if ($depth == 1) {
				// root depth
				if (is_array($t) && $t[0] == T_VARIABLE) {
					$var = $t[1];
					if ($var == '$inputs') {
						$this->read_inputs();
					} else if ($var == '$outputs') {
						$this->read_outputs();
					}
				}
			}
		}
	}


	private function read_inputs()
	{
		$str = "\t\$inputs";

		while (($t = current($this->tokens))) {
			next($this->tokens);

			$str .= is_array($t) ? $t[1] : $t;

			if ($t == ';') {
				break;
			}
		}

		$this->data['inputs'] = $str;
	}


	private function read_outputs()
	{
		$str = "\t\$outputs";

		while (($t = current($this->tokens))) {
			next($this->tokens);

			$str .= is_array($t) ? $t[1] : $t;

			if ($t == ';') {
				break;
			}
		}

		$this->data['outputs'] = $str;
	}


	private function strip_doc_comment($comment)
	{
		return trim(preg_replace('/^[\t ]*\* ?/m', '', substr($comment, 3, -2)));
	}
}

