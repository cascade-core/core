<?php
/*
 * Copyright (c) 2010, Josef Kufner  <jk@myserver.cz>
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

	private $id;
	private $pipeline_controller;
	private $module_name;

	private $is_prepared = null;
	private $is_done = false;
	private $input_refs = array();
	private $output_cache = array();

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


	final public function id()
	{
		return $this->id;
	}


	final public function module_name()
	{
		return $this->module_name;
	}


	/****************************************************************************
	 *	Part of Pipeline Controller
	 */

	// "constructor" -- called imediately after module creation
	final public function pc_init($id, $pipeline_controller, $module_name)
	{
		$this->id = $id;
		$this->pipeline_controller = $pipeline_controller;
		$this->module_name = $module_name;
	}


	final public function pc_connect($connections)
	{
		$wildcard = array_key_exists('*', $this->inputs);

		/* replace defaults */
		$new_inputs = array_merge($this->inputs, $connections);

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
		if ($this->is_done) {
			return true;
		} else if ($this->is_prepared !== null) {
			debug_msg('%s: Skipping partialy prepared module "%s"', $this->module_name(), $this->id());
			return false;
		}

		debug_msg('%s: Preparing module "%s"', $this->module_name(), $this->id());
		$this->is_prepared = true;

		/* dereference module names and build dependency list */
		$dependencies = array();
		foreach($this->inputs as $in => & $out) {
			if (is_array($out)) {
				@list($mod_name, $mod_out) = $out;

				// connect to output
				if (($m = @$module_refs[$mod_name]) && isset($m->outputs[$mod_out])) {
					$dependencies[$mod_name] = $m;
					$out[0] = $m;
				} else {
					error_msg('Can\'t connect input "%s.%s" to "%s.%s" !',
							$this->id, $in, $mod_name, $mod_out);
					$this->is_prepared = false;
				}
			}
		}

		/* abort if failed */
		if (!$this->is_prepared) {
			error_msg('%s: Failed to prepare module "%s"', $this->module_name(), $this->id());
			return false;
		}

		/* execute dependencies */
		foreach($dependencies as & $d) {
			if (!$d->is_done) {
				if ($d->is_prepared) {
					error_msg('Circular dependency detected while preparing module "%s" !', $this->id);
					$this->is_prepared = false;
				} else {
					$this->is_prepared &= $d->pc_execute(& $module_refs);
				}

				if (!$this->is_prepared) {
					error_msg('%s: Failed to solve dependencies of module "%s"', $this->module_name(), $this->id());
					return false;
				}
			}
		}

		/* execute main */
		debug_msg('%s: Starting module "%s"', $this->module_name(), $this->id());
		$this->main();
		$this->is_done = true;
		return true;
	}


	final private function get_output($name)
	{
		assert($this->is_done);

		if (array_key_exists($name, $this->output_cache)) {
			// cached output
			return $this->output_cache[$name];

		} else {
			// create output and cache it
			$fn = 'out_'.$name;
			if (method_exists($this, $fn)) {	// FIXME
				$value = $this->$fn();
			} else {
				$value = $this->out_wildcard($name);
			}
			$this->output_cache[$name] = $value;
			return $value;
		}
	}


	/****************************************************************************
	 *	For module itself
	 */

	// get value from input
	final protected function in($name)
	{
		// get input
		if (array_key_exists($name, $this->input_refs)) {
			$ref = & $this->input_refs[$name];

		} else if (array_key_exists($name, $this->inputs)) {
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
			return $ref[0] ? $ref[0]->get_output($ref[1]) : null;
		} else {
			// ref is constant
			return $ref;
		}
	}


	// set value to output
	final protected function out($name, $value)
	{
		$this->output_cache[$name] = $value;
	}


	// add output object to template subsystem
	final protected function template_add($id_suffix, $template, $data)
	{
		// todo
	}


	// add output object to template subsystem
	final protected function pipeline_add($module, $id, $connections)
	{
		return $this->pipeline_controller->add_module($module, $id, $connections);
	}
}

// vim:encoding=utf8:
?>
