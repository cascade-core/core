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
 * Load specified session variables and set them on according outputs.
 */
class B_core__session__load extends \Cascade\Core\Block {

	protected $inputs = array(
		'key' => null,		// Key to load.
	);

	protected $connections = array(
		'key' => array(),
	);

	protected $outputs = array(
		'key' => true,		// Loaded key.
		'value' => true,	// Session value.
		'done' => true,
	);


	public function main()
	{
		$k = $this->in('key');
		$this->out('key', $k);

		if (isset($_SESSION[$k])) {
			$this->out('value', $_SESSION[$k]);
			$this->out('done', true);
		} else {
			$this->out('value', null);
			$this->out('done', false);
		}
	}

}

