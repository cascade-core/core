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
 * Show heading.
 */
class B_core__out__header extends \Cascade\Core\Block
{
	const force_exec = true;

	protected $inputs = array(
		'level' => 2,		// Heading level. (1 is page title, 2 is chapter, ...)
		'text' => null,		// Text of heading.
		'img_src' => null,	// URL of image.
		'link' => null,		// Make heading link to this URL (href attribute).
		'anchor' => null,	// Name of heading (name attribute).
		'option' => null,	// Load text from slot option.
		'slot' => 'default',
		'slot_weight' => 50,
		'*' => null,
	);

	protected $outputs = array(
	);

	public function main()
	{
		$in = $this->inAll();

		$this->templateAdd(null, 'core/header', array(
				'option'  => $in['option'],
				'text'    => template_format($in['text'], $in),
				'img_src' => $in['img_src'],
				'link'    => $in['link'],
				'anchor'  => $in['anchor'],
				'level'   => $in['level'],
			));
	}
}

