<?php
/*
 * Copyright (c) 2012, Josef Kufner  <jk@frozen-doe.net>
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
 * Match request URI or given path against rules from config input. When 
 * matching rule is found, insert specified block into cascade and set 
 * specified outputs.
 */
class B_core__router extends \Cascade\Core\Block
{

	protected $inputs = array(
		'routes' => null,	// Routes
		'protocol' => null,	// Current protocol ('http' or 'https')
		'host' => null,		// Current hostname (full domain)
		'path' => null,		// Current path (that part of URL after hostname)
		'*' => null,		// Postprocessors
	);

	protected $connections = array(
		'routes' => array('config', 'routes'),
	);

	protected $outputs = array(
		'*' => true,
		'done' => true,
	);

	protected $routes = null;


	public function main()
	{
		$routes = $this->in('routes');
		$protocol = $this->in('protocol');
		$host = $this->in('host');
		$path_str = $this->in('path');

		// Get default protocol
		if ($protocol === null) {
			$protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
		}

		// Get default host name
		if ($host === null) {
			$host = $this->getCurrentHostName();
		}

		// Get default path
		if ($path_str === null) {
			$path_str = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
		}

		// Convert current path to array and to string (result: $path is array, $path_str is string)
		if (is_array($path_str)) {
			$path = $path_orig;
			$path_str = '/'.join('/', $path);
		} else {
			$path = trim($path_str, '/');
			$path = ($path == '' ? array() : explode('/', $path));
		}

		// Get '!action'
		$path_tail = end($path);
		if (strpos($path_tail, '!') !== FALSE) {
			list($path_tail, $action, ) = explode('!', $path_tail, 3); // drop extra '!'
			if ($path_tail != '') {
				$path[key($path)] = $path_tail;
			}
		} else {
			$action = null;
		}

		// Does path end with slash?
		$path_slash = (substr($path_str, -1) == '/');

		// Prepare reverse router
		$this->reverse_routes = $routes['reverse_routes'];
		$this->context->template_engine->addReverseRouter(array($this, 'getUrl'));

		// Find current route in all groups
		$route = false;
		foreach ($routes['groups'] as $group_name => $group) {
			// Check group rules
			if (!$this->checkGroup($group, $protocol, $host)) {
				continue;
			}
			// Find route
			$route = $this->route($group['routes'], $path, $action, $group_name, $group);
			if ($route !== false) {
				// Route found
				if (!empty($routes['defaults'])) {
					// Merge with defaults
					$defaults = (array) @ $routes['defaults'];
					$group_defaults = (array) @ $group['defaults'];
					$route = array_replace($defaults, $group_defaults, $route);
				}

				// Redirect to canonical path
				if (isset($route['path_canon']) && $route['path_canon'] != $path_str) {
					$this->templateOptionSet('root', 'redirect_code', 302);
					$this->templateOptionSet('root', 'redirect_url', $route['path_canon']);
					return; // no outputs set
				}

				// Add slash to end of the path if requested (redirect)
				if (isset($route['path_slash']) && $path_str != '/' && (!!$route['path_slash']) != $path_slash) {
					$this->templateOptionSet('root', 'redirect_code', 302);
					$this->templateOptionSet('root', 'redirect_url', $route['path_slash'] ? $path_str.'/' : rtrim($path_str, '/'));
					return; // no outputs set
				}

				$this->outAll($route);
				$done = true;
				break;
			}
		}

		if ($route === false) {
			// Route not found
			if (!empty($routes['defaults'])) {
				$this->outAll($routes['defaults']);
			}
			$done = false;
		}

		$this->out('protocol', $protocol);
		$this->out('host', $host);
		$this->out('path', $path_str);
		$this->out('done', $done);

	}


	/**
	 * Find something that is most likely current server host name.
	 */
	protected function getCurrentHostName()
	{
		if ($host = @ $_SERVER['HTTP_X_FORWARDED_HOST']) {
			$host = explode(',', $host);
			return trim(end($host));
		}
		
		if ($host = @ $_SERVER['HTTP_HOST']) {
			return preg_replace('/:\d+$/', '', $host);
		}
		
		if ($host = @ $_SERVER['SERVER_NAME']) {
			return $host;
		}

		return @ $_SERVER['SERVER_ADDR'];
	}


