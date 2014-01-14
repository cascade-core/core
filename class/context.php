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

class Context {

	private $config_loader = null;
	private $locale = DEFAULT_LOCALE;
	private $template_engine = null;

	private static $last_context_enviroment = false;


	public function __construct()
	{
		// Nothing to do... yet.
		//
		// Don't forget to call this from derived classes, even if this
		// is empty now.
	}


	public function setConfigLoader($config_loader)
	{
		$this->config_loader = $config_loader;
	}

	
	public function getConfigLoader()
	{
		return $this->config_loader;
	}



	/****************************************************************************
	 *	For blocks
	 */

	public function setLocale($locale)
	{
		$this->locale = $locale !== null ? preg_replace('/[^.]*$/', '', $locale).'UTF8' : null;
	}


	public function setTemplateEngine($template_engine)
	{
		$this->template_engine = $template_engine;
	}



	/****************************************************************************
	 *	For Cascade controller
	 */

	/* update enviroment from context, returns true if changes required (for child classes) */
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


	public function getTemplateEngine() {
		return $this->template_engine;
	}

}

