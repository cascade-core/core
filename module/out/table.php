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

class M_core__out__table extends Module {

	protected $inputs = array(
		'items' => array(),
		'config' => array(),
		'slot' => 'default',
		'slot-weight' => 50,
	);

	protected $outputs = array(
	);

	private $items = null;


	public function main()
	{
		$this->items = $this->in('items');
		$table = new TableView();

		foreach($this->in('config') as $row => $opts) {
			$table->add_column($opts['type'], $opts);
		}

		if (is_array($this->items) && count($this->items) == 2 && is_object($this->items[0]) && is_string($this->items[1])) {
			debug_msg('Iterator mode');
			$table->set_data_iterator_function($this, 'next_row_iter');
		} else {
			debug_msg('Array mode');
			$table->set_data_iterator_function($this, 'next_row_array');
		}
	
		$this->template_add(null, 'core/table', $table);
	}


	public function next_row_iter()
	{
		$get_next = $this->items[1];
		return $this->items[0]->$get_next();
	}


	public function next_row_array()
	{
		list($k, $v) = each($this->items);
		return $v;
	}
}


// vim:encoding=utf8:
