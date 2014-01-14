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
 * Merge arrays from all inputs, prefixing their keys with name of input.
 *
 * See also core/prefix/split.
 */
class B_core__prefix__merge extends Block {

	protected $inputs = array(
		'*' => null,
	);

	protected $outputs = array(
		'out' => true,
		'done' => true,
	);

	public function main()
	{
		$out = array();
		foreach($this->inputNames() as $in) {
			if (strchr($in, '_') !== FALSE) {
				error_msg('Input names must not contain underscore (\'_\')!');
				return;
			} else {
				$in_data = $this->in($in);
				if (is_array($in_data)) {
					foreach ($in_data as $k => $v) {
						$out[$in.'_'.$k] = $v;
					}
				} else {
					$out[$in.'_'] = $in_data;
				}
			}
		}
		$this->out('out', $out);
		$this->out('done', true);
	}
}

