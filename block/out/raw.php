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
 * Show raw data without any processing.
 */

class B_core__out__raw extends \Cascade\Core\Block
{
	const force_exec = true;

	protected $inputs = array(
		'data' => null,
		'slot' => 'default',
		'slot_weight' => 50,
	);


	function main()
	{
		$data = $this->in('data');

		if ($data !== null) {
			$this->templateAdd(null, 'core/raw', array('data' => $data));
		}
	}

}

