<?php
/*
 * Copyright (c) 2010, Josef Kufner  <jk@frozen-doe.net>
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

namespace Cascade\Core;

/**
 * Base block infrastructure common to all blocks.
 */
abstract class Block
{

	/**
	 * \name Block states
	 *
	 * @image html  block-states.png
	 * @image latex block-states.pdf
	 * @{
	 */
	const QUEUED   = 0x00;  ///< The block is waiting for execution.
	const RUNNING  = 0x01;	///< The block or its dependencies are being evaluated.
	const ZOMBIE   = 0x02;	///< The block is finished, exist only to maintain its outputs.
	const DISABLED = 0x04;  ///< The block is disabled, its 'enable' input is false.
	const FAILED   = 0x08;	///< The block has failed, because its inputs cannot be connected,
				///< or its dependencies cannot be solved, or it threw an exception.
	/** @} */


	/**
	 * Default value of 'force_exec' flag. It is used only when adding this 
	 * block into cascade (not when calling CascadeController::addBlock() 
	 * from this class). This is used when force_exec arg. is null (or not 
	 * set at all).
	 */
	const force_exec = false;

	/**
	 * Map block state constants to strings.
	 */
	public static $STATUS_NAMES = array(
		self::QUEUED   => 'queued',
		self::RUNNING  => 'running',
		self::ZOMBIE   => 'zombie',
		self::DISABLED => 'disabled',
		self::FAILED   => 'failed',
	);


	private $id;
	private $full_id;
	private $cascade_controller;
	private $block_name;
	private $slot_weight_penalty;		///< Guarantees keeping order of output objects

	private $status = self::QUEUED;
	private $status_message = null;		///< Reason, why is block in this status. Ususally description of error.
	private $output_cache = array();
	private $forward_list = array();
	private $execution_time = null;		///< Time [ms] spent in main()
	private $timestamp_create = null;	///< When was block created ?
	private $timestamp_start = null;	///< When block execution started ?
	private $timestamp_finish = null;	///< When block execution finished ?

	private $parent = null;			///< Parent block
	private $namespace = array();		///< References to other blocks

	private $cascade_errors = array();	///< List of errors received from cascade controller


	/**
	 * List of inputs and their default values
	 */
	protected $inputs = array(
		// 'input_name' => 'default_value',
		// '*' => 'default_value',
	);

	/**
	 * List of default input connections (all inputs mentioned here must be present in $inputs array too)
	 */
	protected $connections = array(
		// 'input_name1' => array('block', 'output'),
		// 'input_name2' => array(':input_function', 'block1', 'output_of_block1', 'block2', 'output_of_block2', ...),
	);

	/**
	 * List of outputs (no default values)
	 */
	protected $outputs = array(
		// 'output_name' => true,
		// '*' => true,
	);


	/**
	 * Global context. A not-so-ugly way to make global things nice.
	 */
	protected $context;


	/**
	 * Body of the block. The method called when block is evaluated.
	 */
	abstract public function main();


	// Get unprepared output (called after main; once or never for each output).
	// Remember to call $this->context->updateEnviroment() if required.
	//abstract public function getOutput();


	/**
	 * Get block id without namespaces.
	 */
	final public function id()
	{
		return $this->id;
	}


	/**
	 * Get full block id (including all parent namespaces).
	 */
	final public function fullId()
	{
		return $this->full_id;
	}


	/**
	 * Get block type
	 */
	final public function blockName()
	{
		return $this->block_name;
	}


	/**
	 * Get block state
	 */
	final public function status()
	{
		return $this->status;
	}


	/**
	 * Get status message - usually contains an error message why block failed.
	 */
	final public function statusMessage()
	{
		return $this->status_message;
	}


	/**
	 * Get CascadeController which owns this block.
	 */
	final public function getCascadeController()
	{
		return $this->cascade_controller;
	}


	/**
	 * Get profiler data
	 *
	 * Returns array of timestams, when block has been created, started and 
	 * finished.
	 */
	final public function getTimestamps()
	{
		return array($this->timestamp_create, $this->timestamp_start, $this->timestamp_finish);
	}


