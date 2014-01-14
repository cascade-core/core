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
 * Do logical AND of all inputs.
 */
class B_core__logic__and extends Block {

	protected $inputs = array(
		'*' => null,
	);

	protected $outputs = array(
		'out' => true,	// Result (true or false).
		'not' => true,	// Negated result (true or false).
	);

	public function main()
	{
		$y = true;

		foreach ($this->inputNames() as $i) {
			$v = $this->in($i);
			if (!$v) {
				$y = false;
				break;
			}
		}

		$this->out('out', $y);
		$this->out('not', !$y);
	}
}

