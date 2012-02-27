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

class M_core__in__path extends Module {

	protected $inputs = array(
	);

	protected $outputs = array(
		'last' => true,
		'depth' => true,
		'path' => true,
		'server' => true,
		'*' => true,
	);

	public function main()
	{
		global $_SERVER;

		$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

		// get uri, explode it to array and calculate depth
		if ($uri == '/' || $uri == '') {
			$uri = '/';
			$path = array();
			$depth = 0;
		} else {
			$uri = rtrim($uri, '/');
			$path = explode('/', ltrim($uri, '/'));
			$depth = count($path);
		}

		// set outputs
		if ($depth >= 1) {
			$path['last'] = & $path[count($path) - 1];
		} else {
			$path['last'] = null;
		}
		$path['depth'] = & $depth;
		$path['path'] = & $uri;
		$path['server'] = & $_SERVER['SERVER_NAME'];
		$this->out_all($path);
	}
}