	/**
	 * List all visible block names already in cascade (for debugging only)
	 *
	 * @bug This does not really work. It is only hotfix for Graphviz diagrams.
	 */
	final public function visibleBlockNames()
	{
		$m = $this;
		$names = $this->cascade_controller->rootNamespaceBlockNames();

		while ($m && $m->namespace) {
			$names = array_merge($names, array_keys($m->namespace));
			$m = $m->parent;
		}

		return $names;
	}


	/************************************************************************//**
	 * @}
	 * \name	Part of Cascade Controller
	 *
	 * These methods should not be used by anything except the CascadeController.
	 * Consider them private.
	 * @{
	 */

	// "constructor" -- called imediately after block creation
	final public function cc_init($parent, $id, $full_id, $cascade_controller, $block_name, $context, $initial_status = self::QUEUED, $initial_status_message = null)
	{
		// basic init
		$this->id = $id;
		$this->parent = $parent;
		$this->full_id = $full_id;
		$this->cascade_controller = $cascade_controller;
		$this->block_name = $block_name;
		$this->context = $context;
		$this->timestamp_create = $this->cascade_controller->currentStep();
		$this->slot_weight_penalty = 1.0 - 100.0 / ($this->timestamp_create + 100.0); // lim -> inf = 1
		$this->status = $initial_status;
		$this->status_message = $initial_status_message;

		// add common inputs
		$this->inputs['enable'] = true;
	}


	final public function cc_connect(array $in_connections, array $in_values)
	{
		$wildcard = array_key_exists('*', $this->inputs);

		/* replace defaults */
		$new_connections = $in_connections + array_diff_key($this->connections, $in_values);
		$new_inputs = $in_values + $this->inputs;

		/* check connections */
		if (!$wildcard) {
			$new_all = $new_inputs + $new_connections;
			if (count($new_all) != count($this->inputs)) {
				/* Connected non-existent inputs */
				foreach(array_diff_key($new_all, $this->inputs) as $in => $out) {
					error_msg('Input "%s:%s" does not exist!', $this->id, $in);
				}
				return false;
			}
		}

		$this->connections = $new_connections;
		$this->inputs = $new_inputs;
		return true;
	}


	final public function cc_registerBlock($block)
	{
		$id = $block->id();

		if (isset($this->namespace[$id])) {
			error_msg('Duplicate ID "%s" in the namespace of %s', $id, $this->fullId());
			return false;
		} else {
			$this->namespace[$id] = $block;
			return true;
		}
	}


