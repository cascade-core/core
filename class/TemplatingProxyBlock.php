<?php
/*
 * Copyright (c) 2014, Josef Kufner  <jk@frozen-doe.net>
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

namespace Cascade\Core;

/**
 * TemplatingProxyBlock is like ProxyBlock, but all valus are passed thru 
 * template_format() function. Input values and predefined constants are 
 * available as template variables.
 */
class TemplatingProxyBlock extends ProxyBlock
{

	/**
	 * Preprocess configuration before it is interpreted by proxy itself.
	 *
	 * Receives original configuration and returns modified version.
	 */
	protected function preprocessConfiguration($conf)
	{
		$inputs = $this->inAll();

		array_walk_recursive($conf, function(& $val, $key) use ($inputs) {
			if (is_string($val)) {
				$val = filename_format($val, $inputs);
			}
		});

		return $conf;
	}

}

