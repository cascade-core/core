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
 * Create slots. When used with core/ini/load, sections are slot names and
 * section contents specifies all options like slot and weight.
 */
class B_core__out__multi_slot extends \Cascade\Core\Block
{
	const force_exec = true;

	protected $inputs = array(
		'slot' => 'default',
		'slot_weight' => 50,
		'list' => null,			// List of slots.
	);

	protected $connections = array(
		'list' => array(),
	);

	protected $outputs = array(
		'*' => true,
		'done' => true,
	);

	public function main()
	{
		$list = $this->in('list');

		if (!is_array($list)) {
			error_msg('Input "list" must contain array!');
		} else {
			foreach ($list as $name => $opts) {
				if (isset($opts['slot'])) {
					debug_msg('Adding slot "%s" into slot "%s".', $name, $opts['slot']);
				} else {
					debug_msg('Adding slot "%s" into default slot.', $name);
				}
				$this->templateAddToSlot($name, @$opts['slot'], @$opts['weight'], 'core/slot', array(
						'name' => $name,
					) + $opts);

				$this->out($name, $name);
			}
			$this->out('done', true);
		}
	}
}

