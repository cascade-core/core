<?php
/*
 * Copyright (c) 2011, Josef Kufner  <jk@frozen-doe.net>
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

class B_core__devel__doc__everything extends B_core__devel__doc__index
{
	const force_exec = true;

	protected $inputs = array(
		'link' => DEBUG_CASCADE_GRAPH_LINK,
		'heading_level' => 2,
		'require_description' => true,
		'slot' => 'default',
		'slot_weight' => 50,
	);

	protected $outputs = array(
		'done' => true,
	);


	public function main()
	{
		$link = $this->in('link');
		$slot = $this->in('slot');
		$slot_weight = $this->in('slot_weight');
		$heading_level = $this->in('heading_level');
		$require_description = $this->in('require_description');

		$titles = $this->get_titles();
		$blocks = $this->get_blocks();

		foreach ($blocks as $prefix => $prefix_blocks) {

			$this->cascade_add('doc_'.$prefix, 'core/out/header', null, array(
					'level' => $heading_level,
					'text' => isset($titles[$prefix])
							? $titles[$prefix]
							: sprintf(_('Plugin: %s'), $prefix),
					'slot' => $slot,
					'slot_weight' => $slot_weight++,
				));	

			foreach ($prefix_blocks as $id => $block) {
				$this->cascade_add('doc_'.$prefix.'__'.$id, 'core/devel/doc/show', null, array(
						'heading_level' => $heading_level + 1,
						'block' => $block,
						'link' => $link,
						'require_description' => $require_description,
						'slot' => $slot,
						'slot_weight' => $slot_weight++,
					));	
			}
		}

		$this->out('done', !empty($blocks));
	}
}

