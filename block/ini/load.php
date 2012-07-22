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
 * Load INI file using parse_ini_file() function.
 */

class B_core__ini__load extends Block {

	protected $inputs = array(
		'filename' => array(),		// Name of the file to load.
		'name' => null,			// If specified, sprintf(filename, name) is used to compose filename.
		'process_sections' => true,	// Second argument to parse_ini_file().
		'multi_output' => false,	// Use top-level keys of loaded file as output names.
		'scan_plugins' => false,	// Search for specified file in all plugin directories.
	);

	protected $outputs = array(
		'data' => true,			// Data loaded from ini file.
		'filename' => true,		// Used filename.
		'error' => true,		// True when loading failed.
		'done' => true,
		'*' => true,
	);


	public function main()
	{
		$name = $this->in('name');
		$fn = $this->in('filename');
		$process_sections = $this->in('process_sections');

		if (is_array($name)) {
			$name = join('/', $name);
		}
		if (is_array($fn)) {
			$fn = join('/', $fn);
		}

		if ($name !== null) {
			$fn = filename_format(sprintf($fn, $name));
		}

		$fn = filename_format($fn);

		if ($this->in('scan_plugins')) {
			$data = @parse_ini_file(DIR_APP.$fn, $process_sections);
			if ($data === FALSE) {
				$data = @parse_ini_file(DIR_CORE.$fn, $process_sections);
				if ($data === FALSE) {
					$data = array();
				}
			}
			$plugin_data[] = $data;
			foreach (get_plugin_list() as $plugin) {
				$d = @parse_ini_file(DIR_PLUGIN.$plugin.'/'.$fn, $process_sections);
				if ($d !== FALSE) {
					$plugin_data[] = $d;
				}
			}
			$data = call_user_func_array('array_merge', $plugin_data);
		} else {
			$data = parse_ini_file($fn, $process_sections);
		}

		if ($data === FALSE) {
			$this->out('error', true);
		} else {
			if ($this->in('multi_output')) {
				$this->out_all($data);
			} else {
				$this->out('data', $data);
			}
		}

		$this->out('filename', $fn);
		$this->out('done', $data !== FALSE);
	}
}

