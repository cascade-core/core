<?php
/*
 * Copyright (c) 2010, 2014   Josef Kufner  <jk@frozen-doe.net>
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
 * Block class for proxy blocks created by JsonBlockStorage.
 *
 * Load JSON file and insert its content to cascade. Used filename is determined
 * from block name, becouse cascade controller uses this block when block
 * specified by JSON file should be inserted.
 *
 * Section "copy-inputs" specifies which inputs will be copied without change
 * to which outputs. Keys are outputs, values are inputs.
 *
 * Section "outputs" specifies values on outputs and/or output forwarding.
 * Syntax is same as when connecting inputs, but keys are output names.
 *
 * There are three policies which specify behaviour when block is denied. They
 * are specified in "policy" section in form: policy[] = list of blocks.
 * See policy handlers section for description of policies.
 */
class ProxyBlock extends Block implements IShebangHandler {

	/**
	 * Block inputs.
	 */
	protected $inputs = array(
		'*' => null,
	);

	/**
	 * Block outputs.
	 */
	protected $outputs = array(
		'*' => true,
	);

	/**
	 * Block must be always executed.
	 */
	const force_exec = true;


	private $conf = null;


	/**
	 * Create block proxy.
	 */
	public static function createFromShebang($block_config, $shebang_config, Context $context, $block_type)
	{
		$block = new self();
		$block->conf = $block_config;
		return $block;
	}


	/**
	 * @copydoc Block::main
	 */
	public function main()
	{
		// Get configuration
		$conf = $this->preprocessConfiguration($this->conf);
		unset($this->conf);	// no need for this anymore

		// Check if there is block definition
		if (!isset($conf['blocks'])) {
			error_msg('%s: Missing "blocks" section in block configuration.', $this->blockName());
			return false;
		}

		// Policy checks
		if (isset($conf['policy'])) {
			// Call policy methods. You may add policies by
			// inheriting from and extending this class. Then use
			// block-map in core.json.php.
			foreach ($conf['policy'] as $policy => $arg) {
				$method = 'policy__'.$policy;
				if (method_exists($this, $method)) {
					if ($this->$method($arg, $conf) == false) {
						return;
					}
				}
			}
		}

		// Fill cascade
		$done = $this->cascadeAddFromArray($conf['blocks']);

		// Copy inputs
		if (isset($conf['copy-inputs'])) {
			foreach ($conf['copy-inputs'] as $out => $in) {
				$this->out($out, $this->in($in));
			}
		}

		// Set/Forward outputs
		if (isset($conf['outputs'])) {
			foreach ($conf['outputs'] as $out => $src) {
				if (is_array($src)) {
					list($src_mod, $src_out) = explode(':', $src[0]);
					$this->outForward($out, $src_mod, $src_out);
				} else {
					$this->out($out, $src);
				}
			}
		}

		// Forward outputs (deprecated)
		if (isset($conf['forward-outputs'])) {
			foreach ($conf['forward-outputs'] as $out => $src) {
				list($src_mod, $src_out) = explode(':', $src);
				$this->outForward($out, $src_mod, $src_out);
			}
		}

		$this->out('done', $done);
	}


	/**
	 * Preprocess configuration before it is interpreted by proxy itself.
	 *
	 * Receives original configuration and returns modified version.
	 */
	protected function preprocessConfiguration($conf)
	{
		return $conf;
	}


	/******************************************************************//**
	 * @}
	 * \name	Policy handlers
	 * @{
	 */

	/**
	 * Deny entire block if specified block is not available.
	 */
	final protected function policy__require_block($arg, & $conf)
	{
		foreach ((array) $arg as $rq_block) {
			if (!$this->authIsBlockAllowed($rq_block)) {
				debug_msg('Required block "%s" is not allowed. Aborting.', $rq_block);
				return;
			}
		}
		return true;
	}


	/**
	 * Silently replace denied blocks with dummy. Useful when other blocksare connected to these.
	 */
	final protected function policy__dummy_if_denied($arg, & $conf)
	{
		foreach ((array) $arg as $id) {
			$m = @ $conf['block:'.$id]['.block'];	// FIXME
			if ($m !== null && !$this->authIsBlockAllowed($m)) {
				debug_msg('Replacing block "%s" (%s) with dummy.', $id, $m);
				$conf['block:'.$id]['.block'] = 'core/dummy';
			}
		}
		return true;
	}


	/**
	 * Silently skip denied blocks. When nothing needs these.
	 */
	final protected function policy__skip_if_denied($arg, & $conf)
	{
		foreach ((array) $arg as $id) {
			$m = @ $conf['block:'.$id]['.block'];	// FIXME
			if ($m !== null && !$this->authIsBlockAllowed($m)) {
				debug_msg('Skipping block "%s" (%s).', $id, $m);
				unset($conf['block:'.$id]);
			}
		}
		return true;
	}
}

