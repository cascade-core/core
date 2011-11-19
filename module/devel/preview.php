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

class M_core__devel__preview extends Module
{
	const force_exec = true;

	protected $inputs = array(
		'modules' => array(),
		'link' => DEBUG_PIPELINE_GRAPH_LINK,
		'slot' => 'default',
		'slot-weight' => 50,
	);

	protected $outputs = array(
		'done' => true,
	);

	public function main()
	{
		/* Initialize pipeline controller */
		$pipeline = new PipelineController();
		$pipeline->set_replacement_table($this->get_pipeline_controller()->get_replacement_table());

		/* Prepare starting modules */
		$errors = array();
		$done = $pipeline->add_modules_from_ini(null, $this->in('modules'), $this->context, $errors);

		/* Template object will render & cache image */
		$this->template_add('_pipeline_graph', 'core/pipeline_graph', array(
				'pipeline' => $pipeline,
				'whitelist' => $this->visible_module_names(),
				'dot_name' => 'data/graphviz/pipeline-%s.%s',
				'preview' => true,
				'style' => 'page-content',
				'link' => $this->in('link'),
				'errors' => $errors,
			));

		$this->out('done', $done);
	}
}

