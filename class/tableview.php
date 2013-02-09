<?php
/*
 * Copyright (c) 2010, Josef Kufner  <jk@frozen-doe.net>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. Neither the name of the author nor the names of its contributors
 *    may be used to endorse or promote products derived from this software
 *    without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE REGENTS AND CONTRIBUTORS ``AS IS'' AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED.  IN NO EVENT SHALL THE REGENTS OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
 * OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * SUCH DAMAGE.
 */

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

