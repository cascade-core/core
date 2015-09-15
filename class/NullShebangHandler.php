<?php
/*
 * Copyright (c) 2015   Josef Kufner  <jk@frozen-doe.net>
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
 * Null shebang handler, which removes block of the same name by shadowing it
 * from lower block storages.
 */
class NullShebangHandler implements IShebangHandler {

	/**
	 * Do not create block.
	 */
	public static function createFromShebang($block_config, $shebang_config, Context $context, $block_type)
	{
		// False means 'block not found'.
		return false;
	}

}