	final public function cc_resolveBlockName($block_name)
	{
		$path = explode('.', $block_name);
		$start_name = array_shift($path);

		// Go out
		if ($start_name == '') {
			$m = $this->cascade_controller->resolveBlockName(array_shift($path));
		} else if ($start_name == 'parent') {
			$m = $this->parent;
		} else {
			$t = $this;
			while ($t && !isset($t->namespace[$start_name])) {
				$t = $t->parent;
			}
			$m = $t !== null ? $t->namespace[$start_name] : $this->cascade_controller->resolveBlockName($start_name);
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
				debug_msg('%s: Skipping failed block "%s"', $this->blockName(), $this->id());
				return false;

			case self::RUNNING:
				error_msg('Circular dependency detected while executing block "%s" !', $this->id);
				return false;
		}

		$this->timestamp_start = $this->cascade_controller->currentStep();
		$this->status = self::RUNNING;
		debug_msg('%s: Preparing block "%s" (t = %d)', $this->blockName(), $this->fullId(), $this->timestamp_start);

		/* dereference block names and build dependency list */
		$dependencies = array();
		foreach($this->connections as $in => & $out) {
			if (is_array($out)) {
				// connect to output(s)
				$n = count($out);
				if ($n == 0) {
					error_msg('%s: Can\'t connect inputs -- connection for input "%s" of block "%s" is not defined!',
							$this->blockName(), $in, $this->fullId());
					$this->status = self::FAILED;
					$this->status_message = 'In: No source specified';
				} else {
					for ($i = $out[0][0] == ':' ? 1 : 0; $i < $n - 1; $i += 2) {
						$block_name = $out[$i];
						$block_out = $out[$i + 1];
						$m = $this->cc_resolveBlockName($block_name);
						if (!$m) {
							error_msg('%s: Can\'t connect inputs -- block "%s" not found!', $this->blockName(), $block_name);
							$this->status = self::FAILED;
							$this->status_message = 'In: Block not found';
						} else if (isset($m->outputs[$block_out]) || isset($m->outputs['*'])) {
							$dependencies[$m->fullId()] = $m;
							$out[$i] = $m;
						} else {
							error_msg('Can\'t connect input "%s:%s" to "%s:%s" !',
									$this->fullId(), $in, $block_name, $block_out);
							$this->status = self::FAILED;
							$this->status_message = 'In: Bad connection';
						}
					}
				}
			}
		}

		/* abort if failed */
		if (!$this->status == self::FAILED) {
			error_msg('%s: Failed to prepare block "%s"', $this->blockName(), $this->fullId());
			$this->timestamp_finish = $this->cascade_controller->currentStep();
			return false;
		}

		/* execute dependencies */
		// TODO: Lze spoustet zavislosti az na vyzadani a ne predem vse ?
		//		-- Pokud ano, tak bude potreba poresit preposilani vystupu.
		foreach($dependencies as & $d) {
			if (!$d->cc_execute()) {
				$this->status = self::FAILED;
				$this->status_message = 'Unsolved dependencies';
				break;
			}
		}

		/* abort if failed */
		if ($this->status == self::FAILED) {
			error_msg('%s: Failed to solve dependencies of block "%s"', $this->blockName(), $this->fullId());
			$this->timestamp_finish = $this->cascade_controller->currentStep();
			return false;
		}

		/* do not execute if disabled */
		if (!$this->in('enable')) {
			debug_msg('%s: Skipping disabled block "%s"', $this->blockName(), $this->fullId());
			$this->status = self::DISABLED;
			$this->timestamp_finish = $this->cascade_controller->currentStep();
			return true;
		}

		/* execute main */
		debug_msg('%s: Starting block "%s"', $this->blockName(), $this->fullId());
		$this->context->updateEnviroment();
		$t = microtime(TRUE);
		try {
			$this->main();
			$this->status = self::ZOMBIE;
		}
		catch (\Exception $ex) {
			$this->status = self::FAILED;
			$this->status_message = 'Exception: '.get_class($ex);
			error_msg("%s: Uncaught exception %s in block \"%s\": \"%s\" in file %s on line %d.\n%s",
				$this->blockName(), get_class($ex), $this->fullId(), $ex->getMessage(),
				$ex->getFile(), $ex->getLine(), $ex->getTraceAsString());
			$this->cascade_controller->logFailedBlockException($this->fullId(), $this->blockName(), $ex);
			return false;
		}
		$this->execution_time = (microtime(TRUE) - $t) * 1000;
		$this->timestamp_finish = $this->cascade_controller->currentStep();

		/* execute & evaluate forwarded outputs */
		// TODO - nebylo by lepsi to udelat az na pozadani ?
		//	-- ne, nebylo. Prilis by se to zeslozitilo a rezie by byla prilis velika.
		//	  Lepe bude pockat az se budou resit zavislosti na pozadani
		//	  a udelat to pri tom.
		foreach($this->forward_list as $name => & $src) {
			$n = count($src);
			if ($n == 0) {
				error_msg('%s: Can\'t forward output to "%s:%s" -- no source output defined!', $this->blockName(), $this->fullId(), $name);
				$this->status = self::FAILED;
				$this->status_message = 'OutFwd: No source';
			} else {
				for ($i = $src[0][0] == ':' ? 1 : 0; $i < $n - 1; $i += 2) {
					$block_name = $src[$i];
					$block_out = $src[$i + 1];
					$b = $this->cc_resolveBlockName($block_name);
					if (!$b) {
						error_msg('%s: Can\'t forward output to "%s:%s" -- block "%s" not found!',
								$this->blockName(), $this->fullId(), $name, $block_name);
						$this->status = self::FAILED;
						$this->status_message = 'OutFwd: Block not found';
					} else if (isset($b->outputs[$block_out]) || isset($b->outputs['*'])) {
						if (is_object($b) && !$b->cc_execute()) {
							error_msg('Can\'t forward output to "%s:%s" -- block "%s" has failed!',
								$this->fullId(), $name, $block_name);
						}
						$src[$i] = $b;
					} else {
						error_msg('Can\'t forward output to "%s:%s" from "%s:%s" !',
							$this->fullId(), $name, $block_name, $block_out);
						$this->status = self::FAILED;
						$this->status_message = 'OutFwd: Bad connection';
					}
				}
				$this->output_cache[$name] = $this->collectOutputs($src, $name);
			}
		}

		return true;
	}


