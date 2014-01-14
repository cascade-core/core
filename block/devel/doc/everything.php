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
 * Show documentation of all blocks. Useful when creating printable referrence
 * manual. It simply adds required core/devel/doc/show blocks.
 */
class B_core__devel__doc__everything extends B_core__devel__doc__index
{
	const force_exec = true;

	protected $inputs = array(
		'link' => DEBUG_CASCADE_GRAPH_DOC_LINK,	// Link to documentation.
		'heading_level' => 2,			// Level of the first heading.
		'require_description' => true,		// Show only documented blocks.
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

		$titles = $this->getTitles();
		$blocks = $this->getCascadeController()->getKnownBlocks();

		foreach ($blocks as $prefix => $prefix_blocks) {

			$this->cascadeAdd('doc_'.$prefix, 'core/out/header', null, array(
					'level' => $heading_level,
					'text' => isset($titles[$prefix])
							? $titles[$prefix]
							: sprintf(_('Plugin: %s'), $prefix),
					'slot' => $slot,
					'slot_weight' => $slot_weight++,
				));	

			foreach ($prefix_blocks as $id => $block) {
				$this->cascadeAdd('doc_'.$prefix.'__'.$id, 'core/devel/doc/show', null, array(
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

