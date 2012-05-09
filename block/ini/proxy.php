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

/**
 * Load INI file and insert its content to cascade. Used filename is determined
 * from block name, becouse cascade controller uses this block when block
 * specified by INI file should be inserted.
 *
 * Section "copy-inputs" specifies which inputs will be copied without change
 * to which outputs. Keys are outputs, values are inputs.
 *
 * Section "outputs" specifies values on outputs and/or output forwarding.
 * Syntax is same as when connecting inputs, but keys are output names.
 *
 * There are three policies which specify behaviour when block is denied. They
 * are specified in "policy" section in form: policy[] = list of blocks.
 *
 * Policy "require_block" says, that all specified blocks must be accessible,
 * or else no block is inserted to cascade.
 *
 * Policy "dummy_if_denied" says, that denied blocks are silently replaced
 * by core/dummy.
 *
 * Policy "skip_if_denied" says, that denied blocks are silently skipped.
 *
 */

class B_core__ini__proxy extends Block {

	protected $inputs = array(
		'*' => null,
	);

	protected $outputs = array(
		'*' => true,
	);

	const force_exec = true;


	public function main()
	{
		$m = $this->block_name();
		$filename = get_block_filename($m, '.ini.php');

		$conf = parse_ini_file($filename, TRUE);
		if ($conf === FALSE) {
			return;
		}

		// Policy checks
		if (isset($conf['policy'])) {
			// Call policy methods. You may add policies by
			// inheriting from and extending this class. Then use
			// block-map in core.ini.php.
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
		$done = $this->cascade_add_from_ini($conf);

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
					$this->out_forward($out, $src_mod, $src_out);
				} else {
					$this->out($out, $src);
				}
			}
		}

		// Forward outputs (deprecated)
		if (isset($conf['forward-outputs'])) {
			foreach ($conf['forward-outputs'] as $out => $src) {
				list($src_mod, $src_out) = explode(':', $src);
				$this->out_forward($out, $src_mod, $src_out);
			}
		}

		$this->out('done', $done);
	}


	final protected function policy__require_block($arg, & $conf)
	{
		// Check required blocks
		foreach ((array) $arg as $rq_block) {
			if (!$this->auth_is_block_allowed($rq_block)) {
				debug_msg('Required block "%s" is not allowed. Aborting.', $rq_block);
				return;
			}
		}
		return true;
	}


	final protected function policy__dummy_if_denied($arg, & $conf)
	{
		// Silently replace denied blocks with dummy. Useful when other blocksare connected to these.
		foreach ((array) $arg as $id) {
			$m = @ $conf['block:'.$id]['.block'];
			if ($m !== null && !$this->auth_is_block_allowed($m)) {
				debug_msg('Replacing block "%s" (%s) with dummy.', $id, $m);
				$conf['block:'.$id]['.block'] = 'core/dummy';
			}
		}
		return true;
	}

	final protected function policy__skip_if_denied($arg, & $conf)
	{
		// Silently skip denied blocks. When nothing needs these.
		foreach ((array) $arg as $id) {
			$m = @ $conf['block:'.$id]['.block'];
			if ($m !== null && !$this->auth_is_block_allowed($m)) {
				debug_msg('Skipping block "%s" (%s).', $id, $m);
				unset($conf['block:'.$id]);
			}
		}
		return true;
	}
}

