<?php
/*
 * Copyright (c) 2014, Josef Kufner  <jk@frozen-doe.net>
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

namespace Cascade\Core;

/**
 * Generates documentation structure of the block from PHP source code. Used by 
 * ClassBlockStorage to describe given block.
 */
class ClassBlockDocumentator
{

	private $tokens = null;
	private $data = array();
	private $filename = null;
	private $expected_class = null;

	/**
	 * Initialize documentator using tokens from given file.
	 */
	public function __construct($filename, $expected_class)
	{
		$this->filename = $filename;
		$this->expected_class = $expected_class;

		$code = file_get_contents($filename);
		if ($code === FALSE) {
			throw new \RuntimeException('Cannot read file: '.$filename);
		}

		$this->tokens = token_get_all($code);
	}


	/**
	 * Analyze the tokens and return description of the block.
	 */
	public function describe()
	{
		reset($this->tokens);
		$this->data = array(
			'filename' => $this->filename,
		);

		while (($t = current($this->tokens))) {
			next($this->tokens);

			if ($t[0] == T_OPEN_TAG) {
				$this->outterCode();
			}
		}

		// Add default connections to input description
		foreach ((array) @ $this->data['connections'] as $conn) {
			$this->data['inputs'][$conn['name']]['connection'] = $conn['value'];
		}

		//debug_dump($this->data);
		return $this->data;
	}


	private function outterCode()
	{
		while (($t = current($this->tokens))) {
			next($this->tokens);
			switch ($t[0]) {
				case T_CLOSE_TAG:
					return;

				case T_DOC_COMMENT:
					$this->data['description'][] = $this->stripDocComment($t[1]);
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
						$this->skipClass();
					} else {
						$this->readClassHeader();
					}
					break;
			}
		}
	}


	private function skipClass()
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


	private function readClassHeader()
	{
		$str = 'class '.$this->expected_class;

		while (($t = current($this->tokens))) {
			next($this->tokens);

			if ($t == '{') {
				$this->readClass();
				break;
			} else {
				$str .= is_array($t) ? $t[1] : $t;
			}
		}

		$this->data['class_header'] = $str;
	}


	private function readClass()
	{
		$depth = 1;

		while (($t = current($this->tokens))) {
			next($this->tokens);

			if ($t == '{' || $t == '(') {
				$depth++;
			} else if ($t == '}' || $t == ')') {
				$depth--;
				if ($depth == 0) {
					return;
				}
			} else if ($depth == 1) {
				// root depth
				if (is_array($t)) {
					if ($t[0] == T_VARIABLE) {
						$var = $t[1];
						if ($var == '$inputs') {
							$this->readArray('inputs');
						} else if ($var == '$connections') {
							$this->readArray('connections');
						} else if ($var == '$outputs') {
							$this->readArray('outputs');
						}
					} else if ($t[0] == T_STRING) {
						if ($t[1] == 'force_exec') {
							$this->readForceExec();
						}
					}
				}
			}
		}
	}


	private function readForceExec()
	{
		$str = "\tconst force_exec";

		while (($t = current($this->tokens))) {
			next($this->tokens);

			$str .= is_array($t) ? $t[1] : $t;

			if ($t == ';') {
				break;
			}
		}

		$this->data['force_exec'] = $str;
	}

	private function readArray($array_name)
	{
		$inputs = array();

		// wait for '='
		if (!$this->readArrayWaitFor('=')) {
			return;
		}

		// wait for 'array'
		if (!$this->readArrayWaitFor(array(T_ARRAY))) {
			return;
		}

		// wait for '('
		if (!$this->readArrayWaitFor('(')) {
			return;
		}

		// read inputs
		while (($t = current($this->tokens)) && $t != ';') {
			$input_comments = array();

			// read key
			$input_name = $this->readValue();

			// get '=>'
			$this->readArrayWaitFor(array(T_DOUBLE_ARROW));

			// read value
			$input_value = $this->readValue($input_comments);

			if (current($this->tokens) == ',') {
				next($this->tokens);
			}

			// read remaining comments
			$this->readComments($input_comments);

			// store gathered data
			if ($input_name !== '') {
				if (preg_match('/^\'[^\']*\'$/', $input_name)) {
					$input_name = trim($input_name, "'");
				} else if (preg_match('/^"[^"]*"$/', $input_name)) {
					$input_name = trim($input_name, '"');
				}
				$this->data[$array_name][$input_name] = array(
					'name' => $input_name,
					'value' => $input_value,
					'comment' => $input_comments,
				);
			}
		}

		// finaly wait for ';'
		$this->readArrayWaitFor(';');
	}


	private function readComments(& $comments)
	{
		while (($t = current($this->tokens))) {
			if (is_array($t)) {
				if ($t[0] == T_DOC_COMMENT) {
					$comments[] = $this->stripDocComment($t[1]);
				} else if ($t[0] == T_COMMENT) {
					$comments[] = $this->stripComment($t[1]);
				} else if ($t[0] != T_WHITESPACE) {
					return;
				}
			} else {
				return;
			}
			next($this->tokens);
		}
	}


	private function readValue(& $comments = null)
	{
		$str = '';
		$depth = 0;

		while (($t = current($this->tokens))) {
			if ($depth == 0) {
				if (is_array($t) && ($t[0] == T_DOUBLE_ARROW)) {
					break;
				} else if ($t == ';' || $t == ',' || $t == ')' || $t == ']' || $t == '}') {
					break;
				}
			}

			next($this->tokens);

			if ($t == '{' || $t == '(' || $t == '[') {
				$depth++;
			} else if ($t == '}' || $t == ')' || $t == ']') {
				$depth--;
			}

			if (is_array($t)) {
				if ($t[0] == T_DOC_COMMENT) {
					$comments[] = $this->stripDocComment($t[1]);
				} else if ($t[0] == T_COMMENT) {
					$comments[] = $this->stripComment($t[1]);
				} else if ($t[0] == T_WHITESPACE) {
					$str .= ' ';
				} else {
					$str .= $t[1];
				}
			} else {
				$str .= $t;
			}
		}

		return trim($str);
	}


	private function readArrayWaitFor($that)
	{
		if (is_array($that)) {
			while (($t = current($this->tokens))) {
				if ($t == ';') {
					return false;
				}
				next($this->tokens);
				if (is_array($t) && $t[0] == $that[0]) {
					return true;
				}
			}
		} else {
			while (($t = current($this->tokens))) {
				if ($t == ';') {
					return false;
				}
				next($this->tokens);
				if ($t == $that) {
					return true;
				}
			}
		}
		return false;
	}


	private function readOutputs()
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


	private function stripComment($comment)
	{
		$begin = substr($comment, 0, 2);

		if ($begin == '//') {
			return trim(substr($comment, 2));
		} else if ($begin == '/*') {
			return trim(preg_replace('/^[\t ]*\* ?/m', '', substr($comment, 2, -2)));
		}
	}

	private function stripDocComment($comment)
	{
		return trim(preg_replace('/^[\t ]*\* ?/m', '', substr($comment, 3, -2)));
	}

}

