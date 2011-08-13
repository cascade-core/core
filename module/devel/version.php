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

class M_core__devel__version extends Module
{
	const force_exec = true;

	protected $inputs = array(
		'format' => 'short',	// 'short' = only app version, 'details' = everything
		'link' => null,		// when 'short' format, link to this url
		'prefix' => null,	// when 'short' format, prepend this string (some delimiter or so)
		'suffix' => null,	// when 'short' format, append this string (some delimiter or so)
		'slot' => 'default',
		'slot-weight' => 50,
	);

	protected $outputs = array(
	);

	public function main()
	{
		$version_file = DIR_ROOT.'var/version.ini.php';
		$version_mtime = @filemtime($version_file);

		if (!$version_mtime || $version_mtime < @filemtime(DIR_ROOT.'.git/refs/heads')) {
			system("core/update-version.sh");
		}

		$version = parse_ini_file($version_file, TRUE);

		if (!empty($version)) {
			$this->template_add(null, 'core/version', array(
					'version' => $version,
					'format'  => $this->in('format'),
					'link'    => $this->in('link'),
					'prefix'  => $this->in('prefix'),
					'suffix'  => $this->in('suffix'),
				));
		}
	}
}

