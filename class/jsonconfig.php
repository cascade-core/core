<?php
/*
 * Copyright (c) 2012, Josef Kufner  <jk@frozen-doe.net>
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
 * Manage configuration stored in JSON files in core, plugins and application 
 * directories. All files of the same name in these locations are merged 
 * together.
 *
 * This class does not use caching, but other classes can extend this one and 
 * add any cache implementation.
 */
class JsonConfig
{

	/**
	 * Retrieve configuration from cache.
	 */
	protected function fetchFromCache($key, & $hit)
	{
		$hit = false;
		return null;
	}


	/**
	 * Add configuration to cache.
	 */
	protected function addToCache($key, $value)
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
	public function load($name, $force_cache_reload = false)
	{
		$filenames = array();
		$cache_key = __CLASS__.'.'.$name;

		// Check if configuration is cached (invalid name will not be cached)
		if (!$force_cache_reload) {
			$cached_cfg = $this->fetchFromCache($cache_key, $hit);
			if ($hit && is_array($cached_cfg)) {
				return $cached_cfg;
			}
		}

		// Validate $name
		if (!preg_match('/^[a-zA-Z0-9_-]+(\/[a-zA-Z0-9_-]+)*$/', $name)) {
			throw new Exception('Malformed config name: '.$name);
		}

		// Core
		$cfn = DIR_CORE.$name.'.json.php';
		if (file_exists($cfn)) {
			$filenames[] = $cfn;
		}

		// All plugins
		foreach (get_plugin_list() as $plugin) {
			$pfn = DIR_PLUGIN.$plugin.'/'.$name.'.json.php';
			if (file_exists($pfn)) {
				$filenames[] = $pfn;
			}
		}

		// Application file is last, so it can ovewrite anything
		$afn = DIR_APP.$name.'.json.php';
		if (file_exists($afn)) {
			$filenames[] = $afn;
		}

		// And local config is even laster, so it can overwrite more 
		// than anything. These files should not be commited, they are 
		// .gitignored by default. Use them for things like database 
		// configuration and other installation-specific setup.
		$afn = DIR_ROOT.$name.'.local.json.php';
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

		// Store loaded configuration to cache
		$this->addToCache($cache_key, $final_cfg);

		return $final_cfg;
	}


	public static function readJson($filename)
	{
		$d = json_decode(file_get_contents($filename), TRUE, 512, JSON_BIGINT_AS_STRING);
		if ($d === null) {
			switch (json_last_error()) {
				case JSON_ERROR_NONE:
					$err = 'No errors';
					break;
				case JSON_ERROR_DEPTH:
					$err = 'Maximum stack depth exceeded';
					break;
				case JSON_ERROR_STATE_MISMATCH:
					$err = 'Underflow or the modes mismatch';
					break;
				case JSON_ERROR_CTRL_CHAR:
					$err = 'Unexpected control character found';
					break;
				case JSON_ERROR_SYNTAX:
					$err = 'Syntax error, malformed JSON';
					break;
				case JSON_ERROR_UTF8:
					$err = 'Malformed UTF-8 characters, possibly incorrectly encoded';
					break;
				default:
					$err = 'Unknown error';
					break;
			}
			error_msg("Failed to load \"%s\": %s", $filename, $err);
			return false;
		} else {
			if (isset($d['_'])) {
				unset($d['_']);
			}
			return $d;
		}
	}

}

