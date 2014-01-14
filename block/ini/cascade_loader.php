<?php
/*
 * Copyright (c) 2011, Josef Kufner  <jk@frozen-doe.net>
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
 * Insert specified blocks into cascade. It is simple wraper around
 * Block::cascade_add_from_ini() function.
 *
 * See also core/value/cascade_loader.
 */
class B_core__ini__cascade_loader extends Block
{
	const force_exec = true;

	protected $inputs = array(
		'items' => array(),	// Blocks and their connections.
	);

	protected $outputs = array(
	);


	public function main()
	{
		$this->cascadeAddFromIni($this->in('items'));
	}

}

