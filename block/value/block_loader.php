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

/**
 * Block loader inserts one block into cascade. It allows to set block's inputs.
 */
class B_core__value__block_loader extends Block
{
	const force_exec = true;

	protected $inputs = array(
		'id' => null,			// ID of the block (basename of block if empty)
		'block' => array(),		// Type of the block
		'block_fmt' => null,		// Format (sprintf) applied to block name (when prefix/suffix is needed).
		'connections' => null,		// Input connections
		'output_forward' => 'done',	// List of outputs which will be forwarded back to this block.
	);

	protected $outputs = array(
		'*' => true,			// Forwarded outputs from added block as specified
						// by 'output_forward' input.
		'done' => true,			// True when block was added successfuly. Can be overriden by output forwarding.
	);


	public function main()
	{

		$id = $this->in('id');
		$block = $this->in('block');
		$block_fmt = $this->in('block_fmt');
		$connections = $this->in('connections');
		$output_forward = $this->in('output_forward');

		$type = $block_fmt !== null ? sprintf($block_fmt, $block) : $block;

		if ($id === null) {
			$id = basename($type);
		}
		$id = preg_replace('/[^a-zA-Z0-9_]+/', '_', $id);

		if (!is_array($output_forward)) {
			$output_forward = preg_split('/[^a-zA-Z0-9_-]+/', $output_forward, -1, PREG_SPLIT_NO_EMPTY);
		}

		$b = $this->cascadeAdd($id, $type, true, (array) $connections);

		if ($b !== false) {
			$this->out('done', true);
			foreach ($output_forward as $out) {
				$this->outForward($out, $id, $out);
			}
		}
	}

}

