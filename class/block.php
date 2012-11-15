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

abstract class Block {

	// block status
	const QUEUED   = 0x00;
	const RUNNING  = 0x01;
	const ZOMBIE   = 0x02;
	const DISABLED = 0x04;
	const FAILED   = 0x08;

	// Default value of 'force_exec' flag when adding this block into
	// cascade (not when calling add_block() from this class).
	// This is used when force_exec arg. is null (or not set at all).
	const force_exec = false;

	public static $STATUS_NAMES = array(
		self::QUEUED   => 'queued',
		self::RUNNING  => 'running',
		self::ZOMBIE   => 'zombie',
		self::DISABLED => 'disabled',
		self::FAILED   => 'failed',
	);

	private $id, $full_id;
	private $cascade_controller;
	private $block_name;
	private $slot_weight_penalty;		// guarantees keeping order of output objects

	private $status = self::QUEUED;
	private $output_cache = array();
	private $forward_list = array();
	private $execution_time = null;		// time [ms] spent in main()
	private $timestamp_create = null;	// when was block created ?
	private $timestamp_start = null;	// when block execution started ?
	private $timestamp_finish = null;	// when block execution finished ?
	private $notes = null;			// notes attached to blocks in graph

	private $parent = null;			// parent block
	private $namespace = null;		// references to other blocks
	protected $context;

	private $cascade_errors = array();	// list of errors received from cascade controller


	// list of inputs and their default values
	protected $inputs = array(
		// 'input_name' => 'default_value',
		// 'input_name' => array('block', 'output'),
		// '*' => 'default_value',
	);