	final public function cc_getOutput($name)
	{
		if (array_key_exists($name, $this->output_cache)) {
			// cached output
			return $this->output_cache[$name];
		} else if ($this->status == self::DISABLED || $this->status == self::FAILED) {
			return null;
		} else {
			// create output and cache it
			if (method_exists($this, 'getOutput')) {
				$value = $this->getOutput($name);
			} else {
				return null;
			}
			$this->output_cache[$name] = $value;
			return $value;
		}
	}


	final public function cc_connections()
	{
		return $this->connections;
	}


	final public function cc_outputs()
	{
		return array_keys($this->output_cache + $this->outputs);
	}

	final public function cc_outputCache()
	{
		return $this->output_cache;
	}

	final public function cc_forwardedOutputs()
	{
		return $this->forward_list;
	}


	final public function cc_outputExists($name, $accept_wildcard = true)
	{
		return array_key_exists($name, $this->output_cache)
			|| array_key_exists($name, $this->outputs)
			|| ($accept_wildcard && array_key_exists('*', $this->outputs));
	}


	final public function cc_getNamespace()
	{
		return $this->namespace;
	}


	final public function cc_executionTime()
	{
		return $this->execution_time;
	}


	final public function cc_dumpNamespace($level = 1)
	{
		$str = '';
		$indent = str_repeat('. ', $level);
		foreach ($this->namespace as $name => $m) {
			$str .= $indent.$name." (".$m->blockName().")\n".($name != 'self' && $name != 'parent' && $m ? $m->cc_dumpNamespace($level + 1) : '');
		}
		return $str;
	}


	/* Describe block, it's inputs, outputs, ... */
	final public function cc_describeBlock()
	{
		return array(
			'block' => str_replace('__', '/', preg_replace('/^B_/', '', __CLASS__)),
			'force_exec' => self::force_exec,
			'inputs' => $this->inputs,
			'connections' => $this->connections,
			'outputs' => $this->outputs,
		);
	}


	/******************************************************************//**
	 * @}
	 * \name	Inputs and outputs
	 * @{
	 */


	/**
	 * Get value from input
	 */
	final protected function in($name)
	{
		// get input
		if (array_key_exists($name, $this->connections)) {
			// input is connected somewhere
			$ref = & $this->connections[$name];
		} else if (array_key_exists($name, $this->inputs)) {
			// input is set to value
			return $this->inputs[$name];
		} else if (array_key_exists('*', $this->connections)) {
			// unconnected wildcard, default is connection
			$ref = & $this->connections['*'];
		} else if (array_key_exists('*', $this->inputs)) {
			// unconnected wildcard, default is value
			return $this->inputs['*'];
		} else {
			// or fail
			error_msg('%s: Input "%s" is not defined!', $this->blockName(), $name);
			return null;
		}

		return $this->collectOutputs($ref, $name);
	}


	/**
	 * Get values from all inputs
	 */
	final protected function inAll()
	{
		$vals = array();
		foreach ($this->inputNames() as $in) {
			$vals[$in] = $this->in($in);
		}
		return $vals;
	}


	/**
	 * Get all input names, excluding common inputs (`enable` and `*`)
	 */
	final protected function inputNames()
	{
		return array_diff(array_keys($this->inputs + $this->connections), array(
				'*',
				'enable',
			));
	}


