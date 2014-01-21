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
 * Split one array to many outputs using prefix as output name. This is reverse
 * operation to core/prefix/merge.
 */
class B_core__prefix__split extends \Cascade\Core\Block {

	protected $inputs = array(
		'in' => null,		// Merged array.
	);

	protected $connections = array(
		'in' => array(),
	);

	protected $outputs = array(
		'*' => true,
		'done' => true,
	);

	public function main()
	{
		$out = array();

		foreach ($this->in('in') as $in => $value) {
			list($prefix, $orig_in) = explode('_', $in, 2);
			$out[$prefix][$orig_in] = $value;
		}

		$this->outAll($out);
		$this->out('done', true);
	}

}

