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

class PipelineController {

	private $queue = array();	// moduly cekajici na provedeni
	private $modules = array();	// moduly existujici v pipeline


	public function __construct()
	{
	}


	public function start()
	{
		reset($this->queue);
		while((list($id, $m) = each($this->queue))) {
			$this->exec_module($m);
		}
	}


	public function add_prepared_module($module, $connections = array())
	{
		$id = $module->id();
		if (array_key_exists($id, $this->modules)) {
			error_log(sprintf('%s::%s(): Module ID "%s" already exists in pipeline!', __CLASS__, __FUNCTION__, $id));
			return false;
		} else {
			if ($module->pc_connect($connections, $this->modules)) {
				$this->modules[$id] = $module;

				$this->queue[] = $module;	// todo
				return true;
			} else {
				return false;
			}
		}
	}


	private function exec_module($module)
	{
		// todo
		$module->pc_execute($this->modules);
	}
}

// vim:encoding=utf8:
?>
