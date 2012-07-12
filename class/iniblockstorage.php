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
 * Load block composition from INI file.
 */
class IniBlockStorage extends ClassBlockStorage implements IBlockStorage {

	protected $filename_match_regexp = '/^[\/a-zA-Z0-9_]+\.ini\.php$/';
	protected $filename_to_block_regexp = '/^\/([\/a-zA-Z0-9_-]+)\.ini\.php$/';

	/**
	 * Returns true if there is no way that this storage can modify or 
	 * create blocks. When creating or modifying block, first storage that 
	 * returns true will be used.
	 */
	public function is_read_only()
	{
		return false;
	}


	/**
	 * Create instance of requested block and give it loaded configuration. 
	 * No further initialisation here, that is job for cascade controller. 
	 * Returns created instance or false.
	 */
	public function create_block_instance ($block)
	{
		$conf = $this->load_block($block);

		if ($conf) {
			$b = new B_core__ini__proxy();
			$b->set_configuration($conf);
			return $b;
		} else {
			return false;
		}
	}


	/**
	 * Load block configuration. Returns false if block is not found.
	 */
	public function load_block ($block)
	{
		$filename = get_block_filename($block, '.ini.php');

		return parse_ini_file($filename, TRUE);
	}


	/**
	 * Store block configuration.
	 */
	public function store_block ($block, $config)
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
	public function delete_block ($block)
	{
		$filename = get_block_filename($block, '.ini.php');

		return file_exists($filename) && unlink($filename);
	}


	/**
	 * Get time (unix timestamp) of last modification of the block.
	 */
	public function block_mtime ($block)
	{
		$filename = get_block_filename($block, '.ini.php');
		return @filemtime($filename);
	}

}

