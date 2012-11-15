<?php
/*
 * Copyright (c) 2012, Josef Kufner  <jk@frozen-doe.net>
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

class B_core__template__output extends Block
{

        protected $inputs = array(
		'engine' => array(),
        );

        protected $outputs = array(
		'done' => true,
        );

	const force_exec = true;


        public function main()
	{
		$engine = $this->in('engine');

		/* Template object will render & cache image */
		if (DEBUG_CASCADE_GRAPH_ADD) {
			// TODO: hook this on 'done' event of cascade controller and move rendering from template to here
			$engine->add_object('_cascade_graph', 'root', 95, 'core/cascade_graph', array(
					'cascade' => $this->get_cascade_controller(),
					'dot_name_tpl' => DEBUG_CASCADE_GRAPH_FILE,
					'dot_url_tpl' => DEBUG_CASCADE_GRAPH_URL,
					'link' => DEBUG_CASCADE_GRAPH_DOC_LINK,
					'animate' => DEBUG_CASCADE_GRAPH_ANIMATE,
					'style' => DEBUG_CASCADE_GRAPH_ADD,
				));
		}

		$engine->start();

                $this->out('done', true);
        }

}

