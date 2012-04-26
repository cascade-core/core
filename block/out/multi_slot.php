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
 * Create slots. When used with core/ini/load, sections are slot names and
 * section contents specifies all options like slot and weight.
 */
class B_core__out__multi_slot extends Block
{
	const force_exec = true;

	protected $inputs = array(
		'slot' => 'default',
		'slot_weight' => 50,
		'list' => array(),	// List of slots.
	);

	protected $outputs = array(
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
				$this->template_add_to_slot($name, @$opts['slot'], @$opts['weight'], 'core/slot', array(
						'name' => $name,
					) + $opts);
			}
		}
	}
}

