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
		'link' => DEBUG_CASCADE_GRAPH_DOC_LINK,	// Link to documentation.
		'slot' => 'default',
		'slot_weight' => 50,
	);

	protected $outputs = array(
		'done' => true,
	);

	public function main()
	{
		/* Initialize cascade controller */
		$cascade = new CascadeController();
		$cascade->set_replacement_table($this->get_cascade_controller()->get_replacement_table());

		/* Prepare starting blocks */
		$errors = array();
		$done = $cascade->add_blocks_from_ini(null, $this->in('blocks'), $this->context, $errors);

		/* Template object will render & cache image */
		$this->template_add('_cascade_graph', 'core/cascade_graph', array(
				'cascade' => $cascade,
				'dot_name_tpl' => DEBUG_CASCADE_GRAPH_FILE,
				'dot_url_tpl' => DEBUG_CASCADE_GRAPH_URL,
				'link' => $this->in('link'),
				'preview' => true,
				'whitelist' => $this->visible_block_names(),
				'errors' => $errors,
				'style' => 'page_content',
			));

		$this->out('done', $done);
	}
}

