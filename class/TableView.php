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

namespace Cascade\Core;

class TableView {

	private $class;
	private $row_class;
	private $columns;
	private $actions;		// Links shown in footer
	private $data_array;
	private $data_iterator;

	public  $show_header = true;	// bool
	public  $show_footer = false;	// bool
	public  $row_data;		// callable, returns array($k => $v) which will
					// be added to <tr> element as data-$k="$v"


	public function __construct()
	{
	}


	public function addColumn($type, $opts)
	{
		$this->columns[] = array($type, $opts);
	}


	public function addAction($name, $opts)
	{
		$this->actions[$name] = $opts;
	}

	public function setTableClass($class)
	{
		$this->class = $class;
	}

	public function getTableClass()
	{
		return $this->class;
	}

	public function setRowClass($class)
	{
		$this->row_class = $class;
	}

	public function getRowClass()
	{
		return $this->row_class;
	}


	public function getActions()
	{
		return $this->actions;
	}


	public function setDataIteratorFunction($iterator_object, $iterator_func)
	{
		$this->data_iterator = array($iterator_object, $iterator_func);
	}

	public function setData($data)
	{
		$this->data_array = $data;
		if ($this->data_array instanceof \Iterator) {
			$this->data_array->rewind();
		} else {
			reset($this->data_array);
		}
	}


	public function columns()
	{
		return $this->columns;
	}


	public function getNextRowData()
	{
		$method = $this->data_iterator[1];
		if ($method !== null) {
			return $this->data_iterator[0]->$method();
		} else if ($this->data_array instanceof \Iterator) {
			if ($this->data_array->valid()) {
				$r = $this->data_array->current();
				$this->data_array->next();
				return $r;
			} else {
				return null;
			}
		} else {
			list($k, $v) = each($this->data_array);
			return $v;
		}
	}
}

