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
	private $actions;	// Links shown in footer
	private $data_array;
	private $data_iterator;

	public  $show_header;	// bool
	public  $show_footer;	// bool
	public  $row_data;	// callable, returns array($k => $v) which will
				// be added to <tr> element as data-$k="$v"


	public function __construct()
	{
	}


	public function add_column($type, $opts)
	{
		$this->columns[] = array($type, $opts);
	}


	public function add_action($name, $opts)
	{
		$this->actions[$name] = $opts;
	}

	public function set_table_class($class)
	{
		$this->class = $class;
	}

	public function get_table_class()
	{
		return $this->class;
	}

	public function set_row_class($class)
	{
		$this->row_class = $class;
	}

	public function get_row_class()
	{
		return $this->row_class;
	}


	public function get_actions()
	{
		return $this->actions;
	}


	public function set_data_iterator_function($iterator_object, $iterator_func)
	{
		$this->data_iterator = array($iterator_object, $iterator_func);
	}

	public function set_data($data)
	{
		$this->data_array = $data;
		reset($this->data_array);
	}


	public function columns()
	{
		return $this->columns;
	}


	public function get_next_row_data()
	{
		$method = $this->data_iterator[1];
		if ($method !== null) {
			return $this->data_iterator[0]->$method();
		} else {
			list($k, $v) = each($this->data_array);
			return $v;
		}
	}
}

