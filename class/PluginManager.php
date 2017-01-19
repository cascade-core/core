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

	protected $plugins;
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
			$this->plugins = array_keys($cfg['plugins']);
			return $cfg;
		}

		$cfg = $this->config_loader->load($name, $this->dir_root, $this->dir_core, $this->dir_app, $this->dir_vendor, []);
		$this->plugins = $cfg['plugins'];
		$this->core_config = $this->config_loader->load($name, $this->dir_root, $this->dir_core, $this->dir_app, $this->dir_vendor, $this->plugins);

		$this->config_loader->addToCache($name, $this->core_config);
		return $this->core_config;
	}


	public function loadConfig($name)
	{
		$cfg = $this->config_loader->fetchFromCache($name);
		if ($cfg === false) {
			$cfg = $this->config_loader->load($name, $this->dir_root, $this->dir_core, $this->dir_app, $this->dir_vendor, $this->plugins);
			$this->config_loader->addToCache($name, $cfg);
		}
		return $cfg;
	}

	/**
	 * Get plugin list
	 */
	public function getPluginList()
	{
		return $this->plugins;

		/* $plugin_list contains everything in plugin directory. It is not
		 * filtered becouse CascadeController will not allow ugly block names
		 * to be loaded. */
		//return array_filter(array_keys($plugin_list), function($block) {
		//		/* Same as block name check in CascadeController */
		//		return !(!is_string($block) || strpos($block, '.') !== FALSE || !ctype_graph($block));
		//	});
	}

	/**
	 * Get block's file from it's name
	 */
	public function getBlockFilename($block, $extension = '.php')
	{
		@ list($head, $tail) = explode('/', $block, 2);

		/* Core */
		if ($head == 'core') {
			return $this->dir_core.'/block/'.$tail.$extension;
		}

		/* Plugins */
		if ($tail !== null && isset($this->plugins[$head])) {
			return $this->dir_vendor.'/'.$head.'/block/'.$tail.$extension;
		}

		/* Application */
		return $this->dir_app.'/block/'.$block.$extension;
	}

	/**
	 * Get block's class name
	 */
	public function getBlockClassName($block)
	{
		$class_name = 'B_'.str_replace('/', '__', $block);
		if (class_exists($class_name)) {
			return $class_name;
		} else {
			return false;
		}
	}

	/**
	 * Get template's file from it's name
	 */
	public function getTemplateFilename($output_type, $template_name, $extension = '.php')
	{
		@ list($head, $tail) = explode('/', $template_name, 2);

		/* Core */
		if ($head == 'core') {
			return $this->dir_core.'/template/'.$output_type.'/'.$tail.$extension;
		}

		/* Plugins */
		if ($tail !== null && isset($this->plugins[$head])) {
			return $this->dir_vendor.'/'.$this->plugins[$head].'/template/'.$output_type.'/'.$tail.$extension;
		}

		/* Application */
		return $this->dir_app.'/template/'.$output_type.'/'.$template_name.$extension;
	}

}

