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

class B_core__router_links extends \Cascade\Core\Block {

	protected $inputs = array(
		'config' => null,		// Configuration or filename where configuration is.
		'flat_list' => false,		// Build flat listing instead of tree structure
		'title_key' => 'title',		// Key used for title in menu item
		'title_fmt' => '{title}',	// Format of title (use any variable set in routes)
		'link_key' => 'link',		// Key used for link in menu item
		'link_fmt' => '{route}',	// Format of title (use any variable set in routes)
		'children_key' => 'children_key', // Key used for nesting children items
		'enable_key' => 'block',	// This value must be set to show the item
	);

	protected $outputs = array(
		'links' => true,
		'done' => true,
	);


	public function main()
	{
		$conf = $this->in('config');
		$flat_list = $this->in('flat_list');
		$title_key = $this->in('title_key');
		$title_fmt = $this->in('title_fmt');
		$link_key = $this->in('link_key');
		$link_fmt = $this->in('link_fmt');
		$enable_key = $this->in('enable_key');

		$links = array();

		// Global defaults
		$defaults = (array) @ $conf['defaults'];

		// Walk all groups
		foreach ($conf['groups'] as $group_name => $group) {
			// Group defaults
			$group_defaults = (array) @ $group['defaults'];

			// Walk all routes in group
			foreach ($group['routes'] as $mask => $route) {
				// Merge with defaults
				$outputs = array_replace($defaults, $group_defaults, $route);

				// Skip non-paths
				if ($mask[0] != '/') {
					continue;
				}
				// Skip disabled items
				if ($enable_key != null && empty($outputs[$enable_key])) {
					continue;
				}

				// Create menu item
				if ($flat_list || (strstr($mask, '/$') === FALSE && preg_match('/\/\*\*$/', $mask) == 0)) {
					$val = array_merge($defaults, $outputs);
					$val['route'] = $mask;
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
						$link_parts = explode('/', $mask);
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
		}

		sort($links);

		$this->out('links', $links);
		$this->out('done', !empty($conf));
	}
}