	/**
	 * Collect values from numeric inputs - works well with vsprintf()
	 *
	 * @deprecated Use template_format and inAll instead.
	 */
	final protected function collectNumericInputs()
	{
		$real_inputs = $this->inputNames();
		$virtual_cnt = count($real_inputs) - count($this->inputs);
		$vals = array_pad(array(), $virtual_cnt, null);

		foreach ($real_inputs as $in) {
			if (is_numeric($in) && $in > 0) {
				$vals[$in - 1] = $this->in($in);
			}
		}

		return $vals;
	}


	/**
	 * Set value to output
	 */
	final protected function out($name, $value)
	{
		if (array_key_exists($name, $this->outputs) || array_key_exists('*', $this->outputs)) {
			$this->output_cache[$name] = & $value;
		} else {
			error_msg('%s: Output "%s" does not exist!', $this->blockName(), $name);
		}
	}


	/**
	 * Set all output values
	 *
	 * Keys are output names. Outputs missing in $values are unset.
	 */
	final protected function outAll($values)
	{
		$this->output_cache = & $values;
	}


	/**
	 * Forward output from another block
	 *
	 * Output value of $source_block:$source_name output is copied to local output $name.
	 */
	final protected function outForward($name, $source_block, $source_name = null)
	{
		if (!array_key_exists($name, $this->outputs) && !array_key_exists('*', $this->outputs)) {
			error_msg('%s: Output "%s" does not exist!', $this->blockName(), $name);
		} else if ($source_name === null && is_array($source_block)) {
			$this->forward_list[$name] = $source_block;
		} else {
			$this->forward_list[$name] = array($source_block, $source_name);
		}
	}

	/**
	 * Calculate output value on demand. If some output is not always used 
	 * and is hard to compute, do not set output value in main(). Implement 
	 * this method instead.
	 *
	 * @fn public function getOutput($name)
	 */
	// public function getOutput($name)
	// {
	// }


	/******************************************************************//**
	 * @}
	 * \name	Template helpers
	 * @{
	 */


	/**
	 * Add output object to template subsystem
	 */
	final protected function templateAdd($id_suffix, $template, $data = array())
	{
		$this->templateAddToSlot($id_suffix, null, null, $template, $data);
	}


	/**
	 * Add output object to template subsystem (with slot and weight)
	 */
	final protected function templateAddToSlot($id_suffix, $slot, $weight, $template, $data = array())
	{
		$id = $id_suffix === null ? $this->fullId() : $this->fullId().'_'.$id_suffix;
		$t = $this->context->template_engine;
		$t->addObject($id, $slot === null ? $this->in('slot') : $slot,
				($weight === null ? $this->in('slot_weight') : $weight) + $this->slot_weight_penalty,
				$template, $data, $this->context);

	}


	/**
	 * Set page title
	 */
	final protected function templateSetPageTitle($title, $format = null)
	{
		$t = $this->context->template_engine;
		if ($title !== null) {
			$t->slotOptionSet('root', 'page_title', $title);
		}
		if ($format !== null) {
			$t->slotOptionSet('root', 'page_title_format', $format);
		}
	}


	/**
	 * Set output type
	 */
	final protected function templateSetType($type)
	{
		$t = $this->context->template_engine;
		$t->slotOptionSet('root', 'type', $type);
	}


	/**
	 * Set slot option
	 */
	final protected function templateOptionSet($slot, $option, $value)
	{
		$t = $this->context->template_engine;
		return $t->slotOptionSet($slot, $option, $value);
	}


	/**
	 * Append value to slot option (which is list)
	 */
	final protected function templateOptionAppend($slot, $option, $value)
	{
		$t = $this->context->template_engine;
		return $t->addSlotOption($slot, $option, $value);
	}


	/******************************************************************//**
	 * @}
	 * \name	Cascade manipulation helpers
	 * @{
	 */


