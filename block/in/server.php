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
 * Load data from $_SERVER superglobal variable, so other blocks can use it.
 */

class B_core__in__server extends \Cascade\Core\Block {

	protected $inputs = array(
	);

	protected $outputs = array(
		'all' => true,		// $_SERVER as is.
		'*' => true,		// Each value in $_SERVER is available on its own output.
	);

	public function main()
	{
		$this->out('all', $_SERVER);
	}

	public function getOutput($name)
	{
		return @$_SERVER[strtoupper($name)];
	}
}

