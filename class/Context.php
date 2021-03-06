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
 * Resources specified in 'resources' option are dereferenced and passed as 
 * regular (top-level) options within the rest of factory configuration.
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
		$this->setupTranslator();
	}


	/**
	 * Setup translator: Bind gettext domain.
	 */
	protected function setupTranslator()
	{
		bindtextdomain('messages', DIR_APP.'locale/');
		bind_textdomain_codeset('messages', 'UTF-8');
		textdomain('messages');
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
		if (!isset($this->_resource_factories_config[$resource_name])) {
			throw new ResourceException('Unknown resource: '.$resource);
		}
		$cfg = $this->_resource_factories_config[$resource_name];

		// Preallocate cache slot (to avoid cycles)
		// TODO: implement lazy loading
		$this->$resource_name = null;

		// Resolve resources
		if (!empty($cfg['_resources'])) {
			$resources = $cfg['_resources'];
			unset($cfg['_resources']);
			foreach ($resources as $k => $v) {
				$cfg[$k] = $this->$v;
			}
		}

		// Use config loader ---- $cfg must not be modified after this ----
		if (!empty($cfg['_load_config'])) {
			$resource_cfg = array_merge($this->config_loader->load($cfg['_load_config']), $cfg);
		} else {
			$resource_cfg = $cfg;
		}

		// Try to create resource using class name
		if (isset($cfg['class'])) {
			$class = $cfg['class'];
			$resource = new $class($resource_cfg, $this, $resource_name);
			return ($this->$resource_name = $resource);
		}

		// Try to call factory method
		if (isset($cfg['factory'])) {
			$factory = $cfg['factory'];
			$resource = call_user_func($factory, $resource_cfg, $this, $resource_name);
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


	/**
	 * Get current locale. It is automaticaly updated to reflect real
	 * locale after updateEnviroment().
	 */
	public function getLocale()
	{
		return $this->_locale;
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
		if (self::$_last_context_enviroment === $this) {
			return false;
		} else {
			self::$_last_context_enviroment = $this;

			//debug_msg('Updating enviroment: locale = "%s"', $this->_locale);

			if ($this->_locale !== null) {
				$this->_locale = setlocale(LC_ALL, $this->_locale.'.UTF-8', $this->_locale, $this->_default_locale, 'C');
				putenv('LANG='.$this->_locale);
			}
			return true;
		}
	}

}