	/**
	 * Add block to cascade
	 */
	final protected function cascadeAdd($id, $block, $force_exec = null, $in_connections = array(), $in_values = array(), $context = null)
	{
		$this->cascade_errors = array();
		return $this->cascade_controller->addBlock($this, $id, $block, $force_exec, $in_connections, $in_values,
				$context === null ? $this->context : $context,
				$this->cascade_errors);
	}


	/**
	 * Add array of blocks to cascade (calls cascadeAdd() for each item).
	 *
	 * Items in $array_of_blocks look like this:
	 *
	 *     array(
	 *         'block' => 'block/type',
	 *         'force_exec' => true/false/null,
	 *         'in_con' => array( ... like Block::$connections ... ),
	 *         'in_val' => array( ... like Block::$inputs ... ),
	 *     ),
	 *
	 * Unknown keys are ignored.
	 *
	 */
	final protected function cascadeAddFromArray($array_of_blocks, $context = null)
	{
		$this->cascade_errors = array();
		return $this->cascade_controller->addBlocksFromArray($this, $array_of_blocks,
				$context === null ? $this->context : $context,
				$this->cascade_errors);
	}


	/**
	 * Get errors from cascade controller (errors can occur when cascade_add or cascade_add_from_ini is called)
	 */
	final protected function cascadeGetErrors()
	{
		return $this->cascade_errors;
	}


	/******************************************************************//**
	 * @}
	 * \name	Authentication & Authorization helpers
	 * @{
	 */


	/**
	 * Get auth object from cascade controller
	 */
	final protected function auth()
	{
		return $this->cascade_controller->getAuth();
	}


	/**
	 * Check if block is allowed before cascade controller loads it
	 */
	final protected function authIsBlockAllowed($block_name, & $details = null)
	{
		$auth = $this->cascade_controller->getAuth();

		// Return false if access should be denied and set $details to string with explanation.
		if ($auth !== null) {
			return $auth->isBlockAllowed($block_name, $details);
		} else {
			// If there is no Auth object, everything is allowed
			return true;
		}
	}

	/** @} */


	private function collectOutputs(& $ref, $name)
	{
		// read input
		if (is_array($ref)) {
			// read from output
			$n = count($ref);
			if ($n == 2) {
				// single output
				return is_object($ref[0]) ? $ref[0]->cc_getOutput($ref[1]) : null;
			} else {
				// use specified function to create one value from multiple outputs
				switch ($ref[0]) {
					case ':or':
						for ($i = 1; $i < $n - 1; $i += 2) {
							$x = is_object($ref[$i]) ? $ref[$i]->cc_getOutput($ref[$i + 1]) : null;
							if ($x) {
								return $x;
							}
						}
						return false;

					case ':nor':
						for ($i = 1; $i < $n - 1; $i += 2) {
							if (is_object($ref[$i]) ? $ref[$i]->cc_getOutput($ref[$i + 1]) : null) {
								return false;
							}
						}
						return true;

					case ':and':
						for ($i = 1; $i < $n - 1; $i += 2) {
							if (!(is_object($ref[$i]) ? $ref[$i]->cc_getOutput($ref[$i + 1]) : null)) {
								return false;
							}
						}
						return true;

					case ':not':
					case ':nand':
						for ($i = 1; $i < $n - 1; $i += 2) {
							if (!(is_object($ref[$i]) ? $ref[$i]->cc_getOutput($ref[$i + 1]) : null)) {
								return true;
							}
						}
						return false;

					case ':merge':
						$a = array();
						for ($i = 1; $i < $n - 1; $i += 2) {
							$a[] = (array) (is_object($ref[$i]) ? $ref[$i]->cc_getOutput($ref[$i + 1]) : null);
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
							$a[] = is_object($ref[$i]) ? $ref[$i]->cc_getOutput($ref[$i + 1]) : null;
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
						error_msg('%s: The "%s" requires unknown operator "%s"!', $this->blockName(), $name, $ref[0]);
						$this->status = self::FAILED;
						return null;
				}
			}
		} else {
			// ref is constant, but it can't be!
			error_msg('%s: The "%s" is messed up! Connection definition must be an array, but %s given.',
				$this->blockName(), $name, var_export($ref[0], true));
			return null;
		}
	}

}

