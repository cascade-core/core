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
 * Merge arrays from all inputs using array_merge() function.
 *
 * See also core/prefix/merge.
 */
class B_core__value__merge extends \Cascade\Core\Block {

	protected $inputs = array(
		'*' => null,
	);

	protected $outputs = array(
		'out' => true,
		'done' => true,
	);

	public function main()
	{
		$out = array();

		foreach ($this->inputNames() as $in) {
			$out = array_merge($out, (array) $this->in($in));
		}

		$this->out('out', $out);
		$this->out('done', true);
	}
}

