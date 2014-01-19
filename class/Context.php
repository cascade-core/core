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
 * The context is not-so-ugly replacement of global variables. It is created 
 * during initialization and then passed to all block storages (IBlockStorage) 
 * and to CascadeController. From CascadeController it is accessible to all 
 * Block instances in the cascade.
 *
 * The Context is intended to setup gettext (or reconfigure it on language 
 * switch), to keep Template engine, and to manage all resources shared between 
 * block storages or created in init.php and passed to block storages.
 *
 * From blocks point of view, the Context is read-only structure. It can be 
 * only cloned and then the clone can be modified and passed to new blocks.
 *
 * TODO: Remove all these getters and setters, use only stupid public 
 *       properties (and magic if neccessary).
 *
 * TODO: Take a look at other dependency injection containers, service 
 *       locators, blah blah, ... and make this better.
 */
class Context {

	private $config_loader = null;
	private $locale = DEFAULT_LOCALE;
	private $template_engine = null;

	private static $last_context_enviroment = false;


	/**
	 * Constructor.
	 */
	public function __construct()
	{
		/**
		 * Nothing to do... yet.
		 *
		 * Don't forget to call this from derived classes, even if it
		 * is empty for now.
		 */
	}


	/**
	 * Set configuration loader (IConfig).
	 */
	public function setConfigLoader($config_loader)
	{
		$this->config_loader = $config_loader;
	}


	/**
	 * Get configuration loader.
	 */
	public function getConfigLoader()
	{
		return $this->config_loader;
	}


	/**
	 * Set Template engine.
	 */
	public function setTemplateEngine($template_engine)
	{
		$this->template_engine = $template_engine;
	}


	/**
	 * Get Template engine.
	 */
	public function getTemplateEngine() {
		return $this->template_engine;
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
		$this->locale = $locale !== null ? preg_replace('/[^.]*$/', '', $locale).'UTF8' : null;
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
		if (self::$last_context_enviroment === $this) {
			return false;
		} else {
			self::$last_context_enviroment = $this;

			debug_msg('Updating enviroment: locale = "%s"', $this->locale);

			if ($this->locale !== null) {
				$this->locale = setlocale(LC_ALL, $this->locale, DEFAULT_LOCALE, 'C');
				putenv('LANG='.$this->locale);
			}
			return true;
		}
	}

}

