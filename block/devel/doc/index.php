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
 * Loads and shows list of all known blocks. Links are easily usable with
 * core/deve/doc/show block.
 */

class B_core__devel__doc__index extends \Cascade\Core\Block
{
	const force_exec = true;

	protected $inputs = array(
		'link' => null,				// Link to documentation.
		'writable_only' => false,		// Show only blocks from writable storages. It does 
							// not mean that listed blocks are writable, only
							// that storage is capable of writing.
		'heading_level' => 2,			// Level of the first heading.
		'slot' => 'default',
		'slot_weight' => 50,
	);

	protected $outputs = array(
		'done' => true,
	);


	public function main()
	{
		$writable_only = $this->in('writable_only');
		$blocks = $this->getCascadeController()->getKnownBlocks($writable_only);

		$this->templateAdd(null, 'core/doc/index', array(
				'link' => $this->in('link'),
				'blocks' => $blocks,
				'heading_level' => $this->in('heading_level'),
				'titles' => $this->getTitles(),
			));

		$this->out('done', !empty($blocks));
	}


	protected function getTitles() {
		return array(
			'' => _('Application'),
			'core' => _('Core'),
		);
	}

}

