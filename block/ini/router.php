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
 * Match request URI or given path against routes and pick first matching
 * route. Then set outputs as specified by selected route.
 *
 * "#" section contans default values. Section name is matched pattern,
 * where "$" prefixed names are used to remember part of URI and then set
 * it to output when rule matches. Two stars at the end of path match rest
 * of path and this tail is available on 'path_tail' output.
 *
 * Example: See routes.examples.ini.php.
 */
class B_core__ini__router extends Block {

	protected $inputs = array(
		'path' => null,			// Path to match.
		'config' => array(),		// Configuration or filename where configuration is.
		'canonize_path' => true,	// Redirect to canonical form of path? (HTTP GET method only.)
	);

	protected $outputs = array(
		'*' => true,			// Values from matched rule.
	);


	public function main()
	{
		// get current path
		$uri_path = $this->in('path');
		if ($uri_path == null) {
			$uri_path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
			$orig_uri_path = $uri_path;
		} else {
			$orig_uri_path = false;
		}

		// have slash at the end ?
		$have_last_slash = $uri_path != '/' && substr($uri_path, -1) == '/';

		// normalize current path and get fragments
		$uri_path = rtrim($uri_path, '/');
		$uri_path = preg_replace('/\/+/', '/', $uri_path);
		if ($uri_path == '') {
			$uri_path = '/';
		}

		// if normalized does not match original, redirect and do not set outputs
		if ($orig_uri_path !== false && $this->in('canonize_path')
				&& ($have_last_slash ? $orig_uri_path != $uri_path.'/' : $orig_uri_path != $uri_path)
				&& $_SERVER['REQUEST_METHOD'] == 'GET')
		{
			//dump('redirect to:', $have_last_slash ? $uri_path.'/' : $uri_path, 'from:', $orig_uri_path);
			$this->templateOptionSet('root', 'redirect_url', $have_last_slash ? $uri_path.'/' : $uri_path);
			$this->out('done', false);
			return;
		}

		$config = $this->loadConfig();
		$args = $this->route($config, $uri_path);

		if ($args !== false) {
			// match found
			$this->outAll($args);
			$this->out('done', true);
			$this->out('path', $uri_path);
			$this->out('no_match', false);
		} else {
			// no match
			debug_msg("No route matched!");
			$this->out('done', false);
			$this->out('path', $uri_path);
			$this->out('no_match', true);
		}
	}


	protected function loadConfig()
	{
		// load config
		$config = $this->in('config');
		if (is_string($config)) {
			$conf = parse_ini_file($config, TRUE);
			if ($conf === FALSE) {
				return false;
			}
			$conf_mtime = filemtime($config);
		} else if (is_array($config)) {
			$conf = $config;
			$conf_mtime = null;
		} else {
			return false;
		}

		// scan blocks, add results to $conf
		if (array_key_exists('scan-blocks', $conf)) {
			$this->scanBlocks($conf, $conf_mtime, $conf['scan-blocks']);
		}

		return $conf;
	}


	public function scanBlocks(& $conf, $conf_mtime, $scan_opts)
	{
		if (!empty($scan_opts['cache_file'])) {
			$cache_file = filename_format(@$scan_opts['cache_file']);
			$cache_mtime = $cache_file ? @filemtime($cache_file) : 0;
			if (!defined('ROUTER_CACHE_FILE')) {
				define('ROUTER_CACHE_FILE', $cache_file);
			}
		} else {
			$cache_file = null;
			$cache_mtime = null;
		}

		// Is cache up-to-date
		if ($cache_file && $conf_mtime < $cache_mtime && filemtime(__FILE__) < $cache_mtime) {
			$cache_content = unserialize(gzuncompress(file_get_contents($cache_file)));
			if (is_array($cache_content)) {
				$conf = $cache_content;
				return;
			}
		}

		$block_var = empty($scan_opts['block_var']) ? 'content' : $scan_opts['block_var'];

		// Do scan
		debug_msg('Scanning blocks for routes... Cache file: %s', $cache_file ? $cache_file : 'none');
		$blocks = $this->getCascadeController()->getKnownBlocks();
		$storages = $this->getCascadeController()->getBlockStorages();
		foreach ($blocks as $plugin => $plugin_blocks) {				// From each block ...
			foreach ($plugin_blocks as $block) {
				foreach ($storages as $storage_id => $src_storage) {		// ... in each storage ...
					$b = $src_storage->loadBlock($block);
					if (is_array($b)) {
						foreach ($b as $k => $v) {			// ... and each part of block configuration ...
							if (strncmp('route:', $k, 6) == 0) {
								$v[$block_var] = $block;
								$conf[substr($k, 6)] = $v;	// ... get route description.
							}
						}
					}
				}
			}
		}

		// TODO: Optimize this... But who cares? It is cached anyway.
		uksort($conf, function ($a, $b) {
			$a_star = substr_compare($a, '**', -2, 2);
			$b_star = substr_compare($b, '**', -2, 2);
			if ($a_star != $b_star) {
				return $b_star - $a_star;
			}

			$dollars = substr_count($a, '$') - substr_count($b, '$');
			if ($dollars != 0) {
				return $dollars;
			}

			$slashes = substr_count($b, '/') - substr_count($a, '/');
			if ($slashes != 0) {
				return $a_star || $b_star ? - $slashes : $slashes;
			}

			return strcmp($a, $b);
		});
		//echo "<pre style='text-align:left;margin:1em;'>\n", join("\n", array_keys($conf)), "\n</pre>\n";

		// Save to cache
		if ($cache_file) {
			file_put_contents($cache_file, gzcompress(serialize($conf), 2));
		}
		return $conf;
	}

	
	protected function route($conf, $path)
	{
		// default args
		if (array_key_exists('#', $conf)) {
			$defaults = $conf['#'];
			unset($conf['#']);
		} else {
			$defaults = array();
		}

		$path = explode('/', rtrim($path, '/'));

		// match rules one by one
		foreach($conf as $mask => $args) {
			if ($mask[0] == '!') {
				$m = explode(':', substr($mask, 1));
				dump($m);
			} else {
				$m = explode('/', rtrim($mask, '/'));
				$last = end($m);

				// check length (quickly drop wrong path)
				if ($last != '**' ? count($m) != count($path) : count($m) - 1 > count($path)) {
					continue;
				}

				// compare fragments
				for ($i = 0; $i < count($m); $i++) {
					if (@$m[$i][0] == '$') {
						// variable - match anything
						$a = substr($m[$i], 1);
						$args[$a] = $path[$i];
					} else if ($i == count($m) - 1 && $m[$i] == '**') {
						// last part is '**' -- copy tail and finish
						$args['path_tail'] = array_slice($path, $i);
						$i = count($m);
						break;
					} else if ($m[$i] != $path[$i]) {
						// fail
						break;
					}
				}
				if ($i < count($m)) {
					// match failed
					continue;
				}
			}

			// match found
			debug_msg("Matched rule [%s]", $mask);
			return array_merge($defaults, $args);
		}

		// no match
		return false;
	}

}

