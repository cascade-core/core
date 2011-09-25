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

class M_core__ini__router extends Module {

	protected $inputs = array(
		'path' => null,
		'config' => array(),
		'canonize_path' => true,
	);

	protected $outputs = array(
		'*' => true,
	);


	public function main()
	{
		// load config
		$conf = parse_ini_file($this->in('config'), TRUE);
		if ($conf === FALSE) {
			return;
		}

		// get current path
		$uri_path = $this->in('path');
		if ($uri_path == null) {
			$uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
			$orig_uri_path = $uri_path;
		} else {
			$orig_uri_path = false;
		}

		// have slash at the end ?
		$have_last_slash = $uri_path != '/' && substr($uri_path, -1) == '/';

		// normalize current path and get fragments
		$uri_path = rtrim($uri_path, '/');
		$uri_path = preg_replace('/\/+/', '/', $uri_path);
		$path = explode('/', $uri_path);
		if ($uri_path == '') {
			$uri_path = '/';
		}

		// if normalized does not match original, redirect and do not set outputs
		if ($orig_uri_path !== false && $this->in('canonize_path')
				&& ($have_last_slash ? $orig_uri_path != $uri_path.'/' : $orig_uri_path != $uri_path)
				&& $_SERVER['REQUEST_METHOD'] == 'GET')
		{
			//dump('redirect to:', $have_last_slash ? $uri_path.'/' : $uri_path, 'from:', $orig_uri_path);
			$this->template_option_set('root', 'redirect_url', $have_last_slash ? $uri_path.'/' : $uri_path);
			$this->out('done', false);
			return;
		}

		// default args
		if (array_key_exists('#', $conf)) {
			$defaults = $conf['#'];
			unset($conf['#']);
		} else {
			$defaults = array();
		}

		// match rules one by one
		foreach($conf as $mask => $args) {
			$m = explode('/', rtrim($mask, '/'));
			$last = end($m);

			// check length (quickly drop wrong path)
			if ($last != '**' ? count($m) != count($path) : count($m) - 1 > count($path)) {
				continue;
			}

			// compare fragments
			for ($i = 0; $i < count($m); $i++) {
				if (@$m[$i][0] == '$') {
					// variable - match anything
					$a = substr($m[$i], 1);
					$args[$a] = $path[$i];
				} else if ($i == count($m) - 1 && $m[$i] == '**') {
					// last part is '**' -- copy tail and finish
					$args['path_tail'] = array_slice($path, $i);
					$i = count($m);
					break;
				} else if ($m[$i] != $path[$i]) {
					// fail
					break;
				}
			}
			if ($i < count($m)) {
				// match failed
				continue;
			}

			// match found
			debug_msg("Matched rule [%s]", $mask);
			$this->out_all(array_merge($defaults, $args));
			$this->out('done', true);
			$this->out('path', $uri_path);
			$this->out('no_match', false);
			return;
		}

		// no match
		debug_msg("No rule matched!");
		$this->out('done', false);
		$this->out('path', $uri_path);
		$this->out('no_match', true);
	}
}

