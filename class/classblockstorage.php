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
 * Load configuration of blocks. It allows loading blocks from many 
 * different storages like simple INI files, SQL database or cloud blob 
 * storage.
 *
 * This class loads native PHP classes.
 */
class ClassBlockStorage implements IBlockStorage {

	// Regular expressions to match right files and get block name from their names
	protected $filename_match_regexp = '/^[\/a-zA-Z0-9_]+\.php$/';
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
		// nop
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

