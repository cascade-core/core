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
 * Interface to configuration loader. Each entry is identified with name and 
 * contains some structured data similar to JSON document.
 */
interface IConfig
{

	/**
	 * Clear cache.
	 *
	 * This should be called after deploy.
	 */
	public function clearCache();


	/**
	 * Load and compose configuration from the storage.
	 *
	 * If $force_cache_reload is true, the cache should be ignored while 
	 * retrieving the configuration, but the cache should be updated once 
	 * data are retrieved.
	 */
	public function load($name, $force_cache_reload = false);

}

