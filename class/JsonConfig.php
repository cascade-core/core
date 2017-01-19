<?php
/*
 * Copyright (c) 2012, Josef Kufner  <jk@frozen-doe.net>
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
 * Manage configuration stored in JSON files in core, plugins and application 
 * directories. All files of the same name in these locations are merged 
 * together.
 *
 * This class does not use caching, but other classes can extend this one and 
 * add any cache implementation.
 *
 * TODO: Split cache and file loader
 */
class JsonConfig
{

	/**
	 * Retrieve configuration from cache.
	 *
	 * @return false on cache miss, array otherwise
	 */
	public function fetchFromCache($name)
	{
		return false;
	}


	/**
	 * Add configuration to cache.
	 */
	public function addToCache($name, $value)
	{
		return true;
	}


	/**
	 * Clear cache.
	 *
	 * This should be called after deploy.
	 */
	public function clearCache()
	{
		return true;
	}


	/**
	 * Load and compose configuration from config files. Core, plugins, 
	 * application and local config files are searched.
	 */
	public function load($name, $dir_root, $dir_core, $dir_app, $dir_plugin, $plugin_list)
	{
		$filenames = array();

		// Validate $name
		if (!preg_match('/^[a-zA-Z0-9_-]+(\/[a-zA-Z0-9_-]+)*$/', $name)) {
			throw new Exception('Malformed config name: '.$name);
		}

		// Core
		$cfn = "$dir_core/$name.json.php";
		if (file_exists($cfn)) {
			$filenames[] = $cfn;
		}

		// All plugins
		foreach ($plugin_list as $plugin_name => $plugin_subdir) {
			$pfn = "$dir_plugin/$plugin_subdir/$name.json.php";
			if (file_exists($pfn)) {
				$filenames[] = $pfn;
			}
		}

		// Application file is last, so it can ovewrite anything
		$afn = "$dir_app/$name.json.php";
		if (file_exists($afn)) {
			$filenames[] = $afn;
		}

		// ... and if we are running from cli, load few more pieces.
		if (empty($_SERVER['REMOTE_ADDR']) && php_sapi_name() == 'cli') {
			$afn = "$dir_app/$name.cli.json.php";
			if (file_exists($afn)) {
				$filenames[] = $afn;
			}
		}

		// And local config is even laster, so it can overwrite more 
		// than anything. These files should not be commited, they are 
		// .gitignored by default. Use them for things like database 
		// configuration and other installation-specific setup.
		$afn = "$dir_root/$name.local.json.php";
		if (file_exists($afn)) {
			$filenames[] = $afn;
		}

		// Load and merge all
		$all_cfg = array_map(array($this, 'readJson'), $filenames);
		$count = count($all_cfg);
		if ($count == 0) {
			$final_cfg = array();
		} else if ($count == 1) {
			$final_cfg = reset($all_cfg);
		} else {
			$final_cfg = call_user_func_array('array_replace_recursive', $all_cfg);
		}

		return $final_cfg;
	}


	/**
	 * Read JSON file.
	 *
	 * FIXME: Why this is not private?
	 */
	public static function readJson($filename)
	{
		$d = parse_json_file($filename);
		if (isset($d['_'])) {
			unset($d['_']);
		}
		return $d;
	}

}

