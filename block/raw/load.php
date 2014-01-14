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

/**
 * Load raw file.
 */

class B_core__raw__load extends Block {

	protected $inputs = array(
		'filename' => array(),	// Filename
		'name' => null,		// If set, sprintf(filename, name) is used.
	);

	protected $outputs = array(
		'data' => true,		// Data loaded from file.
		'filename' => true,	// Filename of loaded file.
		'error' => true,	// True if failed.
		'done' => true,
	);


	public function main()
	{
		$name = $this->in('name');
		$fn = $this->in('filename');

		if ($name !== null) {
			$fn = sprintf($fn, $name);
		}

		$data = file_get_contents($fn);

		if ($data === FALSE) {
			$this->out('error', true);
		} else {
			$this->out('data', $data);
		}
		$this->out('filename', $fn);
		$this->out('done', $data !== FALSE);
	}
}

