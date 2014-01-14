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
 * Create one slot.
 */
class B_core__out__slot extends Block
{
	const force_exec = true;

	protected $inputs = array(
		'slot' => 'default',
		'slot_weight' => 50,
		'name' => null,			// Name of the new slot.
		'extra_class' => null,		// Class added to slot.
	);

	protected $outputs = array(
		'name' => true,			// Name of the new slot.
		'done' => true,
	);

	public function main()
	{
		$name = $this->in('name');
		$name = get_ident($name == '' ? 'slot_'.$this->fullId() : $name);

		$inputs = array();
		foreach ($this->inputNames() as $in) {
			if ($in != 'slot' && $in != 'slot_weight') {
				$inputs[$in] = $this->in($in);
			}
		}
		$inputs['name'] = $name;
		$this->templateAdd(null, 'core/slot', $inputs);

		$this->out('name', $name);
		$this->out('done', true);
	}
}

