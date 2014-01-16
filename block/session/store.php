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
 * Store specified value into session using specified key.
 */

class B_core__session__store extends \Cascade\Core\Block
{
	const force_exec = true;

	protected $inputs = array(
		'key' => array(),	// Session key.
		'value' => null,	// Value to store.
		'unset' => false,	// Remove key from session?
	);

	protected $outputs = array(
		'done' => true,
	);


	public function main()
	{
		$k = $this->in('key');
		$this->out('key', $k);

		if ($this->in('unset')) {
			unset($_SESSION[$k]);
		} else {
			$_SESSION[$k] = $this->in('value');
		}
		$this->out('done', true);
	}

}

