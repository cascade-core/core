<?php
/*
 * Copyright (c) 2017, Josef Kufner  <josef@kufner.cz>
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
 * Plugin Manager - load plugins and their configuration
 */
class PluginManager
{
	protected $dir_root;
	protected $dir_core;
	protected $dir_vendor;
	protected $dir_app;

	protected $plugin_list;
	protected $config_loader;
	protected $core_config = false;


	function __construct($dir_root, JsonConfig $config_loader)
	{
		$this->dir_root = $dir_root;
		$this->dir_core = dirname(__DIR__);
		$this->dir_vendor = dirname(dirname($this->dir_core));
		$this->dir_app = $this->dir_root.'/app';
		$this->config_loader = $config_loader;
	}


	public function loadCoreConfig()
	{
		if ($this->core_config !== false) {
			return $this->core_config;
		}
		$name = 'cascade';

		$cfg = $this->config_loader->fetchFromCache($name);
		if ($cfg !== false) {
			return $cfg;
		}

		$cfg = $this->config_loader->load($name, $this->dir_root, $this->dir_core, $this->dir_app, $this->dir_vendor, []);
		$this->plugin_list = array_keys($cfg['plugins']);
		$this->core_config = $this->config_loader->load($name, $this->dir_root, $this->dir_core, $this->dir_app, $this->dir_vendor, $this->plugin_list);

		$this->config_loader->addToCache($name, $this->core_config);
		return $this->core_config;
	}


	public function loadConfig($name)
	{
		$cfg = $this->config_loader->fetchFromCache($name);
		if ($cfg === false) {
			$cfg = $this->config_loader->load($name, $this->dir_root, $this->dir_core, $this->dir_app, $this->dir_vendor, $this->plugin_list);
			$this->config_loader->addToCache($name, $cfg);
		}
		return $cfg;
	}

}

