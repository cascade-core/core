<?php
/*
 * Copyright (c) 2011, Josef Kufner  <jk@frozen-doe.net>
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
 * Load and show block documentation stored in its own source code.
 *
 */

// TODO: Use block storage to retrive informations.

class B_core__devel__doc__show extends \Cascade\Core\Block
{
	const force_exec = true;

	protected $inputs = array(
		'block' => array(),			// Name of the block to describe.
		'show_code' => false,			// Show full source code of the block?
		'require_description' => false,		// Fail if there is no description.
		'heading_level' => 2,			// Level of the first heading.
		'slot' => 'default',
		'slot_weight' => 50,
	);

	protected $outputs = array(
		'title' => true,			// Page title
		'graphviz_cfg' => true,
		'done' => true,
	);

	private $tokens = null;
	private $data = array();
	private $expected_class = null;

	public function main()
	{
		$block = $this->in('block');
		if (is_array($block)) {
			$block = join('/', $block);
		}
		$block = str_replace('-', '_', $block);
		$this->expected_class = 'B_'.str_replace('/', '__', $block);

		// PHP file
		$filename = get_block_filename($block);
		debug_msg("%s: Loading block %s from file %s", $this->fullId(), $block, $filename);

		if (is_readable($filename)) {

			$code = file_get_contents($filename);
			$this->tokens = token_get_all($code);

			$this->start();

			//NDebugger::barDump($this->data, 'Loaded data');
			//NDebugger::barDump(array_map(function($t) { if (is_array($t)) { $t[0] = token_name($t[0]); } return $t; }, $this->tokens), 'Tokens');

			if ($this->in('require_description') && $this->data['description'] == '') {
				return;
			}

			$this->templateAdd(null, 'core/doc/show', array(
					'block' => $block,
					'filename' => $filename,
					'heading_level' => $this->in('heading_level'),
					'class_header' => $this->data['class_header'],
					'force_exec' => $this->data['force_exec'],
					'inputs' => $this->data['inputs'],
					'outputs' => $this->data['outputs'],
					'description' => $this->data['description'],
					'code' => $this->in('show_code') ? $code : null,
					'is_local' => in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1', 'localhost')),
				));

			$this->out('title', $block);
			$this->out('done', true);
			unset($this->tokens);
			unset($this->data);

			return;
		}

		// INI file
		$filename = get_block_filename($block, '.ini.php');
		debug_msg("%s: Loading block %s from file %s", $this->fullId(), $block, $filename);

		if (is_readable($filename)) {
			$this->templateAdd(null, 'core/doc/show', array(
					'block' => $block,
					'filename' => $filename,
					'heading_level' => $this->in('heading_level'),
					'description' => _('Block is composed of blocks as shown on following diagram. Note that diagram '
							.'represents cascade before its execution, not contents of the INI file.'),
					'is_local' => in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1', 'localhost')),
				));

			$this->cascadeAdd('load', 'core/ini/load', null, array(
					'filename' => $filename,
				));
			$this->cascadeAdd('show_image', 'core/devel/preview', null, array(
					'blocks' => array('load', 'data'),
					'slot' => $this->in('slot'),
					'slot_weight' => $this->in('slot_weight'),
				));
			$this->outForward('done', 'load', 'done');
			return;
		}

		// JSON file
		$filename = get_block_filename($block, '.json.php');
		debug_msg("%s: Loading block %s from file %s", $this->fullId(), $block, $filename);

		if (is_readable($filename)) {
			$this->templateAdd(null, 'core/doc/show', array(
					'block' => $block,
					'filename' => $filename,
					'heading_level' => $this->in('heading_level'),
					'description' => _('Block is composed of blocks as shown on following diagram. Note that diagram '
							.'represents cascade before its execution, not contents of the INI file.'),
					'is_local' => in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1', 'localhost')),
				));

			$this->cascadeAdd('load', 'core/json/load', null, array(
					'filename' => $filename,
				));
			$this->cascadeAdd('show_image', 'core/devel/preview', null, array(
					'blocks' => array('load', 'data'),
					'slot' => $this->in('slot'),
					'slot_weight' => $this->in('slot_weight'),
				));
			$this->outForward('done', 'load', 'done');
			return;
		}

		// Nothing found
		error_msg("%s: Can't read file %s", $this->fullId(), $filename);
		$this->out('done', false);
	}


	private function start()
	{
		reset($this->tokens);

		while (($t = current($this->tokens))) {
			next($this->tokens);

			if ($t[0] == T_OPEN_TAG) {
				$this->outterCode();
			}
		}
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
				$this->data[$array_name][] = array(
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

