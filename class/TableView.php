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

/**
 * Simple table view.
 *
 * Renders list of items into predefined table.
 */
class TableView {

	private $class;
	private $row_class;
	private $columns;
	private $actions;		// Links shown in footer
	private $data_array;
	private $data_iterator;

	/**
	 * Show table header?
	 */
	public  $show_header = true;

	/**
	 * Show table footer?
	 */
	public  $show_footer = false;

	/**
	 * Callable which returns array of data attributes of `<tr>` tag.
	 *
	 * Returns `array($k => $v)`, which will be added to `<tr>` element as 
	 * `data-$k="$v"`.
	 */
	public  $row_data;


	/**
	 * Constructor.
	 */
	public function __construct()
	{
	}


	/**
	 * Add column to table.
	 *
	 * $type sets template used to render the column, $opts are passed to 
	 * the template.
	 */
	public function addColumn($type, $opts)
	{
		$this->columns[] = array($type, $opts);
	}


	/**
	 * Get columns added by addColumn().
	 *
	 * TODO: Rename to `getColumns`.
	 */
	public function columns()
	{
		return $this->columns;
	}


	/**
	 * Add action button.
	 */
	public function addAction($name, $opts)
	{
		$this->actions[$name] = $opts;
	}


	/**
	 * Get all actions added with addAction().
	 */
	public function getActions()
	{
		return $this->actions;
	}


	/**
	 * Set class attribute of the table.
	 */
	public function setTableClass($class)
	{
		$this->class = $class;
	}


	/**
	 * Get class attribute of the table.
	 */
	public function getTableClass()
	{
		return $this->class;
	}


	/**
	 * Set class attribute of each row.
	 */
	public function setRowClass($class)
	{
		$this->row_class = $class;
	}


	/**
	 * Get class attribute of each row.
	 */
	public function getRowClass()
	{
		return $this->row_class;
	}


	/**
	 * Set iterator object as data source.
	 *
	 * To retrieve next row the `$iterator_object->$iterator_func()` is called.
	 */
	public function setDataIteratorFunction($iterator_object, $iterator_func)
	{
		$this->data_iterator = array($iterator_object, $iterator_func);
	}


	/**
	 * Set array as data source.
	 *
	 * One item in array is one row in table.
	 */
	public function setData($data)
	{
		$this->data_array = $data;
		if ($this->data_array instanceof \Iterator) {
			$this->data_array->rewind();
		} else {
			reset($this->data_array);
		}
	}


	/**
	 * Get next row from data source (used by template).
	 */
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

