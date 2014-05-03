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
 * Set page title and type using root slot option. It is useful to connect 
 * inputs to multiple blocks using ':or' input function.
 */

class B_core__out__page_options extends \Cascade\Core\Block
{
	const force_exec = true;

	protected $inputs = array(
		'title' => null,		// Page title.
		'title_fmt' => null,		// sprintf() format string, useful for adding extra suffix.
		'type' => null,			// Output type.
	);

	protected $outputs = array(
		'title' => true,
		'type' => true,
		'done' => true,
	);

	public function main()
	{
		$title = $this->in('title');
		$title_fmt = $this->in('title_fmt');
		$this->templateSetPageTitle($title, $title_fmt);
		$this->out('title', $title);

		$type = $this->in('type');
		$this->templateSetType($type);
		$this->out('type', $type);

		$this->out('done', true);
	}
}

