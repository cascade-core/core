<?php
/*
 * Copyright (c) 2012, Josef Kufner  <jk@frozen-doe.net>
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
 * Match request URI or given path against rules from config input. When 
 * matching rule is found, insert specified block into cascade and set 
 * specified outputs.
 */
class B_core__router extends Block
{

	protected $inputs = array(
		'routes' => array('config', 'routes'),	// Routes
		'protocol' => null,			// Current protocol ('http' or 'https')
		'host' => null,				// Current hostname (full domain)
		'path' => null,				// Current path (that part of URL after hostname)
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
		$path = $this->in('path');

		// Get default protocol
		if ($protocol === null) {
			$protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
		}

		// Get default host name
		if ($host === null) {
			$host = $this->getCurrentHostName();
		}

		// Get default path
		if ($path === null) {
			$path = $_SERVER['REQUEST_URI'];
		}

		// Convert current path to array
		if (!is_array($path)) {
			$path = explode('/', trim($path, '/'));
		}

		// Prepare reverse router
		$this->reverse_routes = $routes['reverse_routes'];
		$this->context->getTemplateEngine()->addReverseRouter(array($this, 'getUrl'));

		// Find current route in all groups
		$route = false;
		foreach ($routes['groups'] as $group_name => $group) {
			// Check group rules
			if (!$this->checkGroup($group, $protocol, $host)) {
				continue;
			}
			// Find route
			$route = $this->route($group['routes'], $path, $group_name);
			if ($route !== false) {
				// Route found
				if (!empty($routes['defaults'])) {
					// Merge with defaults
					$defaults = (array) @ $routes['defaults'];
					$group_defaults = (array) @ $group['defaults'];
					$route = array_replace($defaults, $group_defaults, $route);
				}
				$this->outAll($route);
				$done = true;
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
		$this->out('path', '/'.join('/', $path));
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
			}
		}
		return true;
	}


	protected function route($routes, $path, $group_name)
	{
		$path_len = count($path);

		// match rules one by one
		foreach($routes as $mask => $route) {
			// get path fragments
			$m = explode('/', trim($mask, '/'));
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
			if ($i < $m_len) {
				// match failed
				continue;
			}

			// match found
			debug_msg("Matched rule [%s] in group %s", $mask, $group_name);
			return $outputs;
		}

		// no match
		return false;
	}

}

