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
 * Cascade loader reads all it's inputs and adds all specified blocks
 * to cascade. Blocks can be specified as simple string (one block per
 * input) or array of strings (many blocks per input).
 *
 * Block IDs are same as names of corresponding inputs with index appended.
 *
 * Example: When input 'content' gets value array('page/a', 'page/b'), cascade
 * loader adds blocks 'page/a' with ID 'content_0' and 'page/b' with ID
 * 'content_1' to cascade.
 *
 * This block is designed for use with core/ini/router. See default
 * configuration for detailed example.
 */
class B_core__value__cascade_loader extends Block
{
	const force_exec = true;

	protected $inputs = array(
		'*' => null,			// Blocks or lists of blocks to add.
		'block_fmt' => null,		// Format (sprintf) applied to block name (when prefix/suffix is needed).
		'output_forward' => 'done',	// List of outputs which will be forwarded back to this block.
	);

	protected $outputs = array(
		'*' => true,			// Forwarded outputs from added blocks as specified
						// by 'output_forward' input. Output names are
						// prefixed with source block ID. Example: 'content_0_done'
		'done' => true,			// True when all blocks were added successfuly.
	);


	public function main()
	{
		$all_ok = true;
		$any_ok = false;

		$block_fmt = $this->in('block_fmt');
		$output_forward = $this->in('output_forward');
		if (!is_array($output_forward)) {
			$output_forward = preg_split('/[^a-zA-Z0-9_-]+/', $output_forward, -1, PREG_SPLIT_NO_EMPTY);
		}

		foreach ((array) $this->input_names() as $i) {
			if ($i == 'output_forward' || $i == 'block_fmt') {
				continue;
			}

			$mod = $this->in($i);
			foreach ((array) $mod as $m => $mod2) {
				$id = preg_replace('/[^a-zA-Z0-9_]+/', '_', $i.'_'.$m);
				$block = $this->cascade_add($id, $block_fmt !== null ? sprintf($block_fmt, $mod2) : $mod2, true, array(
						//'enable' => array('parent', 'done'),
					));
				if ($block !== false) {
					$any_ok = true;
					foreach ($output_forward as $out) {
						$this->out_forward($id.'_'.$out, $id, $out);
					}
				} else {
					$all_ok = false;
				}
			}
		}
		$this->out('done', $all_ok && $any_ok);
	}
}



