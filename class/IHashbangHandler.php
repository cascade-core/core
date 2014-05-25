<?php
/*
 * Copyright (c) 2012, Josef Kufner  <jk@frozen-doe.net>
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
 * Interface required by hashbang handlers. Hashbang handler is usualy 
 * proxy block itself, it only needs few extra methods. 
 *
 * If block storage returns something without hashbang specified, default 
 * hashbang 'proxy' is assumed. This is for compatibility with old blocks.
 *
 * TODO: Add some helpers for block editor.
 */
interface IHashbangHandler
{

	/**
	 * Factory method to create a proxy block (usualy self). Called by 
	 * CascadeController::createBlockInstance() when block storage returns 
	 * configuration instead of block.
	 *
	 * @param $block_config is configuration of the new block.
	 * @param $hashbang_config is configuration of the hashbang (from core.json.php).
	 * @param $context is Context of creating block -- not the context in which will be block executed.
	 * @param $block_type is type of the block (ID is not known yet).
	 *
	 * @example Typical implementation: `return new self();`
	 */
	public static function createFromHashbang($block_config, $hashbang_config, Context $context, $block_type);

}

