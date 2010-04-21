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

class M_core__stream__split extends Module {

	protected $inputs = array(
		'iter' => array(),
		'*' => null,
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

		$this->row_count = $this->collect_numeric_inputs();

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


// vim:encoding=utf8:

