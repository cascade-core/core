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
 * Split data accessed via iterator to multiple iterators. Numeric inputs
 * specify sizes of slices, according numeric output then contain results. Some
 * items may be cached when neccesary.
 */

class B_core__stream__split extends \Cascade\Core\Block {

	protected $inputs = array(
		'iter' => null,
		'*' => null,
	);

	protected $connections = array(
		'iter' => array(),
	);

	protected $outputs = array(
		'done' => true,
		'*' => true,
	);

	private $iter_obj, $iter_fn;
	private $iter_next_row = 0;

	private $row = array();
	private $end = array();
	private $cache = array();
	private $cache_next = 0;

	public function main()
	{
		list($this->iter_obj, $this->iter_fn) = $this->in('iter');

		if (!is_object($this->iter_obj) || !isset($this->iter_fn)) {
			return;
		}

		$this->row_count = $this->collectNumericInputs();

		$begin = 0;
		foreach($this->row_count as $k => $count) {
			$ki = 'iter_'.$k;
			$this->row[$ki] = $begin;
			$this->out($k + 1, array($this, $ki));

			if ($count === '*') {
				$this->end[$ki] = TRUE;
				break;
			} else {
				$this->end[$ki] = $begin + $count;
				$begin += $count;
			}
		}
	}


	/* iterator */
	public function __call($k, $args)
	{
		$iter_fn = $this->iter_fn;	// PHP bug
		$row = & $this->row[$k];

		if ($row >= $this->end[$k] && $this->end[$k] !== TRUE) {
			/* end */
			return null;

		} else if ($row == $this->iter_next_row) {
			/* next row is correct row */
			$row++;
			$this->iter_next_row++;
			return $this->iter_obj->$iter_fn();

		} else if ($row < $this->iter_next_row) {
			/* wanted row is cached */
			$v = $this->cache[$row];
			unset($this->cache[$row]);
			$row++;
			return $v;

		} else {
			/* wanted row is far in future */
			while ($this->iter_next_row < $row) {
				if (($this->cache[$this->iter_next_row] = $this->iter_obj->$iter_fn()) !== NULL) {
					$this->iter_next_row++;
				} else {
					/* out of future */
					return null;
				}
			}
			$row++;
			$this->iter_next_row++;
			return $this->iter_obj->$iter_fn();
		}
	}
}

