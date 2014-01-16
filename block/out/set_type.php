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
 * Set output type using root slot option. It is useful to connect 'type' input
 * to multiple blocks using :or function.
 */

class B_core__out__set_type extends \Cascade\Core\Block
{
	const force_exec = true;

	protected $inputs = array(
		'type' => null,			// Output type.
		'type_fallback' => null,	// Alternative output type when 'type' input is empty.
	);

	protected $outputs = array(
		'type' => true,
		'done' => true,
	);

	public function main()
	{
		$type = $this->in('type');

		if ($type != '') {
			$this->templateSetType($type);
			$this->out('type', $type);
			$this->out('done', true);
		} else {
			$type = $this->in('type_fallback');
			if ($type != '') {
				$this->templateSetType($type);
				$this->out('type', $type);
				$this->out('done', true);
			}
		}
	}
}

