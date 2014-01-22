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
 * Show, how would cascade look like if specified blocks were inserted
 * into it. It uses it's own instance of cascade controller to make
 * the  preview.
 */
class B_core__devel__preview extends \Cascade\Core\Block
{
	const force_exec = true;

	protected $inputs = array(
		'blocks' => null,			// Blocks loaded from INI file.
		'graphviz_cfg' => null,			// Cascade visualization config
		'graphviz_profile' => 'cascade',	// Cascade visualization profile
		'slot' => 'default',
		'slot_weight' => 50,
	);

	protected $connections = array(
		'blocks' => array(),
		'graphviz_cfg' => array('config', 'core.graphviz'),
	);

	protected $outputs = array(
		'done' => true,
	);

	public function main()
	{
		$gv_cfg = $this->in('graphviz_cfg');
		$gv_profile = $this->in('graphviz_profile');

		/* Initialize cascade controller */
		$cascade = $this->getCascadeController()->cloneEmpty();

		/* Prepare starting blocks */
		$errors = array();
		$done = $cascade->addBlocksFromArray(null, $this->in('blocks'), $this->context, $errors);

		// export dot file
		$dot = $cascade->exportGraphvizDot($gv_cfg[$gv_profile]['doc_link'], $this->visibleBlockNames());
		$hash = md5($dot);
		$dot_file = filename_format($gv_cfg[$gv_profile]['src_file'], array('hash' => $hash, 'ext' => 'dot'));

		file_put_contents($dot_file, $dot);

		/* Template object will render & cache image */
		$this->templateAdd('_cascade_graph', 'core/cascade_graph', array(
				'hash' => $hash,
				'profile' => 'cascade',
				'link' => $gv_cfg['renderer']['link'],
				'preview' => true,
				'errors' => $errors,
				'style' => 'page_content',
			));

		$this->out('done', $done);
	}
}

