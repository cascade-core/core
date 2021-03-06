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
 * Show table generated by TableView class.
 */

class B_core__out__table extends \Cascade\Core\Block
{
	const force_exec = true;

	protected $inputs = array(
		'items' => null,
		'config' => null,
		'slot' => 'default',
		'slot_weight' => 50,
	);

	protected $connections = array(
		'items' => array(),
		'config' => array(),
	);

	protected $outputs = array(
	);

	private $items = null;


	public function main()
	{
		$this->items = $this->in('items');
		$table = new \Cascade\Core\TableView();

		foreach($this->in('config') as $row => $opts) {
			$table->addColumn($opts['type'], $opts);
		}

		if (is_array($this->items) && count($this->items) == 2 && is_object($this->items[0]) && is_string($this->items[1])) {
			debug_msg('Iterator mode');
			$table->setDataIteratorFunction($this, 'nextRowIter');
		} else {
			debug_msg('Array mode');
			$table->setDataIteratorFunction($this, 'nextRowArray');
		}
	
		$this->templateAdd(null, 'core/table', $table);
	}


	public function nextRowIter()
	{
		$get_next = $this->items[1];
		return $this->items[0]->$get_next();
	}


	public function nextRowArray()
	{
		list($k, $v) = each($this->items);
		return $v;
	}
}

