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
 * Iterate over provided array. Useful for testing.
 */

class B_core__stream__walk extends \Cascade\Core\Block {

	protected $inputs = array(
		'array' => null,
		'key_key' => false,
		'done' => false,
	);

	protected $connections = array(
		'array' => array(),
	);

	protected $outputs = array(
		'iter' => true,
	);

	private $array;
	private $key_key;

	public function main()
	{
		$this->array = $this->in('array');
		$this->key_key = $this->in('key_key');

		$this->out('iter', array($this, 'get_next'));

		if (is_array($this->array)) {
			$this->out('done', true);
		} else {
			error_msg('Input array is not an array!');
			$this->array = array();
			$this->out('done', false);
		}

		reset($this->array);
	}


	public function get_next()
	{
		$n = each($this->array);

		if ($n === FALSE) {
			return null;
		}

		if ($this->key_key !== FALSE && is_array($n[1])) {
			$n[1][$this->key_key] = $n[0];
		}

		return $n[1];
	}
}

