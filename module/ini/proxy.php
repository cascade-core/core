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

class M_core__ini__proxy extends Module {

	protected $inputs = array(
		'*' => null,
	);

	protected $outputs = array(
		'*' => true,
	);


	public function main()
	{
		$m = $this->module_name();
		$filename = get_module_filename($m, '.ini.php');

		$conf = parse_ini_file($filename, TRUE);
		if ($conf === FALSE) {
			return;
		}

		// Policy checks
		if (isset($conf['policy'])) {
			// Call policy methods. You may add policies by
			// inheriting from and extending this class. Then use
			// module-map in core.ini.php.
			foreach ($conf['policy'] as $policy => $arg) {
				$method = 'policy__'.$policy;
				if (method_exists($this, $method)) {
					if ($this->$method($arg, $conf) == false) {
						return;
					}
				}
			}
		}

		// Fill pipeline
		$done = $this->pipeline_add_from_ini($conf);

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


	final protected function policy__require_module($arg, & $conf)
	{
		// Check required modules
		foreach ((array) $arg as $rq_module) {
			if (!$this->context->is_allowed($rq_module)) {
				debug_msg('Required module "%s" is not allowed. Aborting.', $rq_module);
				return;
			}
		}
		return true;
	}


	final protected function policy__dummy_if_denied($arg, & $conf)
	{
		// Silently replace denied modules with dummy. Useful when other modulesare connected to these.
		foreach ((array) $arg as $id) {
			$m = @ $conf['module:'.$id]['.module'];
			if ($m !== null && !$this->context->is_allowed($m)) {
				debug_msg('Replacing module "%s" (%s) with dummy.', $id, $m);
				$conf['module:'.$id]['.module'] = 'core/dummy';
			}
		}
		return true;
	}

	final protected function policy__skip_if_denied($arg, & $conf)
	{
		// Silently skip denied modules. When nothing needs these.
		foreach ((array) $arg as $id) {
			$m = @ $conf['module:'.$id]['.module'];
			if ($m !== null && !$this->context->is_allowed($m)) {
				debug_msg('Skipping module "%s" (%s).', $id, $m);
				unset($conf['module:'.$id]);
			}
		}
		return true;
	}
}

