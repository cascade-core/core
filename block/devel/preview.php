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
 * Show, how would cascade look like if specified blocks were inserted
 * into it. It uses it's own instance of cascade controller to make
 * the  preview.
 */
class B_core__devel__preview extends Block
{
	const force_exec = true;

	protected $inputs = array(
		'blocks' => array(),			// Blocks loaded from INI file.
		'graphviz_cfg' => array('config', 'core.graphviz'),	// Cascade visualization config
		'graphviz_profile' => 'cascade',	// Cascade visualization profile
		'slot' => 'default',
		'slot_weight' => 50,
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
		$done = $cascade->addBlocksFromIni(null, $this->in('blocks'), $this->context, $errors);

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

