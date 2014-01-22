#!/usr/bin/env php
<?php
/*
 * Copyright (c) 2013, Josef Kufner  <jk@frozen-doe.net>
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

require dirname(dirname(__FILE__)).'/utils.php';

array_shift($argv);

if (count($argv) == 0) {
	die("Usage: ".$argv[0]." source-file.json\n");
}

foreach ($argv as $src) {

	$data = parse_json_file($src);
	if ($data === FALSE) {
		die('Load error: '.$src."\n");
	}

	foreach ($data as $k => $v) {

		if ($k === 'block' && !array_key_exists('blocks', $data)) {
			$data['blocks'] = $v;
			unset($data['block']);
		}

		if ($k === 'route' && !array_key_exists('routes', $data)) {
			$data['routes'] = $v;
			unset($data['route']);
		}

		@list($keyword, $id) = explode(':', $k, 2);

		if ($id !== null) {
			if ($keyword == 'block') {
				$new_v = array();
				foreach ($v as $vk => $vv) {
					if ($vk[0] == '.') {
						$new_v[substr($vk, 1)] = $vv;
					} else if (is_array($vv)) {
						/* parse connections */
						if (count($vv) == 1) {
							/* single connection */
							$vv = explode(':', $vv[0], 2);
						} else {
							/* multiple connections */
							$outs = array(null);
							foreach ($vv as $o) {
								if ($o[0] == ':') {
									$outs[0] = $o;
								} else {
									list($o_mod, $o_out) = explode(':', $o, 2);
									$outs[] = $o_mod;
									$outs[] = $o_out;
								}
							}
							$vv = $outs;
						}
						$new_v['in_con'][$vk] = $vv;
					} else {
						$new_v['in_val'][$vk] = $vv;
					}
				}
				$data[$keyword.'s'][$id] = $new_v;
			} else {
				$data[$keyword.'s'][$id] = $v;
			}
			unset($data[$k]);
		}
	}

	write_json_file($src, $data);
}