	// list of outputs (no default values)
	protected $outputs = array(
		// 'output_name' => true,
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


	final public function full_id()
	{
		return $this->full_id;
	}


	final public function block_name()
	{
		return $this->block_name;
	}


	final public function status()
	{
		return $this->status;
	}


	final public function get_cascade_controller()
	{
		return $this->cascade_controller;
	}


	final public function get_timestamps()
	{
		return array($this->timestamp_create, $this->timestamp_start, $this->timestamp_finish);
	}


	/****************************************************************************
	 *	Part of Cascade Controller
	 */

	// "constructor" -- called imediately after block creation
	final public function cc_init($parent, $id, $full_id, $cascade_controller, $block_name, $context, $initial_status = self::QUEUED)
	{
		// basic init
		$this->id = $id;
		$this->parent = $parent;
		$this->full_id = $full_id;
		$this->cascade_controller = $cascade_controller;
		$this->block_name = $block_name;
		$this->context = $context;
		$this->timestamp_create = $this->cascade_controller->current_step();
		$this->slot_weight_penalty = 1.0 - 100.0 / ($this->timestamp_create + 100.0); // lim -> inf = 1
		$this->status = $initial_status;

		// add common inputs
		$this->inputs['enable'] = true;
	}


	final public function cc_connect(array $connections)
	{
		$wildcard = array_key_exists('*', $this->inputs);

		/* replace defaults */
		$new_inputs = $connections + $this->inputs;

		/* check connections */
		if (!$wildcard && count($this->inputs) != count($new_inputs)) {
			/* Connected non-existent inputs */
			foreach(array_diff_key($connections, $this->inputs) as $in => $out) {
				error_msg('Input "%s:%s" does not exist!', $this->id, $in);
			}
			return false;
		}

		$this->inputs = $new_inputs;
		return true;
	}


	final public function cc_register_block($block)
	{
		$id = $block->id();

		if (isset($this->namespace[$id])) {
			error_msg('Duplicate ID "%s" in the namespace of %s', $id, $this->full_id());
			return false;
		} else {
			$this->namespace[$id] = $block;
			return true;
		}
	}


	final public function cc_resolve_block_name($block_name)
	{
		$path = explode('.', $block_name);
		$start_name = array_shift($path);

		// Go out
		if ($start_name == '') {
			$m = $this->cascade_controller->resolve_block_name(array_shift($path));
		} else if ($start_name == 'parent') {
			$m = $this->parent;
		} else {
			$t = $this;
			while ($t && !isset($t->namespace[$start_name])) {
				$t = $t->parent;
			}
			$m = $t !== null ? $t->namespace[$start_name] : $this->cascade_controller->resolve_block_name($start_name);
		}

		if (!$m) {
			return null;
		}

		// Go in
		foreach ($path as $p) {
			if (!$m->cc_execute()) {
				return null;
			}
			$m = $m->namespace[$p];
			if ($m === null) {
				return null;
			}
		}

		return $m;
	}


	final public function cc_execute()
	{
		switch ($this->status) {
			case self::ZOMBIE:
			case self::DISABLED:
				return true;

			case self::FAILED:
				debug_msg('%s: Skipping failed block "%s"', $this->block_name(), $this->id());
				return false;

			case self::RUNNING:
				error_msg('Circular dependency detected while executing block "%s" !', $this->id);
				return false;
		}

		$this->timestamp_start = $this->cascade_controller->current_step();
		$this->status = self::RUNNING;
		debug_msg('%s: Preparing block "%s" (t = %d)', $this->block_name(), $this->full_id(), $this->timestamp_start);

		/* dereference block names and build dependency list */
		$dependencies = array();
		foreach($this->inputs as $in => & $out) {
			if (is_array($out)) {
				// connect to output(s)
				$n = count($out);
				if ($n == 0) {
					error_msg('%s: Can\'t connect inputs -- connection for input "%s" of block "%s" is not defined!',
							$this->block_name(), $in, $this->full_id());
					$this->status = self::FAILED;
				} else {
					for ($i = $out[0][0] == ':' ? 1 : 0; $i < $n - 1; $i += 2) {
						$block_name = $out[$i];
						$block_out = $out[$i + 1];
						$m = $this->cc_resolve_block_name($block_name);
						if (!$m) {
							error_msg('%s: Can\'t connect inputs -- block "%s" not found!', $this->block_name(), $block_name);
							$this->status = self::FAILED;
						} else if (isset($m->outputs[$block_out]) || isset($m->outputs['*'])) {
							$dependencies[$m->full_id()] = $m;
							$out[$i] = $m;
						} else {
							error_msg('Can\'t connect input "%s:%s" to "%s:%s" !',
									$this->full_id(), $in, $block_name, $block_out);
							$this->status = self::FAILED;
						}
					}
				}
			}
		}

		/* abort if failed */
		if (!$this->status == self::FAILED) {
			error_msg('%s: Failed to prepare block "%s"', $this->block_name(), $this->full_id());
			$this->timestamp_finish = $this->cascade_controller->current_step();
			return false;
		}

		/* execute dependencies */
		// TODO: Lze spoustet zavislosti az na vyzadani a ne predem vse ?
		//		-- Pokud ano, tak bude potreba poresit preposilani vystupu.
		foreach($dependencies as & $d) {
			if (!$d->cc_execute()) {
				$this->status = self::FAILED;
				break;
			}
		}

		/* abort if failed */
		if ($this->status == self::FAILED) {
			error_msg('%s: Failed to solve dependencies of block "%s"', $this->block_name(), $this->full_id());
			$this->timestamp_finish = $this->cascade_controller->current_step();
			return false;
		}

		/* do not execute if disabled */
		if (!$this->in('enable')) {
			debug_msg('%s: Skipping disabled block "%s"', $this->block_name(), $this->full_id());
			$this->status = self::DISABLED;
			$this->timestamp_finish = $this->cascade_controller->current_step();
			return true;
		}

		/* execute main */
		debug_msg('%s: Starting block "%s"', $this->block_name(), $this->full_id());
		$this->context->update_enviroment();
		$t = microtime(TRUE);
		$this->main();
		$this->execution_time = (microtime(TRUE) - $t) * 1000;
		$this->status = self::ZOMBIE;
		$this->timestamp_finish = $this->cascade_controller->current_step();

		/* execute & evaluate forwarded outputs */
		// TODO - nebylo by lepsi to udelat az na pozadani ?
		//	-- ne, nebylo. Prilis by se to zeslozitilo a rezie by byla prilis velika.
		//	  Lepe bude pockat az se budou resit zavislosti na pozadani
		//	  a udelat to pri tom.
		foreach($this->forward_list as $name => & $src) {
			$n = count($src);
			if ($n == 0) {
				error_msg('%s: Can\'t forward output to "%s:%s" -- no source output defined!', $this->block_name(), $this->full_id(), $name);
				$this->status = self::FAILED;
			} else {
				for ($i = $src[0][0] == ':' ? 1 : 0; $i < $n - 1; $i += 2) {
					$block_name = $src[$i];
					$block_out = $src[$i + 1];
					$b = $this->cc_resolve_block_name($block_name);
					if (!$b) {
						error_msg('%s: Can\'t forward output to "%s:%s" -- block "%s" not found!',
								$this->block_name(), $this->full_id(), $name, $block_name);
						$this->status = self::FAILED;
					} else if (isset($b->outputs[$block_out]) || isset($b->outputs['*'])) {
						if (is_object($b) && !$b->cc_execute()) {
							error_msg('Can\'t forward output to "%s:%s" -- block "%s" has failed!',
								$this->full_id(), $name, $block_name);
						}
						$src[$i] = $b;
					} else {
						error_msg('Can\'t forward output to "%s:%s" from "%s:%s" !',
							$this->full_id(), $name, $block_name, $block_out);
						$this->status = self::FAILED;
					}
				}
				$this->output_cache[$name] = $this->collect_outputs($src);
			}
		}

		return true;
	}


	final public function cc_get_output($name)
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


	final public function cc_inputs()
	{
		return $this->inputs;
	}


	final public function cc_outputs()
	{
		return array_keys($this->output_cache + $this->outputs);
	}

	final public function cc_output_cache()
	{
		return $this->output_cache;
	}

	final public function cc_forwarded_outputs()
	{
		return $this->forward_list;
	}


	final public function cc_output_exists($name, $accept_wildcard = true)
	{
		return array_key_exists($name, $this->output_cache)
			|| array_key_exists($name, $this->outputs)
			|| ($accept_wildcard && array_key_exists('*', $this->outputs));
	}


	final public function cc_get_namespace()
	{
		return $this->namespace;
	}


	final public function cc_execution_time()
	{
		return $this->execution_time;
	}


	final public function cc_dump_namespace($level = 1)
	{
		$str = '';
		$indent = str_repeat('. ', $level);
		foreach ($this->namespace as $name => $m) {
			$str .= $indent.$name."\n".($name != 'self' && $name != 'parent' && $m ? $m->cc_dump_namespace($level + 1) : '');
		}
		return $str;
	}


	/* Describe block, it's inputs, outputs, ... */
	final public function cc_describe_block()
	{
		return array(
			'block' => str_replace('__', '/', preg_replace('/^B_/', '', __CLASS__)),
			'force_exec' => self::force_exec,
			'inputs' => $this->inputs,
			'outputs' => $this->outputs,
		);
	}


	final public function cc_add_note($note)
	{
		if ($this->notes === null) {
			$this->notes = array($note);
		} else {
			$this->notes[] = $note;
		}
	}


	final public function cc_get_notes()
	{
		return (array) $this->notes;
	}


	/****************************************************************************
	 *	For block itself
	 */

	// get value from input
	final protected function in($name)
	{
		// TODO: Virtualni moduly -- Neexistujici moduly vzdy pritomne
		//       v cascade, ktere maji zvlastni jmeno osetrene zde.
		//       Umozni to velmi efektivne pristupovat k casto
		//       pouzivanym hodnotam. Mozna by tak sel resit kontext.

		// get input
		if (array_key_exists($name, $this->inputs)) {
			$ref = & $this->inputs[$name];
		} else if (array_key_exists('*', $this->inputs)) {
			$ref = & $this->inputs['*'];
		} else {
			// or fail
			error_msg('%s: Input "%s" is not defined!', $this->block_name(), $name);
			return null;
		}

		return $this->collect_outputs($ref);
	}


	private function collect_outputs(& $ref)
	{
		// read input
		if (is_array($ref)) {
			// read from output
			$n = count($ref);
			if ($n == 2) {
				// single output
				return is_object($ref[0]) ? $ref[0]->cc_get_output($ref[1]) : null;
			} else {
				// use specified function to create one value from multiple outputs
				switch ($ref[0]) {
					case ':or':
						for ($i = 1; $i < $n - 1; $i += 2) {
							$x = is_object($ref[$i]) ? $ref[$i]->cc_get_output($ref[$i + 1]) : null;
							if ($x) {
								return $x;
							}
						}
						return false;

					case ':nor':
						for ($i = 1; $i < $n - 1; $i += 2) {
							if (is_object($ref[$i]) ? $ref[$i]->cc_get_output($ref[$i + 1]) : null) {
								return false;
							}
						}
						return true;

					case ':and':
						for ($i = 1; $i < $n - 1; $i += 2) {
							if (!(is_object($ref[$i]) ? $ref[$i]->cc_get_output($ref[$i + 1]) : null)) {
								return false;
							}
						}
						return true;

					case ':not':
					case ':nand':
						for ($i = 1; $i < $n - 1; $i += 2) {
							if (!(is_object($ref[$i]) ? $ref[$i]->cc_get_output($ref[$i + 1]) : null)) {
								return true;
							}
						}
						return false;

					case ':merge':
						$a = array();
						for ($i = 1; $i < $n - 1; $i += 2) {
							$a[] = (array) (is_object($ref[$i]) ? $ref[$i]->cc_get_output($ref[$i + 1]) : null);
						}
						return call_user_func('array_merge', $a);

					case ':array':
					case ':filter':
					case ':max':
					case ':min':
					case ':product':
					case ':sum':
					case ':unique':
						$a = array();
						for ($i = 1; $i < $n - 1; $i += 2) {
							$a[] = is_object($ref[$i]) ? $ref[$i]->cc_get_output($ref[$i + 1]) : null;
						}
						switch ($ref[0]) {
							case ':array':    return $a;
							case ':filter':   return array_filter($a);
							case ':max':      return max($a);
							case ':min':      return min($a);
							case ':product':  return array_product($a);
							case ':sum':      return array_sum($a);
							case ':unique':   return array_unique($a);
						}

					default:
						error_msg('%s: Input "%s" requires unknown operator "%s"!', $this->block_name(), $name, $ref[0]);
						$this->status = self::FAILED;
						return null;
				}
			}
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


	// collect values from numeric inputs - works well with vsprintf()
	final protected function collect_numeric_inputs()
	{
		$real_inputs = $this->input_names();
		$virtual_cnt = count($real_inputs) - count($this->inputs);
		$vals = array_pad(array(), $virtual_cnt, null);

		foreach ($real_inputs as $in) {
			if (is_numeric($in) && $in > 0) {
				$vals[$in - 1] = $this->in($in);
			}
		}

		return $vals;
	}


	// set value to output
	final protected function out($name, $value)
	{
		if (array_key_exists($name, $this->outputs) || array_key_exists('*', $this->outputs)) {
			$this->output_cache[$name] = & $value;
		} else {
			error_msg('%s: Output "%s" does not exist!', $this->block_name(), $name);
		}
	}


	// set all output values (keys are output names)
	final protected function out_all($values)
	{
		$this->output_cache = & $values;
	}


	// forward output from another block
	final protected function out_forward($name, $source_block, $source_name = null)
	{
		if (!array_key_exists($name, $this->outputs) && !array_key_exists('*', $this->outputs)) {
			error_msg('%s: Output "%s" does not exist!', $this->block_name(), $name);
		} else if ($source_name === null && is_array($source_block)) {
			$this->forward_list[$name] = $source_block;
		} else {
			$this->forward_list[$name] = array($source_block, $source_name);
		}
	}


	// list all visible block names already in cascade (for debugging only)
	final public function visible_block_names()
	{
		$m = $this;
		$names = $this->cascade_controller->root_namespace_block_names();

		while ($m && $m->namespace) {
			$names = array_merge($names, array_keys($m->namespace));
			$m = $m->parent;
		}

		return $names;
	}


	// add output object to template subsystem
	final protected function template_add($id_suffix, $template, $data = array())
	{
		$this->template_add_to_slot($id_suffix, null, null, $template, $data);
	}


	// add output object to template subsystem (with slot and weight)
	final protected function template_add_to_slot($id_suffix, $slot, $weight, $template, $data = array())
	{
		$id = $id_suffix === null ? $this->full_id() : $this->full_id().'_'.$id_suffix;
		$t = $this->context->get_template_engine();
		$t->add_object($id, $slot === null ? $this->in('slot') : $slot,
				($weight === null ? $this->in('slot_weight') : $weight) + $this->slot_weight_penalty,
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


	// add block to cascade
	final protected function cascade_add($id, $block, $force_exec = null, $connections = array(), $context = null)
	{
		$this->cascade_errors = array();
		return $this->cascade_controller->add_block($this, $id, $block, $force_exec, $connections,
				$context === null ? $this->context : $context,
				$this->cascade_errors);
	}


	// add blocks to cascade from parsed inifile
	final protected function cascade_add_from_ini($parsed_ini_with_sections, $context = null)
	{
		$this->cascade_errors = array();
		return $this->cascade_controller->add_blocks_from_ini($this, $parsed_ini_with_sections,
				$context === null ? $this->context : $context,
				$this->cascade_errors);
	}


	// get errors from cascade controller (errors can occur when cascade_add or cascade_add_from_ini is called)
	final public function cascade_get_errors()
	{
		return $this->cascade_errors;
	}


	/****************************************************************************
	 *	Authentication & Authorization
	 */

	/* Get auth object from cascade controller */
	final public function auth()
	{
		return $this->cascade_controller->get_auth();
	}


	/* Security - Level 1: check if block is allowed before cascade controller loads it */
	final public function auth_is_block_allowed($block_name, & $details = null)
	{
		$auth = $this->cascade_controller->get_auth();

		// Return false if access should be denied and set $details to string with explanation.
		if ($auth !== null) {
			return $auth->is_block_allowed($block_name, $details);
		} else {
			// If there is no Auth object, everything is allowed
			return true;
		}
	}


	/* Security - Level 2: check permissions to specified entity */
	final public function auth_check_item(& $item, & $details = null)
	{
		$auth = $this->cascade_controller->get_auth();

		if ($auth !== null) {
			return $auth->check_item($this->block_name, $item, $details);
		} else {
			// If there is no Auth object, everything is allowed
			return true;
		}
	}


	/* Security - Level 2: check permissions to specified entity */
	final public function auth_add_condition($block_name, & $query, $options = array())
	{
		$auth = $this->cascade_controller->get_auth();

		if ($auth !== null) {
			return $auth->add_condition($this->block_name, $query, $options = array());
		} else {
			// If there is no Auth object, do nothing
			return true;
		}
	}
}

