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
 * Extract specified or all values from array and set them to outputs. Keys are
 * used as output names.
 */
class B_core__value__split extends Block {

	protected $inputs = array(
		'in' => null,
		'keys' => null,
	);

	protected $outputs = array(
		'done' => true,
		'*' => true,
	);

	public function main()
	{
		$keys = $this->in('keys');
		if ($keys !== null) {
			if (!is_array($keys)) {
				$keys = explode(':', $keys);
			}
			$this->outAll((array) array_extract_keys($this->in('in'), $keys));
		} else {
			$this->outAll((array) $this->in('in'));
		}

		$this->out('done', true);
	}
}

