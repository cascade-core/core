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
 * Templating engine which stores objects in the slots and then arranges them 
 * on a page.
 *
 *   - Objects are sorted by their weight (light on top).
 *   - Each object has a template name assigned. This template is used to 
 *     render the object.
 *   - Slots may be recursively nested.
 *   - Each slot has set of options (key-value store) which are inherited from 
 *     parent slots (inherited options do not override slot's own options).
 *
 * The slot is nothing more than function which renders all objects in named array.
 *
 * Templating engine also keeps track of all reverse routers -- the objects 
 * which translate an object ID to URI. These are used to generate links.
 */
class Template
{
	protected $plugin_manager;

	private $objects = array();
	private $slot_content = array();
	private $slot_options = array();
	private $current_slot_depth = 0;
	private $reverse_router = array();
	private $redirect_enabled = true;
	private $annotate = false;


	/**
	 * Constructor compatible with Context resources.
	 */
	function __construct($cfg)
	{
		$default_type = @ $cfg['default_type'];
		if ($default_type) {
			$this->slotOptionSet('root', 'type', $default_type);
		}

		// Debug option to disable all redirects
		if (isset($cfg['redirect_enabled'])) {
			$this->redirect_enabled = $cfg['redirect_enabled'];
		}

		// Debug option to comment source of the HTML markup
		if (isset($cfg['annotate'])) {
			$this->annotate = $cfg['annotate'];
		}
	}


	/**
	 * Register plugin manager. This must be set before start().
	 */
	function setPluginManager(PluginManager $plugin_manager)
	{
		$this->plugin_manager = $plugin_manager;
	}


	/**
	 * Add object to specified slot and set its weight.
	 */
	function addObject(Block $source_block = null, $id, $slot, $weight, $template, $data = array(), $context = null)
	{
		debug_msg('New object: id = "%s", slot = "%s", weight = %d, template = "%s"', $id, $slot, $weight, $template);

		if (array_key_exists($id, $this->objects)) {
			error_msg('Duplicate ID "%s"!', $id);
			return false;
		} else {
			$this->objects[$id] = array($weight, $slot, $id, $template, $data, $context, $source_block);
			$this->slot_content[$slot][] = & $this->objects[$id];
			return true;
		}
	}


	/**
	 * Set slot option (no arrays allowed)
	 */
	function slotOptionSet($slot, $option, $value)
	{
		if (is_array($value)) {
			error_msg('Slot option must not be array!');
			// FIXME: Why not?
		} else {
			debug_msg('Setting slot option "%s" of slot "%s" = "%s"', $option, $slot, $value);
			$this->slot_options[$slot][$option] = $value;
		}
	}


	/**
	 * Append slot option value to list
	 */
	function slotOptionAppend($slot, $option, $value)
	{
		if (!is_array(@$this->slot_options[$slot][$option])) {
			$this->slot_options[$slot][$option] = array();
		}
		if (is_array($value)) {
			$this->slot_options[$slot][$option] += $value;
		} else {
			$this->slot_options[$slot][$option][] = $value;
		}
	}


	/**
	 * Register reverse router for URL generator
	 */
	function addReverseRouter($router)
	{
		if (is_callable($router)) {
			$this->reverse_router[] = $router;
			return true;
		} else {
			error_msg('Template::addReverseRouter(): Router must be callable (a function)!');
			return false;
		}
	}


	/**
	 * Generate URL from route.
	 */
	function url($route_name, $values /* ... */)
	{
		// collect args
		$args = func_get_args();

		// search & use reverse route
		foreach ($this->reverse_router as $router) {
			$url = call_user_func_array($router, $args);
			if ($url !== false) {
				return $url;
			}
		}
		error_msg('Template::url(): Reverse route "%s" not found in %d reverse router(s) !', $route_name, count($this->reverse_router));
		return false;
	}


	/**
	 * Load template of given type and name.
	 */
	function loadTemplate($output_type, $template_name, $function_name, $indent = '')
	{
		$f = $this->plugin_manager->getTemplateFilename($output_type, $template_name);

		if (is_readable($f)) {
			debug_msg('%s Loading "%s"', $indent, substr($f, strlen(DIR_ROOT)));
			include $f;
		} else {
			debug_msg('%s Can\'t load "%s" - file "%s" not found.', $indent, substr($f, strlen(DIR_ROOT)), str_replace(DIR_ROOT, '', $f));
		}
		return function_exists($function_name);
	}


	/**
	 * Returns true if there are no objects in given slot (or if slot does 
	 * not exist at all).
	 */
	function isSlotEmpty($slot_name)
	{
		return empty($this->slot_content[$slot_name]);
	}


	/**
	 * Render content of the slot to a page (stdout).
	 */
	function processSlot($slot_name)
	{
		static $options = array();
		static $output_type = null;

		$indent = str_repeat(' .', $this->current_slot_depth);
		$this->current_slot_depth++;
		$last_options = $options;

		if (!array_key_exists($slot_name, $this->slot_content)) {
			debug_msg(' %s Slot "%s" is empty.', $indent, $slot_name);
		} else if ($this->slot_content[$slot_name] === false) {
			debug_msg(' %s Slot "%s" is already processed.', $indent, $slot_name);
		} else {
			debug_msg(' %s Processing slot "%s" ...', $indent, $slot_name);

			/* get slot content */
			$content = $this->slot_content[$slot_name];
			$this->slot_content[$slot_name] = false;
			sort($content);		// sort by weight (this is why weight is first)

			/* get slot options & merge with parent slot options */
			$options = array_merge($options, (array) @$this->slot_options[$slot_name]);

			/* special option 'type' sets output type */
			if (isset($options['type'])) {
				$new_output_type = trim(preg_replace('/[^a-z0-9_]+/', '_', strtolower($options['type'])), '_');
				if ($new_output_type != '' && $new_output_type != $output_type) {
					debug_msg(' %s Output type changed to "%s" ...', $indent, $new_output_type);
					$output_type = $new_output_type;
				}
			}

			/* process slot content */
			foreach($content as $obj) {
				list($weight, $slot, $id, $template, $data, $context, $source_block) = $obj;
				
				$tpl_fn = 'TPL_'.$output_type.'__'.str_replace('/', '__', $template);

				if (function_exists($tpl_fn) || $this->loadTemplate($output_type, $template, $tpl_fn, $indent)) {
					debug_msg(' %s Executing "%s" ...', $indent, $template);
					if ($context !== null) {
						$context->updateEnviroment();
					}

					/* call template (can recursively call processSlot()) */
					try {
						if ($this->annotate && $output_type == 'html5' && $source_block) {
							// FIXME: This is specific for html5 output -- move it somewhere else
							echo "<!-- Block: ", htmlspecialchars($source_block->id()), " (", htmlspecialchars($source_block->blockName()), ") -->\n";
						}
						$tpl_fn($this, $id, $data, $options);
					}
					catch (\Exception $ex) {
						error_msg('Template "%s" (object ID: "%s") threw an exception: %s', $template, $id, $ex->getMessage());
						error_msg('Exception: %s', $ex);
					}
				} else {
					error_msg('Failed to load template "%s"! Object ID is "%s".', $template, $id);
				}
			}

			debug_msg(' %s Processing slot "%s" done.', $indent, $slot_name);
		}

		$options = $last_options;
		$this->current_slot_depth--;
	}


	/**
	 * Start rendering. A 'root' slot will be rendered first, all other 
	 * slots must be redered by objects in the 'root' slot.
	 *
	 * A redirect can be specified using slot options (`redirect_url`, 
	 * `redirect_code` and `redirect_message`). Also a HTTP code of the 
	 * response can be set (slot options `http_status_code` and 
	 * `http_status_message`).
	 *
	 * Slot option `type` determines the set of templates used to render 
	 * the objects. Note that this option can be changed in the nested 
	 * slots.
	 */
	function start($return_output = false)
	{
		/*
		header('Content-Type: text/plain');
		print_r($this);
		return;
		// */

		if (isset($this->slot_options['root']['redirect_url']) && $this->redirect_enabled) {
			$redirect_url = $this->slot_options['root']['redirect_url'];
		} else {
			$redirect_url = null;
		}

		/* Show core's name in header */
		header('X-Powered-By: Dynamic Cascade', TRUE);		// FIXME

		/* Send custom status code & message */
		if ($redirect_url) {
			$code    = @$this->slot_options['root']['redirect_code'];
			$message = @$this->slot_options['root']['redirect_message'];
			$code    = $code >= 300 && $code < 400 ? $code : 303;
		} else {
			$code    = @$this->slot_options['root']['http_status_code'];
			$message = @$this->slot_options['root']['http_status_message'];
			$code    = $code >= 100 && $code < 600 ? $code : 200;
		}
		$message = $message ? $message : $this->getHttpStatusMessage($code);
		header(sprintf('HTTP/1.1 '.$code.' '.$message));

		/* process redirect (no output, headers only) */
		if ($redirect_url) {
			session_write_close();
			debug_msg('Redirecting to "%s" (%d %s)', $redirect_url, $code, $message);
			header('Location: '.$redirect_url, TRUE, $code);
			return;
		}

		/* check if type is set */
		if (!isset($this->slot_options['root']['type'])) {
			error_msg('Output type is not specified!');
			return;
		}

		/* load init.php */
		$init_filename = $this->plugin_manager->getTemplateFilename($this->slot_options['root']['type'], 'init');
		if (file_exists($init_filename)) {
			include $init_filename;
		} else {
			$core_init_filename = $this->plugin_manager->getTemplateFilename($this->slot_options['root']['type'], 'core/init');
			if (file_exists($core_init_filename)) {
				include $core_init_filename;
			}
		}

		/* process root slot */
		ob_start();
		$this->processSlot('root');

		/* log what has been missed */
		$missed_slots = array_keys(array_filter($this->slot_content));
		if (!empty($missed_slots)) {
			foreach ($missed_slots as $s) {
				debug_msg('Missed slot: %s (%d objects: %s)', $s, count($this->slot_content[$s]),
						join(', ', array_map(function($a) { return $a[2]; }, $this->slot_content[$s])));
			}
		}

		if ($return_output) {
			$out = ob_get_contents();
			ob_end_clean();
		} else {
			ob_end_flush();
		}
	}


	/**
	 * Convert HTTP status code to textual description (as described in RFC2616).
	 */
	function getHttpStatusMessage($code)
	{
		switch ($code) {
			case 100: return 'Continue';
			case 101: return 'Switching Protocols';
			case 200: return 'OK';
			case 201: return 'Created';
			case 202: return 'Accepted';
			case 203: return 'Non-Authoritative Information';
			case 204: return 'No Content';
			case 205: return 'Reset Content';
			case 206: return 'Partial Content';
			case 300: return 'Multiple Choices';
			case 301: return 'Moved Permanently';
			case 302: return 'Found';
			case 303: return 'See Other';
			case 304: return 'Not Modified';
			case 305: return 'Use Proxy';
			case 307: return 'Temporary Redirect';
			case 400: return 'Bad Request';
			case 401: return 'Unauthorized';
			case 402: return 'Payment Required';
			case 403: return 'Forbidden';
			case 404: return 'Not Found';
			case 405: return 'Method Not Allowed';
			case 406: return 'Not Acceptable';
			case 407: return 'Proxy Authentication Required';
			case 408: return 'Request Timeout';
			case 409: return 'Conflict';
			case 410: return 'Gone';
			case 411: return 'Length Required';
			case 412: return 'Precondition Failed';
			case 413: return 'Request Entity Too Large';
			case 414: return 'Request-URI Too Long';
			case 415: return 'Unsupported Media Type';
			case 416: return 'Requested Range Not Satisfiable';
			case 417: return 'Expectation Failed';
			case 500: return 'Internal Server Error';
			case 501: return 'Not Implemented';
			case 502: return 'Bad Gateway';
			case 503: return 'Service Unavailable';
			case 504: return 'Gateway Timeout';
			case 505: return 'HTTP Version Not Supported';
			default: return 'WTF';
		}
	}
}

