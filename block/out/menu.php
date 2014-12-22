<?php
/*
 * Copyright (c) 2010, Josef Kufner  <jk@frozen-doe.net>
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
 * Show menu.
 */
class B_core__out__menu extends \Cascade\Core\Block
{
	const force_exec = true;

	protected $inputs = array(
		'items' => null,		// Menu items.
		'layout' => 'tree',		// Layout: tree or row.
		'active_uri' => null,		// Highlight item linking to this URI.
		'title_fmt' => null,		// Default: '{a}{label}{/a}'; wrapped with span if it is not in format '{a}...{/a}'
		'class' => null,		// Class set to top-level element.
		'max_depth' => PHP_INT_MAX,	// Show only items up to specified depth.
		'column_count' => 4,		// Column count for columns layout
		'active_only' => false,		// Render only active path
		'slot' => 'default',
		'slot_weight' => 50,
	);

	protected $outputs = array(
	);


	public function main()
	{
		$items = $this->in('items');
		$active_uri = $this->in('active_uri');

		if (is_array($items)) {
			if ($active_uri) {
				$this->findBestMatch($items, $active_uri);
			}

			$this->templateAdd(null, 'core/menu', array(
					'items' => $items,
					'layout' => $this->in('layout'),
					'title_fmt' => $this->in('title_fmt'),
					'class' => $this->in('class'),
					'max_depth' => $this->in('max_depth'),
					'column_count' => $this->in('column_count'),
					'active_only' => $this->in('active_only'),
				));
		}
	}


	/**
	 * Find the best match for active url and mark that item and its parents with classes.
	 */
	protected function findBestMatch(& $items, $active_uri)
	{
		$path = array();
		$best_len = -1;
		$best_path = array();

		$this->findBestMatchWalk($items, rtrim($active_uri, '/'), $path, $best_len, $best_path);

		//debug_dump($best_path, $this->fullId().': Best path');

		if ($best_len >= 0) {
			$last = array_pop($best_path);
			if (empty($best_path)) {
				$items[$last]['classes'][] = 'active';
				$items[$last]['is_active'] = true;
			} else {
				$i = & $items[reset($best_path)];
				$i['classes'][] = 'active_child';
				$i['has_active_child'] = true;
				while (next($best_path) !== FALSE) {
					$i = & $i['children'][current($best_path)];
					$i['classes'][] = 'active_child';
					$i['has_active_child'] = true;
				}
				$i['children'][$last]['classes'][] = 'active';
				$i['children'][$last]['is_active'] = true;
			}
		}

		//debug_dump($items, $this->fullId().': Items');
	}


	private function findBestMatchWalk($items, $active_uri, & $path = null, & $best_len = null, & $best_path = null)
	{
		foreach ($items as $i => $item) {
			if (!empty($item['hidden'])) {
				continue;
			}
			if (!empty($item['link'])) {
				$link  = $item['link'][0] == '/' ? rtrim(parse_url($item['link'], PHP_URL_PATH), '/') : $item['link'];
				$len   = strlen($link);
				$match = (strncmp($link, $active_uri, $len) == 0);

				if ($match && $len > $best_len) {
					$best_len = $len;
					$best_path = $path;
					array_push($best_path, $i);
				}
			}

			if (!empty($item['children'])) {
				array_push($path, $i);
				$this->findBestMatchWalk($item['children'], $active_uri, $path, $best_len, $best_path);
				array_pop($path);
			}
		}
	}

}

