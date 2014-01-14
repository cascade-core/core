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
 * Extract path from request URI and split it into chunks by slash. Then put
 * these chunks to outputs.
 *
 * See also core/ini/router -- it is much more useful.
 */
class B_core__in__path extends Block {

	protected $inputs = array(
	);

	protected $outputs = array(
		'last' => true,		// Last part of path
		'depth' => true,	// Length of path (in chunks)
		'path' => true,		// Path as a string.
		'server' => true,	// Server's hostname
		'*' => true,		// Path chunks.
	);

	public function main()
	{
		global $_SERVER;

		$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

		// get uri, explode it to array and calculate depth
		if ($uri == '/' || $uri == '') {
			$uri = '/';
			$path = array();
			$depth = 0;
		} else {
			$uri = rtrim($uri, '/');
			$path = explode('/', ltrim($uri, '/'));
			$depth = count($path);
		}

		// set outputs
		if ($depth >= 1) {
			$path['last'] = & $path[count($path) - 1];
		} else {
			$path['last'] = null;
		}
		$path['depth'] = & $depth;
		$path['path'] = & $uri;
		$path['server'] = & $_SERVER['SERVER_NAME'];
		$this->outAll($path);
	}
}

