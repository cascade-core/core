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
 * Show menu.
 */
class B_core__out__menu extends Block
{
	const force_exec = true;

	protected $inputs = array(
		'items' => null,		// Menu items.
		'layout' => 'tree',		// Layout: tree or row.
		'active_uri' => null,		// Highlight item linking to this URI.
		'title_fmt' => null,		// Default: '{a}{label}{/a}'; wrapped with span if it is not in format '{a}...{/a}'
		'class' => null,		// Class set to top-level element.
		'max_depth' => PHP_INT_MAX,	// Show only items up to specified depth.
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
				));
		}
	}


	/**
	 * Find the best match for active url and mark that item adn it's parents with classes.
	 */
	protected function findBestMatch(& $items, $active_uri)
	{
		$path = array();
		$best_len = 0;
		$best_path = array();

		$this->findBestMatchWalk($items, $active_uri, $path, $best_len, $best_path);

		if ($best_len > 0) {
			$last = array_pop($best_path);
			if (empty($best_path)) {
				$items[$last]['classes'][] = 'active';
			} else {
				$i = & $items[reset($best_path)];
				$i['classes'][] = 'active_child';
				while (next($best_path)) {
					$i = & $i['children'][current($best_path)];
					$i['classes'][] = 'active_child';
				}
				$i['children'][$last]['classes'][] = 'active';
			}
		}
		//echo "<pre style=\"border:1px solid red\">", join('/', $best_path), "\n", json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "</pre>";
	}


	private function findBestMatchWalk($items, $active_uri, & $path = null, & $best_len = null, & $best_path = null)
	{
		foreach ($items as $i => $item) {
			if (!empty($item['hidden'])) {
				continue;
			}
			if (!empty($item['link'])) {
				$link  = $item['link'];
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

