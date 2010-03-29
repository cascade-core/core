<?php
/*
 * Copyright (c) 2010, Josef Kufner  <jk@myserver.cz>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. Neither the name of the author nor the names of its contributors
 *    may be used to endorse or promote products derived from this software
 *    without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE REGENTS AND CONTRIBUTORS ``AS IS'' AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED.  IN NO EVENT SHALL THE REGENTS OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
 * OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * SUCH DAMAGE.
 */

class Template {

	private $objects = array();
	private $current_slot_depth = 0;

	// TODO !!!

	function add_object($id, $slot, $weight, $template, $data = array())
	{
		if (array_key_exists($id, $this->objects)) {
			error_msg('Duplicate ID "%s"!', $id);
			return false;
		} else {
			$this->objects[$id] = array($weight, $slot, $id, $template, $data);
			$this->slot_content[$slot][] = & $this->objects[$id];
			return true;
		}
	}


	function process_slot($slot_name)
	{
		$indent = str_repeat(' .', $this->current_slot_depth);
		$this->current_slot_depth++;

		if (!array_key_exists($slot_name, $this->slot_content)) {
			debug_msg('%s Slot "%s" is empty.', $indent, $slot_name);
		} else if ($this->slot_content[$slot_name] === false) {
			debug_msg('%s Slot "%s" is already processed.', $indent, $slot_name);
		} else {
			debug_msg('%s Processing slot "%s" ...', $indent, $slot_name);
			$content = $this->slot_content[$slot_name];
			$this->slot_content[$slot_name] = false;

			foreach($content as $obj) {
				list($weight, $slot, $id, $template, $data) = $obj;
				
				$tpl_fn = 'TPL_'.str_replace('/', '__', $template);

				if (function_exists($tpl_fn)) {
					debug_msg('%s Executing preloaded "%s" ...', $indent, $template);
					$tpl_fn($this, $data);
				} else {
					// FIXME
					$f = DIR_CORE_TEMPLATE.'xhtml/'.preg_replace('|^core/|', '', $template).'.php';
					debug_msg('%s Loading "%s"', $indent, substr($f, strlen(DIR_ROOT)));
					include $f;

					if (function_exists($tpl_fn)) {
						debug_msg('%s Executing "%s" ...', $indent, $template);
						$tpl_fn($this, $data);
					} else {
						error_msg('Failed to load template "%s"! Object ID is "%s".', $template, $id);
					}
				}
			}

			debug_msg('%s Processing slot "%s" done.', $indent, $slot_name);
		}

		$this->current_slot_depth--;
	}


	function start($return_output = false)
	{
		/*
		header('Content-Type: text/plain');
		print_r($this);
		return;
		// */

		ob_start();
		$this->process_slot('root');

		if ($return_output) {
			$out = ob_get_contents();
			ob_end_clean();
		} else {
			ob_end_flush();
		}
	}
}

// vim:encoding=utf8:
?>
