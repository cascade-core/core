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
 * Loads and shows list of all known blocks. Links are easily usable with
 * core/deve/doc/show block.
 */

class B_core__devel__doc__index extends Block
{
	const force_exec = true;

	protected $inputs = array(
		'link' => DEBUG_CASCADE_GRAPH_LINK,	// Link to documentation.
		'heading_level' => 2,			// Level of the first heading.
		'regexp' => null,			// additional regexp used to filter filenames (example: '/\.ini\.php$/').
		'slot' => 'default',
		'slot_weight' => 50,
	);

	protected $outputs = array(
		'done' => true,
	);


	public function main()
	{
		$regexp = $this->in('regexp');
		$blocks = $this->get_blocks($regexp);

		$this->template_add(null, 'core/doc/index', array(
				'link' => $this->in('link'),
				'blocks' => $blocks,
				'heading_level' => $this->in('heading_level'),
				'titles' => $this->get_titles(),
			));

		$this->out('done', !empty($blocks));
	}


	protected function get_titles() {
		return array(
			'' => _('Application'),
			'core' => _('Core'),
		);
	}


	public static function get_blocks($regexp = null)
	{
		$prefixes = array(
			'' => DIR_APP.DIR_BLOCK,
			'core' => DIR_CORE.DIR_BLOCK,
		);

		foreach (get_plugin_list() as $plugin) {
			$prefixes[$plugin] = DIR_PLUGIN.$plugin.'/'.DIR_BLOCK;
		}

		$blocks = array();

		foreach ($prefixes as $prefix => $dir) {
			$list = self::scan_directory($dir, $prefix, $regexp);
			if (!empty($list)) {
				$blocks[$prefix] = $list;
			}
		}

		return $blocks;
	}


	private static function scan_directory($directory, $prefix, $regexp = null, $subdir = '', & $list = array())
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
			$block = $subdir.'/'.$f;

			if (is_dir($file)) {
				self::scan_directory($directory, $prefix, $regexp, $block, $list);
			} else if (preg_match('/^[\/a-zA-Z0-9_]+(\.ini)?\.php$/', $block) && ($regexp == null || preg_match($regexp, $block))) {
				$list[] = ($prefix != '' ? $prefix.'/' : '').preg_replace('/^\/([\/a-zA-Z0-9_-]+)(?:\.ini)?\.php$/', '$1', $block);
			}
		}

		closedir($d);

		if ($subdir == '') {
			sort($list);
		}
		return $list;
	}
}

