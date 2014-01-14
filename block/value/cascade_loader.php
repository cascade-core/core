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
		'done' => true,			// True when all blocks were added successfuly and at least one block added.
	);


	public function main()
	{
		$all_ok = true;			// All blocks added successfuly?
		$any_ok = false;		// Added at least one block?

		$block_fmt = $this->in('block_fmt');
		$output_forward = $this->in('output_forward');
		if (!is_array($output_forward)) {
			$output_forward = preg_split('/[^a-zA-Z0-9_-]+/', $output_forward, -1, PREG_SPLIT_NO_EMPTY);
		}

		foreach ((array) $this->inputNames() as $i) {
			if ($i == 'output_forward' || $i == 'block_fmt') {
				continue;
			}

			$mod = $this->in($i);
			foreach ((array) $mod as $m => $mod2) {
				if ($mod2 == '') {
					continue;
				}

				$id = preg_replace('/[^a-zA-Z0-9_]+/', '_', $i.'_'.$m);
				$type = $block_fmt !== null ? sprintf($block_fmt, $mod2) : $mod2;

				if ($type == '') {
					continue;
				}

				$block = $this->cascadeAdd($id, $type, true, array(
						//'enable' => array('parent', 'done'),
					));

				if ($block !== false) {
					$any_ok = true;
					foreach ($output_forward as $out) {
						$this->outForward($id.'_'.$out, $id, $out);
					}
				} else {
					$all_ok = false;
				}
			}
		}
		$this->out('done', $all_ok && $any_ok);
	}
}



