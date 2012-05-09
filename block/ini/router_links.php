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
 * Extract links from router's config file and build menu from them. Useful
 * when website navigation is not finished yet. Works well with core/out/menu.
 */

class B_core__ini__router_links extends Block {

	protected $inputs = array(
		'path' => null,			// Path to match.
		'config' => array(),		// Configuration or filename where configuration is.
		'title_output' => 'title',	// Name of router's output that contains menu item title.
	);

	protected $outputs = array(
		'links' => true,
		'done' => true,
	);


	public function main()
	{
		// load config
		$config = $this->in('config');
		if (is_string($config)) {
			$conf = parse_ini_file($config, TRUE);
			if ($conf === FALSE) {
				return false;
			}
		} else if (is_array($config)) {
			$conf = $config;
		} else {
			return false;
		}

		$links = array();
		$title_key = $this->in('title_output');

		// default args
		if (array_key_exists('#', $conf)) {
			$defaults = $conf['#'];
			unset($conf['#']);
		} else {
			$defaults = array();
		}

		// build list
		foreach ($conf as $link => $outputs) {
			if (strstr($link, '/$') == FALSE && preg_match('/\/\*\*$/', $link) == FALSE) {
				if (array_key_exists($title_key, $outputs)) {
					$title = $outputs[$title_key];
				} else {
					$title = @ $defaults[$title_key];
				}
				if ($title == '') {
					$title = $link;
				}

				$parent = & $links;
				$link_parts = explode('/', $link);
				$parent_parts = array_slice($link_parts, 1, -1);
				$this_part = end($link_parts);
				foreach($parent_parts as $part) {
					$parent = & $parent[$part]['children'];
				}

				$parent[$this_part]['link']  = $link;
				$parent[$this_part]['title'] = $title;
			}
		}

		$this->out('links', $links);
		$this->out('done', true);
	}
}

