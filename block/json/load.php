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
 * Load JSON file like core/ini/load.
 *
 * @deprecated Use core/config instead. This will exist only until 
 * documentation is redesigned.
 */

class B_core__json__load extends \Cascade\Core\Block {

	protected $inputs = array(
		'filename' => array(),		// Name of the file to load.
		'name' => null,			// If specified, sprintf(filename, name) is used to compose filename.
		'process_sections' => true,	// Ignored.
		'multi_output' => false,	// Use top-level keys of loaded file as output names.
		'scan_plugins' => false,	// Search for specified file in all plugin directories.
	);

	protected $outputs = array(
		'data' => true,			// Data loaded from json file.
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
			$data = @parse_json_file(DIR_APP.$fn);
			if ($data === FALSE) {
				$data = @parse_json_file(DIR_CORE.$fn);
				if ($data === FALSE) {
					$data = array();
				}
			}
			$plugin_data[] = $data;
			foreach (get_plugin_list() as $plugin) {
				$d = @parse_json_file(DIR_PLUGIN.$plugin.'/'.$fn);
				if ($d !== FALSE) {
					$plugin_data[] = $d;
				}
			}
			$data = call_user_func_array('array_merge', $plugin_data);
		} else {
			$data = parse_json_file($fn);
		}

		if ($data === FALSE) {
			$this->out('error', true);
		} else {
			if ($this->in('multi_output')) {
				$this->outAll($data);
			} else {
				$this->out('data', $data);
			}
		}

		$this->out('filename', $fn);
		$this->out('done', $data !== FALSE);
	}
}