	/**
	 * Reverse router
	 */
	public function getUrl($route, $values)
	{
		// Todo
	}


	protected function checkGroup($group, $protocol, $host)
	{
		foreach ((array) @ $group['require'] as $condition => $values) {
			switch ($condition) {
				// Check if current protocol is in enumerated posibilities
				case 'protocol':
					if (!in_array($protocol, (array) $values, true)) {
						return false;
					}
					break;
				// Check if current host is in enumerated posibilities
				case 'host':
					if (!in_array($host, (array) $values, true)) {
						return false;
					}
					break;
				// Check if user can execute all enumerated blocks
				case 'block_allowed':
					foreach ((array) $values as $block) {
						if (!$this->authIsBlockAllowed($block)) {
							return false;
						}
					}
					break;
			}
		}
		return true;
	}


	protected function route($routes, $path, $action, $group_name, $group)
	{
		// find matching route
		$outputs = static::findMatchingRoute($routes, $path, $mask);
		if ($outputs === false) {
			return false;
		}

		// good enough, add '!action'
		if (isset($action)) {
			$outputs['path_action'] = $action;
		}

		// postprocess found match
		$postprocessor_input = @ $group['postprocessor'];
		if ($postprocessor_input) {
			if (!is_string($postprocessor_input)) {
				throw new \InvalidArgumentException(sprintf(
					'Postprocessor must be a string in rule [%s], group %s.', $mask, $group_name));
			}
			$postprocessor = $this->in($postprocessor_input);
			if (!is_callable($postprocessor)) {
				throw new \InvalidArgumentException(sprintf(
					'Postprocessor at input \"%s\" is not a callable.', $postprocessor_input));
			}
			return $postprocessor($outputs, $group);
		}

		// match found and processed
		debug_msg("Matched rule [%s] in group %s", $mask, $group_name);
		return $outputs;

		// no match
		return false;
	}


	/**
	 * Find route which matches given path.
	 *
	 * @param $routes Routes to search.
	 * @param $path Path to find in `$routes`.
	 * @param $mask Mask (pattern) of the found route.
	 * @param $validation_callback Function which returns non-false when
	 * 	found route is valid. If `false` is returned, router continues to
	 * 	search as if the `$mask` did not match. Prototype: `function($outputs, $mask)`
	 * @return Found route is returned. If `$validation_callback` is
	 * 	specified, its return value is returned.
	 */
	public static function findMatchingRoute($routes, $path, & $mask = null, $validation_callback = null)
	{
		$path_len = count($path);

		// match rules one by one
		foreach($routes as $mask => $route) {
			// get path fragments
			$m = trim($mask, '/');
			$m = ($m == '' ? array() : explode('/', $m));
			$m_len = count($m);
			$last = end($m);

			// check length (quickly drop wrong path)
			if ($last != '**' ? $m_len != $path_len : $m_len - 1 > $path_len) {
				continue;
			}

			$outputs = $route;

			// compare fragments
			for ($i = 0; $i < $m_len; $i++) {
				$mi = $m[$i];
				if (@$mi[0] == '$') {
					// variable - match anything
					$a = substr($mi, 1);
					$outputs[$a] = $path[$i];
				} else if ($i == $m_len - 1 && $mi == '**') {
					// last part is '**' -- copy tail and finish
					$outputs['path_head'] = array_slice($path, 0, $i);
					$outputs['path_tail'] = array_slice($path, $i);
					$i = $m_len;
					break;
				} else if ($mi != '*' && $mi != $path[$i]) {
					// fail
					break;
				}
			}
			if ($i >= $m_len && ($validation_callback === null || ($outputs = $validation_callback($outputs, $mask)) !== false)) {
				// match found
				return $outputs;
			}
		}
		return false;
	}

}

