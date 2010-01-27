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

class PipelineController {

	private $queue = array();	// moduly cekajici na provedeni
	private $modules = array();	// moduly existujici v pipeline


	public function __construct()
	{
	}


	public function start()
	{
		reset($this->queue);
		while((list($id, $m) = each($this->queue))) {
			$m->pc_execute($this->modules);
		}
	}


	public function add_module($id, $module, $force_exec = false, $connections = array())
	{
		/* check module name */
		if (!is_string($module) || strpos($module, '.') !== FALSE) {
			error_msg('Invalid module name: %s', $module);
			return false;
		}

		/* check for duplicate IDs */
		if (array_key_exists($id, $this->modules)) {
			error_msg('Module ID "%s" already exists in pipeline!', $id);
			return false;
		}

		/* build class name */
		$class = 'M_'.str_replace('/', '__', $module);

		/* skip autoloader (and another str_replace()) */
		if (!class_exists($class)) {
			$f = strtolower($class).'.php';
			$cf = DIR_CORE_CLASS.$f;
			$af = DIR_APP_CLASS.$f;

			if (is_readable($cf)) {
				include($cf);
			} else if (is_readable($af)) {
				include($af);
			}

			if (!class_exists($class)) {
				/* module not found */
				// todo: module not found message
				error_msg('Module "%s" not found.', $module);
			}
		}

		/* check permissions */
		if (false) {
			/* TODO */
			return false;
		}

		/* initialize module */
		$m = new $class();
		$m->pc_init($id, $this, $module);
		if (!$m->pc_connect($connections, $this->modules)) {
			error_msg('Module "%s": Can\'t connect inputs!', $id);
			return false;
		}

		/* add module to queue */
		$this->modules[$id] = $m;

		if ($force_exec) {
			$this->queue[] = $m;
		}
		return $m;
	}


	public function export_graphviz_dot()
	{
		$colors = array(
			Module::QUEUED  => '#eeeeee',	// grey
			Module::RUNNING => '#aaccff',	// blue -- never used
			Module::ZOMBIE  => '#ccffaa',	// green
			Module::FAILED  => '#ffccaa',	// red
		);
		$missing_modules = array();

		$gv =	 "#\n"
			."# Generated at ".strftime('%F %T')."\n"
			."#\n"
			."# Use \"dot -Tpng this-file.gv -o this-file.png\" to compile.\n"
			."#\n"
			."digraph structs {\n"
			."	rankdir = LR;\n"
			."	edge [ arrowtail=none, arrowhead=normal, arrowsize=0.6 ];\n"
			."	node [ shape=none, fontsize=8 ];\n"
			."\n";

		foreach ($this->modules as $id => & $module) {
			/* add module header */
			$gv .=	 "	m_".get_ident($id)." [label=<<table border=\"1\" cellborder=\"0\" cellspacing=\"0\">\n"
				."		<tr>\n"
				."			<td bgcolor=\"".$colors[$module->status()]."\" colspan=\"2\">\n"
				."				<font face=\"Sans Bold\">".htmlspecialchars($id)."</font><br/>\n"
				."				<font face=\"Sans Italic\" point-size=\"7\">".htmlspecialchars($module->module_name())."</font>\n"
				."			</td>\n"
				."		</tr>\n";

			$inputs  = array_keys($module->pc_inputs());
			$outputs = array_keys($module->pc_outputs());

			reset($inputs);
			reset($outputs);
			$in = current($inputs);
			$out = current($outputs);

			/* add module inputs and outputs */
			while($in !== false || $out !== false) {
				while ($in === '*') {
					$in = next($inputs);
				}
				while ($out === '*') {
					$out = next($outputs);
				}

				if ($in !== false || $out !== false) {
					$gv .=	"\t\t<tr>\n";
					if ($in !== false) {
						$gv .= "\t\t\t<td align=\"left\"  port=\"i_".get_ident($in)."\">".htmlspecialchars($in)."</td>\n";
					} else {
						$gv .= "\t\t\t<td></td>\n";
					}
					if ($out !== false) {
						$gv .= "\t\t\t<td align=\"right\" port=\"o_".get_ident($out)."\">".htmlspecialchars($out)."</td>\n";
					} else {
						$gv .= "\t\t\t<td></td>\n";
					}
					$gv .= "\t\t</tr>\n";
				}

				$in = next($inputs);
				$out = next($outputs);
			}

			$gv .=	"\t\t</table>>];\n";

			/* connect inputs */
			foreach ($module->pc_inputs() as $in => $out) {
				if (is_array($out)) {
					list($out_mod, $out_name) = $out;

					if (is_object($out_mod)) {
						$out_mod = $out_mod->id();
					}
					if (@$this->modules[$out_mod] === null) {
						$missing_modules[$out_mod] = true;
						$missing = true;
					} else if (!$this->modules[$out_mod]->pc_output_exists($out_name)) {
						$missing = true;
					} else {
						$missing = false;
					}

					$gv .= "\tm_".get_ident($out_mod).":o_".get_ident($out_name).":e -> m_".get_ident($id).":i_".get_ident($in).':w'
						.($missing ? " [color=red]":'').";\n";
				}
			}
			$gv .= "\n";
		}

		/* add missing modules */
		if (!empty($missing_modules)) {
			foreach ($missing_modules as $module => $t) {
				if ((string) $module == '') {
					$label = '<<font face="Sans Italic">null</font>>';
				} else {
					$label = '"'.addcslashes($module, '"\\').'"';
				}
				$gv .= "\t m_".get_ident($module)." [color=\"#ff6666\", shape=ellipse, label=$label, padding=0];\n";
			}
		}

		$gv .= "}\n";

		return $gv;
	}


	public function exec_dot($dot_source, $out_type, $out_file = null)
	{
		$descriptorspec = array(
			0 => array('pipe', 'r'),
			1 => ($out_file == null ? array('pipe', 'w') : array('file', $out_file, 'w')),
		);
		$pipe = null;

		$proc = proc_open('dot -T '.escapeshellarg($out_type), $descriptorspec, $pipe);

		if (is_resource($proc)) {

			/* send dot source */
			fwrite($pipe[0], $dot_source);
			fclose($pipe[0]);

			if ($out_file == null) {
				/* load result */
				$result = stream_get_contents($pipe[1]);
				fclose($pipe[1]);

				$ret_code = proc_close($proc);
				return ($ret_code == 0 ? $result : false);
			} else {
				$ret_code = proc_close($proc);
				return ($ret_code == 0 ? true : false);
			}
		} else {
			return false;
		}
	}
}

// vim:encoding=utf8:
?>
