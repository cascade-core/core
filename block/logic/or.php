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
 * Do logical OR of all inputs. Output is first non-zero value.
 */
class B_core__logic__or extends Block {

	protected $inputs = array(
		'*' => null,
	);

	protected $outputs = array(
		'out' => true,	// Result (first non-zero value).
		'not' => true,	// Negated result (true or false).
	);

	public function main()
	{
		$y = false;

		foreach ($this->inputNames() as $i) {
			$v = $this->in($i);
			if ($v) {
				$y = $v;
				break;
			}
		}

		$this->out('out', $y);
		$this->out('not', !$y);
	}
}

