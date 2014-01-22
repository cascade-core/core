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
 * Load and show block documentation stored in its own source code.
 *
 * TODO: Redesign documentation rendering. Let documentating block storage to 
 * define appropriate sections.
 */
class B_core__devel__doc__show extends \Cascade\Core\Block
{
	const force_exec = true;

	protected $inputs = array(
		'block' => null,			// Name of the block to describe.
		'show_code' => false,			// Show full source code of the block?
		'require_description' => false,		// Fail if there is no description.
		'heading_level' => 2,			// Level of the first heading.
		'slot' => 'default',
		'slot_weight' => 50,
	);

	protected $connections = array(
		'block' => array(),
	);

	protected $outputs = array(
		'title' => true,			// Page title
		'desc' => true,
		'blocks' => true,
		'composition_slot' => true,
		'graphviz_cfg' => true,
		'done' => true,
	);

	public function main()
	{
		// Get block name
		$block = $this->in('block');
		if (is_array($block)) {
			$block = join('/', $block);
		}
		$block = str_replace('-', '_', $block);

		// Scan block storages
		foreach ($this->getCascadeController()->getBlockStorages() as $storage) {
			$desc = $storage->describeBlock($block);
			if ($desc) {
				//debug_dump($desc);
				$composition_slot = $this->fullId().'__composition_slot';
				$this->templateAdd(null, 'core/doc/show', array(
						'block' => $block,
						'heading_level' => $this->in('heading_level'),
						'is_local' => in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1', 'localhost')),
						'code' => $this->in('show_code') ? $code : null,
						'block_description' => $desc,
						'composition_slot' => $composition_slot,
					));
				if (!empty($desc['is_composed_block'])) {
					$this->cascadeAdd('cascade_diagram', 'core/devel/preview', null, array(
							'blocks' => array('parent', 'blocks'),
							'slot' => array('parent', 'composition_slot'),
						), array(
							'slot_weight' => 50,
						));
				}
				$this->out('title', $block);
				$this->out('desc', $desc);
				$this->out('blocks', @ $desc['blocks']);
				$this->out('composition_slot', $composition_slot);
				$this->out('done', true);
				break;
			}
		}
	}

}

