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
 * Load configuration of blocks. It allows loading blocks from many 
 * different storages like simple INI files, SQL database or cloud blob 
 * storage.
 *
 * This class loads native PHP classes.
 */
class ClassBlockStorage implements IBlockStorage {

	/**
	 * Regular expression to match files containing block classes.
	 */
	protected $filename_match_regexp = '/^[\/a-zA-Z0-9_]+\.php$/';

	/**
	 * Regular expression to convert filename to block name.
	 */
	protected $filename_to_block_regexp ='/^\/([\/a-zA-Z0-9_-]+)\.php$/'; 


	/**
	 * Constructor will get options from core.ini.php file.
	 *
	 * Arguments:
	 * 	$storage_opts - Options loaded from config file
	 * 	$context - Common default context (dependency injection 
	 *		container) passed to all storages, and later also to 
	 *		all blocks.
	 */
	public function __construct($storage_opts, $context)
	{
		spl_autoload_register(function ($class)
		{
			// TODO: Remove this
			global $plugin_list;

			@ list($head, $tail) = explode("\\", $class, 2);

			/* Block */
			if ($tail === null && $class[0] == 'B' && $class[1] == '_') {
				$m = str_replace('__', '/', substr($class, 2));
				$f = get_block_filename($m);
				if (file_exists($f)) {
					require($f);
				}
				return;
			}
		});

	}

	/**
	 * Returns true if there is no way that this storage can modify or 
	 * create blocks. When creating or modifying block, first storage that 
	 * returns true will be used.
	 */
	public function isReadOnly()
	{
		return true;
	}


	/**
	 * Create instance of requested block and give it loaded configuration. 
	 * No further initialisation here, that is job for cascade controller. 
	 * Returns created instance or false.
	 */
	public function createBlockInstance ($block)
	{
		$c = $this->loadBlock($block);

		if ($c) {
			return new $c();
		} else {
			return false;
		}
	}


	/**
	 * Load block configuration. Returns false if block is not found.
	 */
	public function loadBlock ($block)
	{
		/* build class name */
		$class = 'B_'.str_replace('/', '__', $block);

		/* kick autoloader and return class name if autoloader found class */
		return class_exists($class) ? $class : false;
	}


	/**
	 * Store block configuration.
	 */
	public function storeBlock ($block, $config)
	{
		// source code is read only
		return false;
	}


	/**
	 * Delete block configuration.
	 */
	public function deleteBlock ($block)
	{
		return false;
	}


	/**
	 * Get time (unix timestamp) of last modification of the block.
	 */
	public function blockMTime ($block)
	{
		$filename = get_block_filename($block, '.php');
		return @filemtime($filename);
	}


	/**
	 * List all available blocks in this storage.
	 */
	public function getKnownBlocks (& $blocks = array())
	{
		$prefixes = array(
			'' => DIR_APP.DIR_BLOCK,
			'core' => DIR_CORE.DIR_BLOCK,
		);

		foreach (get_plugin_list() as $plugin) {
			$prefixes[$plugin] = DIR_PLUGIN.$plugin.'/'.DIR_BLOCK;
		}

		foreach ($prefixes as $prefix => $dir) {
			if (file_exists($dir)) {
				$this->getKnownBlocks_scanDirectory($blocks[$prefix], $dir, $prefix);
			}
		}

		return $blocks;
	}


	/**
	 * Recursively scan directory.
	 */
	private function getKnownBlocks_scanDirectory(& $list, $directory, $prefix, $subdir = '')
	{
		$dir_name = $directory.$subdir;
		$d = opendir($dir_name);
		if (!$d) {
			return;
		}

		while (($f = readdir($d)) !== FALSE) {
			if ($f[0] == '.') {
				continue;
			}

			$file = $dir_name.'/'.$f;
			$block = $subdir.'/'.$f;

			if (is_dir($file)) {
				$this->getKnownBlocks_scanDirectory($list, $directory, $prefix, $block);
			} else if (preg_match($this->filename_match_regexp, $block)) {
				$list[] = ($prefix != '' ? $prefix.'/' : '').preg_replace($this->filename_to_block_regexp, '$1', $block);
			}
		}

		closedir($d);
	}

}

