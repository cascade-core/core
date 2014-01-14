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
 * Set page title using root slot option. It is useful to connect 'title' input
 * to multiple blocks using :or function.
 */

class B_core__out__set_page_title extends Block
{
	const force_exec = true;

	protected $inputs = array(
		'title' => null,		// Title.
		'title_fallback' => null,	// Alternative title when 'title' input is empty.
		'format' => null,		// sprintf() format string, useful for adding extra suffix.
	);

	protected $outputs = array(
		'title' => true,
		'done' => true,
	);

	public function main()
	{
		$t = $this->in('title');
		$fmt = $this->in('format');

		if ($t == '') {
			$t = $this->in('title_fallback');
		}

		$this->templateSetPageTitle($t, $fmt);

		$this->out('title', $t);
		$this->out('done', true);
	}
}

