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

/**
 * Show something using specified template. When new block is created by
 * inheriting from this one, template name can be specified in $template
 * protected property.
 *
 * If 'data' input is false or $template property is set, all inputs are
 * forwarded to template, otherwise 'data' input is used.
 */
class B_core__out__output extends Block
{
	const force_exec = true;

	protected $inputs = array(
		'template' => array(),	// Template to use.
		'data' => false,	// If false, all inputs are forwarded, otherwise only content of this one.
		'*' => null,		// All inputs are forwarded to template if input 'data' is false.
		'slot' => 'default',
		'slot_weight' => 50,
	);

	protected $outputs = array(
		'done' => true,
	);

	protected $template = null;	// Used instead of template input


	function main()
	{
		if ($this->template === null) {

			// Template is not set, use 'template' and 'data'
			// inputs. If 'data' input is false, forward everything
			// to template.

			$data = $this->in('data');
			if ($data === false) {
				$data = array();
				foreach ($this->inputNames() as $i) {
					$data[$i] = $this->in($i);
				}
			}

			$this->templateAdd(null, $this->in('template'), $data);

		} else {

			// Template is set, ignore 'template' and 'data' inputs
			// and forward everything to template.

			$data = array();
			foreach ($this->inputNames() as $i) {
				$data[$i] = $this->in($i);
			}

			$this->templateAdd(null, $this->template, $data);

		}

		$this->out('done', true);
	}
}

