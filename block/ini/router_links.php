<?php
/*
 * Copyright (c) 2011, Josef Kufner  <jk@frozen-doe.net>
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

/**
 * Extract links from router's config file and build menu from them. Useful
 * when website navigation is not finished yet. Works well with core/out/menu.
 */

class B_core__ini__router_links extends B_core__ini__router {

	protected $inputs = array(
		'config' => null,		// Configuration or filename where configuration is.
		'flat_list' => false,		// Build flat listing instead of tree structure
		'title_key' => 'title',		// Key used for title in menu item
		'title_fmt' => '{title}',	// Format of title (use any variable set in routes)
		'link_key' => 'link',		// Key used for link in menu item
		'link_fmt' => '{ROUTE}',	// Format of title (use any variable set in routes)
		'children_key' => 'children_key', // Key used for nesting children items
		'enable_key' => null,		// This value must be set to show the item
	);

	protected $outputs = array(
		'links' => true,
		'done' => true,
	);


	public function main()
	{
		// load config
		$conf = $this->loadConfig();

		$flat_list = $this->in('flat_list');
		$title_key = $this->in('title_key');
		$title_fmt = $this->in('title_fmt');
		$link_key = $this->in('link_key');
		$link_fmt = $this->in('link_fmt');
		$enable_key = $this->in('enable_key');

		$links = array();

		// default args
		if (array_key_exists('#', $conf)) {
			$defaults = $conf['#'];
			unset($conf['#']);
		} else {
			$defaults = array();
		}

		if (array_key_exists('scan-blocks', $conf)) {
			$scan_opts = $conf['scan-blocks'];
			$block_var = $scan_opts['block_var'];
		} else {
			$block_var = null;
		}

		if ($enable_key == 'BLOCK') {
			$enable_key = $block_var;
		}

		// build list
		foreach ($conf as $route => $outputs) {
			if ($route[0] != '/') {
				continue;
			}
			if ($enable_key != null && empty($outputs[$enable_key])) {
				continue;
			}
			if ($flat_list || (strstr($route, '/$') == FALSE && preg_match('/\/\*\*$/', $route) == FALSE)) {
				$val = array_merge($defaults, $outputs);
				$val['ROUTE'] = $route;
				if ($block_var != null) {
					$val['BLOCK'] = @ $val[$block_var];
				}
				$title = template_format($title_fmt, $val);
				$link = template_format($link_fmt, $val);

				if ($title == '') {
					$title = $link;
				}

				if ($flat_list) {
					$links[] = array(
						$title_key => $title,
						$link_key => $link,
					);
				} else {
					$parent = & $links;
					$link_parts = explode('/', $route);
					$parent_parts = array_slice($link_parts, 1, -1);
					$this_part = end($link_parts);
					foreach($parent_parts as $part) {
						$parent = & $parent[$part]['children'];
					}

					$parent[$this_part][$link_key]  = $link;
					$parent[$this_part][$title_key] = $title;
				}
			}
		}

		sort($links);

		$this->out('links', $links);
		$this->out('done', !empty($conf));
	}
}

