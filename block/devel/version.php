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
 * Show current version of application and optionaly also version of all
 * plugins and core. This block executes update-version.sh script to keep
 * version info fresh. If this script cannot be executed and/or Git is not
 * installed on server, try using Git plugin.
 */

class B_core__devel__version extends Block {
	
	const force_exec = true;

	protected $inputs = array(
		'filename' => '{DIR_ROOT}var/version.ini.php',	// Location of generated version file.
		'format' => 'short',	// 'short' = only app version, 'details' = everything.
		'link' => null,		// when 'short' format, link to this url.
		'prefix' => null,	// when 'short' format, prepend this string (some delimiter or so).
		'suffix' => null,	// when 'short' format, append this string (some delimiter or so).
		'slot' => 'default',
		'slot_weight' => 50,
	);

	protected $outputs = array(
		'done' => true,
	);

	public function main()
	{
		$version_file = template_format($this->in('filename'), get_defined_constants(), null);
		$version_mtime = @filemtime($version_file);
		$version_size = @filesize($version_size);

		$format = $this->in('format');

		// Check if version.ini needs update
		$need_update = false;
		if (!$version_mtime || ($version_size < 10 && $version_mtime + 28800 < time()) || $this->is_git_repo_newer($version_mtime, DIR_ROOT)) {
			// Short format needs only app version, so do not check everything
			$need_update = true;
		} else if ($format != 'short') {
			// If format is not 'short', check core and all plugins
			if ($this->is_git_repo_newer($version_mtime, DIR_CORE)) {
				$need_update = true;
			} else {
				if ($version_mtime < filemtime(DIR_PLUGIN)) {
					// new, deleted or renamed block
					$need_update = true;
				} else {
					foreach (get_plugin_list() as $plugin) {
						if ($this->is_git_repo_newer($version_mtime, DIR_PLUGIN.$plugin.'/')) {
							$need_update = true;
							break;
						}
					}
				}
			}
		}

		// Update version.ini
		if ($need_update) {
			if (system(escapeshellarg(DIR_CORE.'update-version.sh')) === FALSE) {
				error_msg('Cannot update version info, executing update-version.sh failed.');
			}
			touch($version_file);	// be sure that file is created even if script failed
		}

		// Get version data
		$version = parse_ini_file($version_file, TRUE);

		// Show version (if present)
		if (!empty($version)) {
			$this->template_add(null, 'core/version', array(
					'version' => $version,
					'format'  => $format,
					'link'    => $this->in('link'),
					'prefix'  => $this->in('prefix'),
					'suffix'  => $this->in('suffix'),
				));
			$this->out('done', true);
		}
	}

	private function is_git_repo_newer($ref_mtime, $basedir)
	{
		$gitdir = $basedir.'.git';
		if (is_file($gitdir)) {
			$repo = file_get_contents($gitdir);
			if (preg_match('/^gitdir: (.*)/', $repo, $m)) {
				$gitdir = realpath($m[1]);
			}
		}

		$head_file = $gitdir.'/HEAD';

		if ($ref_mtime < @filemtime($head_file)) {
			return true;
		}

		$head = @ file_get_contents($head_file);

		if ($head === FALSE) {
			return false;
		}
		if (sscanf($head, 'ref: %s', $ref_file) == 1) {
			return $ref_mtime < @filemtime($basedir.'.git/'.$ref_file);
		} else {
			return false;
		}
	}
}
