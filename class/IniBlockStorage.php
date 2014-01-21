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
 * Load block composition from INI file.
 *
 * @deprecated Use JsonBlockStorage instead.
 */
class IniBlockStorage extends ClassBlockStorage implements IBlockStorage {

	/// @copydoc ClassBlockStorage::$filename_match_regexp
	protected $filename_match_regexp = '/^[\/a-zA-Z0-9_]+\.ini\.php$/';

	/// @copydoc ClassBlockStorage::$filename_match_regexp
	protected $filename_to_block_regexp = '/^\/([\/a-zA-Z0-9_-]+)\.ini\.php$/';


	/**
	 * Returns true if there is no way that this storage can modify or 
	 * create blocks. When creating or modifying block, first storage that 
	 * returns true will be used.
	 */
	public function isReadOnly()
	{
		return false;
	}


	/**
	 * Create instance of requested block and give it loaded configuration. 
	 * No further initialisation here, that is job for cascade controller. 
	 * Returns created instance or false.
	 */
	public function createBlockInstance ($block)
	{
		$conf = $this->loadBlock($block);

		if ($conf) {
			$b = new \B_core__ini__proxy();
			$b->setConfiguration($conf);
			return $b;
		} else {
			return false;
		}
	}


	/**
	 * Describe block for documentation generator.
	 */
	public function describeBlock ($block)
	{
		$filename = get_block_filename($block, '.ini.php');
		if (!file_exists($filename)) {
			return null;
		}

		// Load file
		$doc = parse_ini_file($filename, TRUE);

		$doc['filename'] = $filename;
		$doc['is_composed_block'] = true;
		$inputs = array();
		$outputs = array();

		// Copied inputs
		foreach ($doc['copy-inputs'] as $out => $in) {
			$inputs[$in] = array(
				'name' => $in,
				'value' => null,
				'comment' => sprintf(_('Copied to output "%s".'), $out),
			);
			$outputs[$out] = array(
				'name' => $out,
				'comment' => sprintf(_('Copied from input "%s".'), $in),
			);
		}


		// Outputs
		foreach ($doc['outputs'] as $out => $src) {
			$outputs[$out] = array(
				'name' => $out,
				'comment' => null,
			);
		}

		// Forwarded-outputs
		foreach ($doc['forward-outputs'] as $out => $src) {
			$outputs[$out] = array(
				'name' => $out,
				'comment' => null,
			);
		}

		// Store converted data
		$doc['inputs'] = $inputs;
		$doc['outputs'] = $outputs;
		
		return $doc;
	}


	/**
	 * Load block configuration. Returns false if block is not found.
	 */
	public function loadBlock ($block)
	{
		$filename = get_block_filename($block, '.ini.php');

		return file_exists($filename) ? parse_ini_file($filename, TRUE) : null;
	}


	/**
	 * Store block configuration.
	 */
	public function storeBlock ($block, $config)
	{
		$filename = get_block_filename($block, '.ini.php');
		$dir = dirname($filename);

		if (!file_exists($dir)) {
			if (!mkdir($dir, 0777, true)) {
				error_msg('Failed to create directory "%s" while storing block "%s".', $dir, $block);
			}
		}

		$saved = write_ini_file($filename, $config, TRUE);
		if (!$saved) {
			error_msg('Failed to write INI file "%s" while storing block "%s".', $filename, $block);
			return false;
		}

		return true;
	}


	/**
	 * Delete block configuration.
	 */
	public function deleteBlock ($block)
	{
		$filename = get_block_filename($block, '.ini.php');

		return file_exists($filename) && unlink($filename);
	}


	/**
	 * Get time (unix timestamp) of last modification of the block.
	 */
	public function blockMTime ($block)
	{
		$filename = get_block_filename($block, '.ini.php');
		return @filemtime($filename);
	}

}

