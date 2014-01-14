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
 * Show page skeleton. It fills 'root' slot.
 */
class B_core__out__page extends Block
{
	const force_exec = true;

	protected $inputs = array(
		'css_link' => null,	// CSS file to link.
	);

	protected $outputs = array(
		'done' => true,
	);

	function main()
	{
		$this->templateAddToSlot(null, 'root', 50, 'core/main', array(
				'css_link' => $this->in('css_link'),
			));
		$this->out('done', true);
	}

}

