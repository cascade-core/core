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

/**
 * Load configuration on demand. Each output matches merged config file as 
 * loaded by config loader suplied by cascade controller. Loaded configuration 
 * is cached automatically.
 */

class B_core__config extends Block {

	protected $inputs = array(
	);

	protected $outputs = array(
		'done' => true,
		'*' => true,
	);

	private $config = null;


	public function main()
	{
		$this->config = $this->context->getConfigLoader();
		$this->out('done', true);
	}


	public function getOutput($name)
	{
		// TODO: Cache loaded configuration

		$keys = explode('.', $name);
		$config_name = array_shift($keys);

		$cfg = $this->config->load($config_name);


		if (empty($keys)) {
			return $cfg;
		} else {
			$p = $cfg;
			foreach ($keys as $k) {
				if (isset($p[$k])) {
					$p = $p[$k];
				} else {
					return null;
				}
			}
			return $p;
		}
	}
}

