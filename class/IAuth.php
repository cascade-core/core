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

namespace Cascade\Core;

/**
 * Authorizator interface, used by CascadeController to determine whether user 
 * is allowed to perform an action or not.
 *
 * Authorization is separed into two levels:
 *
 *   1. Block level
 *   2. Entity level
 *
 * When permission is denied at the first level, block is not executed at all. 
 * The second level permissions are be checked by each block, therefore, there 
 * is a lot of space for errors. User must pass both levels to perform the 
 * action.
 *
 * Only the first level is in scope of this interface and the CascadeController.
 */
interface IAuth
{

	/**
	 * Level 1: Check if block is allowed to current user.
	 */
	public function isBlockAllowed($block_name, & $details = null);

};


