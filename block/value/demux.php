<?php
/*
 * Copyright (c) 2015, Josef Kufner  <josef@kufner.cz>
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
 * Set given output to given value (demultiplexer).
 */

class B_core__value__demux extends \Cascade\Core\Block {

	protected $inputs = array(
		'out' => null,
		'value' => true,
	);

	protected $outputs = array(
		'*' => true,
	);

	public function main()
	{
		$out = $this->in('out');
		$value = $this->in('value');

		$this->out($out, $value);
	}
}

