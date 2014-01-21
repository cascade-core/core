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
 * Show something using specified template. When new block is created by
 * inheriting from this one, template name can be specified in $template
 * protected property.
 *
 * If 'data' input is false or $template property is set, all inputs are
 * forwarded to template, otherwise 'data' input is used.
 */
class B_core__out__output extends \Cascade\Core\Block
{
	const force_exec = true;

	protected $inputs = array(
		'template' => null,	// Template to use.
		'data' => false,	// If false, all inputs are forwarded, otherwise only content of this one.
		'*' => null,		// All inputs are forwarded to template if input 'data' is false.
		'slot' => 'default',
		'slot_weight' => 50,
	);

	protected $connections = array(
		'template' => array(),
	);

	protected $outputs = array(
		'done' => true,
	);

	protected $template = null;	// Used instead of template input


	function main()
	{
		if ($this->template === null) {

			// Template is not set, use 'template' and 'data'
			// inputs. If 'data' input is false, forward everything
			// to template.

			$data = $this->in('data');
			if ($data === false) {
				$data = array();
				foreach ($this->inputNames() as $i) {
					$data[$i] = $this->in($i);
				}
			}

			$this->templateAdd(null, $this->in('template'), $data);

		} else {

			// Template is set, ignore 'template' and 'data' inputs
			// and forward everything to template.

			$data = array();
			foreach ($this->inputNames() as $i) {
				$data[$i] = $this->in($i);
			}

			$this->templateAdd(null, $this->template, $data);

		}

		$this->out('done', true);
	}
}

