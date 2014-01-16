<?php
/*
 * Copyright (c) 2013, Josef Kufner  <jk@frozen-doe.net>
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
 * JsonConfig with APC cache.
 */
class JsonConfigAPC extends JsonConfig
{

	/**
	 * Retrieve configuration from cache.
	 */
	protected function fetchFromCache($key, & $hit)
	{
		// TODO: Use global caching mechanism
		return apc_fetch($key, $hit);
	}


	/**
	 * Add configuration to cache.
	 */
	protected function addToCache($key, $value)
	{
		// TODO: Use global caching mechanism
		return apc_add($key, $value, 0);
	}


	/**
	 * Clear cache.
	 *
	 * This should be called after deploy.
	 */
	public function clearCache()
	{
		return apc_clear_cache('user');
	}

}

