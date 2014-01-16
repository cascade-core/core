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

interface IAuth
{

	// Level 1: Check if block is allowed to current user
	public function isBlockAllowed($block_name, & $details = null);


	// Level 2: Check permissions to specified item
	public function checkItem($block_name, & $item, & $details = null);

	// Level 2: Add permission conditions to query object (like adding where clause to sql query)
	public function addCondition($block_name, & $query, $options = array());

};


