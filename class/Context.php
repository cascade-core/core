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

namespace Cascade\Core;

/**
 * Context maintains global environment and acts as resource container.
 *
 * It is created during initialization and then passed to all block storages
 * (IBlockStorage) and to CascadeController. From CascadeController it is
 * accessible to all Block instances in the cascade.
 *
 * The Context has two jobs to do:
 *
 *   1. Setup gettext and maintan context switches.
 *   2. Keep and create resources.
 *
 * Just before block is executed Context::updateEnviroment() is called.
 *
 * Resources are created lazily, when they are requested for the first time.
 *
 * @warning Context SHOULD be treated as read-only structure.
 */
class Context {

	private static $_last_context_enviroment = false;
	private $_locale = null;
	private $_default_locale = 'C';
	private $_resource_factories_config;


	/**
	 * Constructor.
	 */
	public function __construct(array $resource_factories_config)
	{
		$this->_resource_factories_config = $resource_factories_config;
	}


	/**
	 * Set locale which will be used as fallback when setting new locale.
	 */
	public function setDefaultLocale($locale)
	{
		$this->_default_locale = $locale;
	}


	/**
	 * Lazily create requested resource and store it as public property, so 
	 * this is called only once per resource.
	 */
	public function __get($resource_name)
	{
		$cfg = @ $this->_resource_factories_config[$resource_name];
		if ($cfg === null) {
			throw new ResourceException('Unknown resource: '.$resource);
		}

		// Try to create resource using class name
		$class = @ $cfg['class'];
		if ($class) {
			$resource = new $class($cfg, $this, $resource_name);
			return ($this->$resource_name = $resource);
		}

		// Try to call factory method
		$factory = @ $cfg['factory'];
		if ($factory) {
			$resource = call_user_func($factory, $cfg, $this, $resource_name);
			return ($this->$resource_name = $resource);
		}

		// Intentionaly empty resource
		if (empty($cfg)) {
			return null;
		}

		// No other way to create resource
		throw new ResourceException('Cannot create resource: '.$resource);
	}


	/************************************************************************//**
	 * @}
	 * \name	For blocks
	 * @{
	 */


	/**
	 * Set gettext locale, the global environment will be updated at next 
	 * updateEnviroment() call.
	 */
	public function setLocale($locale)
	{
		$this->_locale = $locale;
	}


	/************************************************************************//**
	 * @}
	 * \name	For Cascade controller
	 * @{
	 */


	/**
	 * Update enviroment from context, returns true if changes required (for child classes)
	 */
	public function updateEnviroment()
	{
		/* do not update if not changed */
		if (static::$_last_context_enviroment === $this) {
			return false;
		} else {
			static::$_last_context_enviroment = $this;

			//debug_msg('Updating enviroment: locale = "%s"', $this->_locale);

			if ($this->_locale !== null) {
				$this->_locale = setlocale(LC_ALL, $this->_locale.'.UTF-8', $this->_locale, $this->_default_locale, 'C');
				putenv('LANG='.$this->_locale);
			}
			return true;
		}
	}

}

