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

class B_core__template__engine extends Block
{

        protected $inputs = array(
		'engine_class' => 'Template',	// Name of template engine class
		'default_type' => 'html5',	// Default output type
		'auto_run' => false,		// If true, this block will hook on cascade controller "on_empty" callback list
        );

        protected $outputs = array(
		'engine' => true,	// Template engine
		'done' => true,
        );

	private $output_created = false;


        public function main()
	{
		$auto_run = $this->in('auto_run');
		$engine_class = $this->in('engine_class');
		$default_type = $this->in('default_type');

		/* create engine */
		$engine = new $engine_class();

		// FIXME: context nobody should ever modify context
		if ($this->context->get_template_engine() === null) {
			$this->context->set_template_engine($engine);
		}

		/* set default output type */
		$engine->slot_option_set('root', 'type', $default_type);

		/* hook on cascade controller callback */
		if ($auto_run) {
			$on_empty_cbl = $this->get_cascade_controller()->register_callback_on_empty($this, 'on_empty_cascade_queue');
		}

		$this->out('engine', $engine);
                $this->out('done', true);
        }


	/**
	 * This callback is called when there are no blocks for execution in 
	 * cascade controller's queue.
	 */
	public function on_empty_cascade_queue()
	{
		if ($this->output_created) {
			return;
		}

		$this->cascade_add('output', 'core/template/output', null, array(
				'engine' => array('parent', 'engine'),
			));

		$this->output_created = true;
	}

}

