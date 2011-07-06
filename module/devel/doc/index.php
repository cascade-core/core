<?php
/*
 * Copyright (c) 2011, Josef Kufner  <jk@frozen-doe.net>
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
 * Loads and shows list of all known modules. Links are easily usable with
 * core/deve/doc/show module.
 *
 * Example:
 *
 *	; app/core.ini.php
 *	;  (listing modules at end of file only)
 *
 *	[module:router]
 *	.module		= core/ini/router
 *	config		= app/routes.ini.php
 *
 *	[module:doc_index]
 *	.module		= "core/devel/doc/index"
 *	.force-exec	= true
 *	enable[]	= "router:index"
 *
 *	[module:doc_show]
 *	.module		= "core/devel/doc/show"
 *	.force-exec	= true
 *	module[]	= "router:path_tail"
 *	show-code	= false
 *	enable[]	= "router:show"
 *
 *
 * 	; app/routes.ini.php
 *	;  (You will want to use core/value/pipeline_loader or something like
 *	;  that, but this will do the job too.)
 *
 *	[/doc]
 *	index = true
 *	show = false
 *
 *	[/doc/**]
 *	index = false
 *	show = true
 *	; path_tail is '**' part
 *
 */

class M_core__devel__doc__index extends Module
{
	const force_exec = true;

	protected $inputs = array(
		'link' => '/doc/%s',
		'slot' => 'default',
		'slot-weight' => 50,
	);

	protected $outputs = array(
		'done' => true,
	);


	public function main()
	{
		$prefixes = array(
			'' => DIR_APP.DIR_MODULE,
			'core' => DIR_CORE.DIR_MODULE,
		);

		foreach (get_plugin_list() as $plugin) {
			$prefixes[$plugin] = DIR_PLUGIN.$plugin.'/'.DIR_MODULE;
		}

		$modules = array();

		foreach ($prefixes as $prefix => $dir) {
			$list = $this->scan_directory($dir, $prefix);
			if (!empty($list)) {
				$modules[$prefix] = $list;
			}
		}

		$this->template_add(null, 'core/doc/index', array(
				'link' => $this->in('link'),
				'modules' => $modules,
			));

		$this->out('done', !empty($modules));
	}


	private function scan_directory($directory, $prefix, $subdir = '', & $list = array())
	{
		$dir_name = $directory.$subdir;
		$d = opendir($dir_name);
		if (!$d) {
			return $list;
		}

		while (($f = readdir($d)) !== FALSE) {
			if ($f[0] == '.') {
				continue;
			}

			$file = $dir_name.'/'.$f;
			$module = $subdir.'/'.$f;

			if (is_dir($file)) {
				$this->scan_directory($directory, $prefix, $module, $list);
			} else if (preg_match('/^[\/a-zA-Z0-9_]+(\.ini)?\.php$/', $module)) {
				$list[] = ($prefix != '' ? $prefix.'/' : '').preg_replace('/^\/([\/a-zA-Z0-9_-]+)(?:\.ini)?\.php$/', '$1', $module);
			}
		}

		closedir($d);

		if ($subdir == '') {
			sort($list);
		}
		return $list;
	}
}

