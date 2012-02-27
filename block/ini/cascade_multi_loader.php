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

class B_core__ini__cascade_multi_loader extends Block
{
	const force_exec = true;

	protected $inputs = array(
		'list' => array(),		// array of names
		'filename' => array(),		// format string for sprintf(filename, list[i])
		'separe_namespaces' => false,	// if true, each loaded file will get its own namespace using core/ini/cascade_loader
	);

	protected $outputs = array(
		'*' => true,
		'done' => true,
	);


	public function main()
	{
		$filename = $this->in('filename');
		$separe_namespaces = $this->in('separe_namespaces');

		$n = 0;
		$done = true;

		foreach ((array) $this->in('list') as $i) {
			$fn = sprintf($filename, $i);

			$config = parse_ini_file($fn, TRUE);

			if ($config !== FALSE) {
				if ($separe_namespaces) {
					$cfg_output = 'config_'.$n;
					$id = preg_replace('/[^a-zA-Z0-9_]+/', '_', $i);
					$this->out($cfg_output, $config);
					$this->cascade_add($id, 'core/ini/cascade_loader', true, array(
							'items' => array('parent', $cfg_output),
						));
				} else {
					$this->cascade_add_from_ini($config);
				}
			} else {
				$done = false;
			}

			$n++;
		}

		$this->out('done', $done);
	}
}

