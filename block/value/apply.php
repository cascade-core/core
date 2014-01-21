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
 * Apply function to each input and put results to outputs. When value is array,
 * array_map() function is used.
 */
class B_core__value__apply extends \Cascade\Core\Block {

	protected $inputs = array(
		'function' => null,	// Function to apply.
		'*' => null,
	);

	protected $connections = array(
		'function' => array(),
	);

	protected $outputs = array(
		'done' => true,
		'*' => true,
	);

	public function main()
	{
		$func = $this->in('function');

		if (!function_exists($func)) {
			return;
		}

		foreach ($this->inputNames() as $in) {
			if ($in == 'enable' || $in == 'function') {
				continue;
			}

			$val = $this->in($in);

			if (is_array($val)) {
				$out = array_map($func, $val);
			} else {
				$out = $func($val);
			}

			$this->out($in, $out);
		}

		$this->out('done', true);
	}
}

