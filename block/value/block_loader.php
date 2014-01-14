<?php
/*
 * Copyright (c) 2011, Josef Kufner  <jk@frozen-doe.net>
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

