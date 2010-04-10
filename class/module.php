<?php
/*
 * Copyright (c) 2010, Josef Kufner  <jk@frozen-doe.net>
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

abstract class Module {

	// module status
	const QUEUED   = 0x00;
	const RUNNING  = 0x01;
	const ZOMBIE   = 0x02;
	const DISABLED = 0x04;
	const FAILED   = 0x08;

	public static $STATUS_NAMES = array(
		self::QUEUED   => 'queued',
		self::RUNNING  => 'running',
		self::ZOMBIE   => 'zombie',
		self::DISABLED => 'disabled',
		self::FAILED   => 'failed',
	);


	private $id;
	private $pipeline_controller;
	private $module_name;
	private $slot_weight_penalty;		// guarantees keeping order of output objects

	private $status = self::QUEUED;
	private $output_cache = array();
	private $forward_list = array();

	protected $context;

	// list of inputs and their default values
	protected $inputs = array(
		// 'input-name' => 'default-value',
		// 'input-name' => array('MODULE', 'output'),
		// '*' => 'default-value',
	);

	// list of outputs (no default values)
	protected $outputs = array(
		// 'output-name' => true,
		// '*' => true,
	);


	abstract public function main();

	// Get unprepared output (called after main; once or never for each output).
	// Remember to call $this->context->update_enviroment() if required.
	//abstract public function get_output();


	final public function id()
	{
		return $this->id;
	}


	final public function module_name()
	{
		return $this->module_name;
	}


	final public function status()
	{
		return $this->status;
	}

	/****************************************************************************
	 *	Part of Pipeline Controller
	 */

	// "constructor" -- called imediately after module creation
	final public function pc_init($id, $pipeline_controller, $module_name, $context, $add_order)
	{
		$this->id = $id;
		$this->pipeline_controller = $pipeline_controller;
		$this->module_name = $module_name;
		$this->context = $context;
		$this->slot_weight_penalty = 1.0 - 100.0 / ($add_order + 99.0); // lim -> inf = 1

		// add common inputs
		$this->inputs['enable'] = true;
	}


	final public function pc_connect(array $connections)
	{
		$wildcard = array_key_exists('*', $this->inputs);

		/* replace defaults */
		$new_inputs = $connections + $this->inputs;

		/* check connections */
		if (!$wildcard && count($this->inputs) != count($new_inputs)) {
			/* Connected non-existent inputs */
			foreach(array_diff_key($connections, $this->inputs) as $in => $out) {
				error_msg('Input "%s.%s" does not exist!', $this->id, $in);
			}
			return false;
		}

		$this->inputs = $new_inputs;
		return true;
	}


	final public function pc_execute(& $module_refs)
	{
		switch ($this->status) {
			case self::ZOMBIE:
			case self::DISABLED:
				return true;

			case self::FAILED:
				debug_msg('%s: Skipping failed module "%s"', $this->module_name(), $this->id());
				return false;

			case self::RUNNING:
				error_msg('Circular dependency detected while executing module "%s" !', $this->id);
				return false;
		}

		debug_msg('%s: Preparing module "%s"', $this->module_name(), $this->id());
		$this->status = self::RUNNING;

		/* dereference module names and build dependency list */
		$dependencies = array();
		foreach($this->inputs as $in => & $out) {
			if (is_array($out)) {
				@list($mod_name, $mod_out) = $out;

				// connect to output
				if (($m = @$module_refs[$mod_name]) && (isset($m->outputs[$mod_out]) || isset($m->outputs['*']))) {
					$dependencies[$mod_name] = $m;
					$out[0] = $m;
				} else {
					error_msg('Can\'t connect input "%s.%s" to "%s.%s" !',
							$this->id, $in, $mod_name, $mod_out);
					$this->status = self::FAILED;
				}
			}
		}

		/* abort if failed */
		if (!$this->status == self::FAILED) {
			error_msg('%s: Failed to prepare module "%s"', $this->module_name(), $this->id());
			return false;
		}

		/* execute dependencies */
		// TODO: Lze spoustet zavislosti az na vyzadani a ne predem vse ?
		//		-- Pokud ano, tak bude potreba poresit preposilani vystupu.
		foreach($dependencies as & $d) {
			if (!$d->pc_execute(& $module_refs)) {
				$this->status = self::FAILED;
				break;
			}
		}

		/* abort if failed */
		if ($this->status == self::FAILED) {
			error_msg('%s: Failed to solve dependencies of module "%s"', $this->module_name(), $this->id());
			return false;
		}

		/* do not execute if disabled */
		if (!$this->in('enable')) {
			debug_msg('%s: Skipping disabled module "%s"', $this->module_name(), $this->id());
			$this->status = self::DISABLED;
			return true;
		}

		/* execute main */
		debug_msg('%s: Starting module "%s"', $this->module_name(), $this->id());
		$this->context->update_enviroment();
		$this->main();
		$this->status = self::ZOMBIE;

		/* execute & evaluate forwarded outputs */
		// TODO - nebylo by lepsi to udelat az na pozadani ?
		//	-- ne, nebylo. Prilis by se to zeslozitilo a rezije by byla prilis velika.
		//	  Lepe bude pockat az se budou resit zavislosti na pozadani
		//	  a udelat to pri tom.
		foreach($this->forward_list as $name => $src) {
			list($src_name, $src_out) = $src;
			$m = @$module_refs[$src_name];
			if ($m && (isset($m->outputs[$src_out]) || isset($m->outputs['*']))) {
				if ($m->pc_execute(& $module_refs)) {
					$this->output_cache[$name] = $m->pc_get_output($src_out);
				} else {
					error_msg('Source module or output not found while forwarding to "%s.%s" from "%s.%s"!',
							$this->id(), $name, $src_name, $src_out);
					$this->status = self::FAILED;
					break;
				}
			} else {
				error_msg('Can\'t forward output "%s.%s" from "%s.%s" !',
						$this->id, $name, $src_name, $src_out);
				$this->status = self::FAILED;
				break;
			}

		}

		return true;
	}


	final private function pc_get_output($name)
	{
		if (array_key_exists($name, $this->output_cache)) {
			// cached output
			return $this->output_cache[$name];
		} else if ($this->status == self::DISABLED) {
			return null;
		} else {
			// create output and cache it
			if (method_exists($this, 'get_output')) {
				$value = $this->get_output($name);
			} else {
				return null;
			}
			$this->output_cache[$name] = $value;
			return $value;
		}
	}


	final public function pc_inputs()
	{
		return $this->inputs;
	}


	final public function pc_outputs()
	{
		return $this->output_cache + $this->outputs;
	}

	final public function pc_forwarded_outputs()
	{
		return $this->forward_list;
	}


	final public function pc_output_exists($name)
	{
		return array_key_exists($name, $this->output_cache)
			|| array_key_exists($name, $this->outputs)
			|| array_key_exists('*', $this->outputs);
	}


	/****************************************************************************
	 *	For module itself
	 */

	// get value from input
	final protected function in($name)
	{
		// TODO: Virtualni moduly -- Neexistujici moduly vzdy pritomne
		//       v pipeline, ktere maji zvlastni jmeno osetrene zde.
		//       Umozni to velmi efektivne pristupovat k casto
		//       pouzivanym hodnotam. Mozna by tak sel resit kontext.

		// get input
		if (array_key_exists($name, $this->inputs)) {
			$ref = & $this->inputs[$name];
		} else if (array_key_exists('*', $this->inputs)) {
			$ref = & $this->inputs['*'];
		} else {
			// or fail
			error_msg('Input "%s" is not defined!', $name);
			return null;
		}

		// read input
		if (is_array($ref)) {
			// read from output
			return $ref[0] !== null ? $ref[0]->pc_get_output($ref[1]) : null;
		} else {
			// ref is constant
			return $ref;
		}
	}


	// get input names, excluding common inputs and '*'
	final protected function input_names()
	{
		return array_diff(array_keys($this->inputs), array(
				'*',
				'enable',
			));
	}


	// set value to output
	final protected function out($name, $value)
	{
		$this->output_cache[$name] = $value;
	}


	// set all output values (keys are output names)
	final protected function out_all($values)
	{
		$this->output_cache = $values;
	}


	// forward output from another module
	final protected function out_forward($name, $source_module, $source_name)
	{
		$this->forward_list[$name] = array($source_module, $source_name);
	}


	// add output object to template subsystem
	final protected function template_add($id_suffix, $template, $data = array())
	{
		$this->template_add_to_slot($id_suffix, null, null, $template, $data);
	}


	// add output object to template subsystem (with slot and weight)
	final protected function template_add_to_slot($id_suffix, $slot, $weight, $template, $data = array())
	{
		$id = $id_suffix === null ? $this->id() : $this->id().'_'.$id_suffix;
		$t = $this->context->get_template_engine();
		$t->add_object($id, $slot === null ? $this->in('slot') : $slot,
				($weight === null ? $this->in('slot-weight') : $weight) + $this->slot_weight_penalty,
				$template, $data, $this->context);

	}


	// set page title
	final protected function template_set_page_title($title, $format = null)
	{
		$t = $this->context->get_template_engine();
		if ($title !== null) {
			$t->slot_option_set('root', 'page_title', $title);
		}
		if ($format !== null) {
			$t->slot_option_set('root', 'page_title_format', $format);
		}
	}


	// set output type
	final protected function template_set_type($type)
	{
		$t = $this->context->get_template_engine();
		$t->slot_option_set('root', 'type', $type);
	}


	// set slot option
	final protected function template_option_set($slot, $option, $value)
	{
		$t = $this->context->get_template_engine();
		return $t->slot_option_set($slot, $option, $value);
	}


	// append value to slot option (which is list)
	final protected function template_option_append($slot, $option, $value)
	{
		$t = $this->context->get_template_engine();
		return $t->add_slot_option($slot, $option, $value);
	}


	// add module to pipeline
	final protected function pipeline_add($id, $module, $force_exec = false, $connections = array(), $context = null)
	{
		return $this->pipeline_controller->add_module($id, $module, $force_exec, $connections, $context === null ? $this->context : $context);
	}


	// add modules to pipeline from parsed inifile
	final protected function pipeline_add_from_ini($parsed_ini_with_sections, $context = null)
	{
		return $this->pipeline_controller->add_modules_from_ini($parsed_ini_with_sections, $context === null ? $this->context : $context);
	}
}

// vim:encoding=utf8:

