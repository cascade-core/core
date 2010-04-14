<?php
/*
 * Copyright (c) 2010, Josef Kufner  <jk@frozen-doe.net>
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

	private $queue = array();	// waiting modules
	private $modules = array();	// all existing modules
	private $add_order = 1;		// insertion serial number - slot weight modifier
	private $replacement = array();	// module replacement table (aliases)
	private $root_namespace = array();

	public function __construct()
	{
	}


	public function start()
	{
		reset($this->queue);
		while((list($id, $m) = each($this->queue))) {
			$m->pc_execute();
		}
	}


	public function set_replacement_table($table)
	{
		if (is_array($table)) {
			$this->replacement = $table;
		} else {
			$this->replacement = array();
		}
	}


	public function add_module($parent, $id, $module, $force_exec, array $connections, Context $context)
	{
		/* check replacement table */
		for ($step = 32; isset($this->replacement[$module]) && $step > 0; $step--) {
			$module = $this->replacement[$module];
		}

		/* check module name */
		if (!is_string($module) || strpos($module, '.') !== FALSE || !ctype_graph($module)) {
			error_msg('Invalid module name: %s', $module);
			return false;
		}

		/* check malformed IDs */
		if (!is_string($id) || $id == '' || !ctype_alpha($id[0]) || !ctype_graph($id)) {
			error_msg('Invalid module ID: %s', $id);
			return false;
		}

		/* check for duplicate IDs */
		if (array_key_exists($id, $this->modules)) {
			error_msg('Module ID "%s" already exists in pipeline!', $id);
			return false;
		}

		/* build class name */
		$class = 'M_'.str_replace('/', '__', $module);

		/* skip autoloader -- there are different rules */
		if (!class_exists($class)) {
			$m = strtolower(str_replace('__', '/', substr($class, 2)));
			include(strncmp($m, 'core/', 5) == 0 ? DIR_CORE_MODULE.substr($m, 5).'.php' : DIR_APP_MODULE.$m.'.php');

			if (!class_exists($class)) {
				/* module not found */
				// todo: module not found message (alternative placeholder module?)
				error_msg('Module "%s" not found.', $module);
				return false;
			}
		}

		/* check permissions */
		if (false) {
			/* TODO */
			return false;
		}

		/* initialize module */
		$m = new $class();
		$m->pc_init($parent, $id, $this, $module, $context, $this->add_order);
		if (!$m->pc_connect($connections, $this->modules)) {
			error_msg('Module "%s": Can\'t connect inputs!', $id);
			return false;
		}
		$this->add_order++;

		/* put module to parent's namespace */
		if ($parent) {
			$parent->pc_register_module($m);
		} else {
			$this->root_namespace[$id] = $m;
		}

		/* add module to queue */
		$this->modules[$id] = $m;

		if ($force_exec) {
			$this->queue[] = $m;
		}
		return $m;
	}


	public function add_modules_from_ini($parent, $parsed_ini_with_sections, Context $context)
	{
		/* walk thru ini and take 'module:*' sections */
		foreach ($parsed_ini_with_sections as $section => $opts) {
			@list($keyword, $id) = explode(':', $section, 2);
			if ($keyword == 'module' && isset($id) && @($module = $opts['.module']) !== null) {
				$force_exec = !empty($opts['.force-exec']);

				/* drop module options and keep only connections */
				unset($opts['.module']);
				unset($opts['.force-exec']);

				/* parse connections */
				foreach($opts as & $out) {
					if (is_array($out) && count($out) == 1) {
						$out = explode(':', $out[0], 2);
					}
				}

				$this->add_module($parent, $id, $module, $force_exec, $opts, $context);
			}
		}
	}


	public function resolve_module_name($mod_name)
	{
		if (($m = $this->root_namespace[$mod_name])) {
			return $m;
		} else {
			error_msg('Module "%s" not found in root namespace!', $mod_name);
		}
	}


	public function dump_namespaces()
	{
		$str = '';
		foreach ($this->root_namespace as $name => $m) {
			$str .= $name."\n".$m->pc_dump_namespace(1);
		}
		return $str;
	}


	public function export_graphviz_dot()
	{
		$colors = array(
			Module::QUEUED   => '#eeeeee',	// grey
			Module::RUNNING  => '#aaccff',	// blue -- never used
			Module::ZOMBIE   => '#ccffaa',	// green
			Module::DISABLED => '#cccccc',	// dark grey
			Module::FAILED   => '#ffccaa',	// red
		);

		$gv =	 "#\n"
			."# Pipeline visualization\n"
			."#\n"
			."# Use \"dot -Tpng this-file.gv -o this-file.png\" to compile.\n"
			."#\n"
			."digraph structs {\n"
			."	rankdir = LR;\n"
			."	edge [ arrowtail=none, arrowhead=normal, arrowsize=0.6 ];\n"
			."	node [ shape=none, fontsize=7, fontname=\"sans\" ];\n"
			."	subgraph [ shape=none, color=blueviolet, fontcolor=blueviolet, fontsize=9, fontname=\"sans\" ];\n"
			."\n";

		list($clusters, $specs) = $this->export_graphviz_dot_namespace($this->root_namespace, $colors);
		$gv .= $clusters;
		$gv .= $specs;
		$gv .= "}\n";

		return $gv;
	}

	private function export_graphviz_dot_namespace($namespace, $colors, $indent = "\t")
	{
		$missing_modules = array();
		$gv = '';
		$clusters = '';
		$subgraph = '';

		foreach ($namespace as $id => & $module) {
			/* skip specials */
			if ($id == 'parent' || $id == 'self') {
				continue;
			}

			$id = $module->full_id();

			/* add module header */
			$subgraph .= $indent."m_".get_ident($id).";\n";
			$gv .=	 "\tm_".get_ident($id)." [label=<<table border=\"1\" cellborder=\"0\" cellspacing=\"0\">\n"
				."	<tr>\n"
				."		<td bgcolor=\"".$colors[$module->status()]."\" colspan=\"2\">\n"
				."			<font face=\"sans bold\">".htmlspecialchars($module->id())."</font><br/>\n"
				."			<font face=\"sans italic\">".htmlspecialchars($module->module_name())."</font>\n"
				."		</td>\n"
				."	</tr>\n";


			$gv_inputs = '';
			$inputs  = $module->pc_inputs();
			$input_names = array();
			$output_names = $module->pc_outputs();

			/* connect inputs */
			foreach ($inputs as $in => $out) {
				if (is_array($out)) {
					list($out_mod, $out_name) = $out;
					$input_names[] = $in;

					$out_mod_id = is_object($out_mod) ? $out_mod->full_id() : $out_mod;

					if (!is_object($out_mod)) {
						$missing_modules[$out_mod] = true;
						$missing = true;
					} else if (!$out_mod->pc_output_exists($out_name)) {
						$missing = true;
					} else {
						$missing = false;
						$v = $out_mod->pc_output_cache();
						$v = $v[$out_name];
						$zero = empty($v);
						$big = is_array($v) || is_object($v);
					}

					$gv_inputs .= "\tm_".get_ident($out_mod_id).":o_".get_ident($out_name).":e -> m_".get_ident($id).":i_".get_ident($in).':w'
						.($missing ? " [color=red]":($zero ? ' [color=dimgrey,penwidth=0.8]':($big ? ' [penwidth=2]':''))).";\n";
				}
			}

			reset($input_names);
			reset($output_names);
			$in = current($input_names);
			$out = current($output_names);

			/* add module inputs and outputs */
			while($in !== false || $out !== false) {
				while ($in === '*') {
					$in = next($input_names);
				}
				while ($out === '*') {
					$out = next($output_names);
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

				$in = next($input_names);
				$out = next($output_names);
			}

			$gv .=	"\t\t</table>>];\n";
			$gv .=	$gv_inputs;

			/* connect forwarded outputs */
			foreach ($module->pc_forwarded_outputs() as $name => $src) {
				list($src_mod, $src_out) = $src;
				$src_mod_id = is_object($src_mod) ? $src_mod->full_id() : $src_mod;
				$gv .= "\tm_".get_ident($src_mod_id).":o_".get_ident($src_out).":e -> m_".get_ident($id).":o_".get_ident($name).':e'
					."[color=royalblue,arrowhead=dot,weight=min];\n";
			}

			$gv .= "\n";

			/* recursively draw sub-namespaces */
			$child_namespace = $module->pc_get_namespace();
			if (count($child_namespace) > 2) {
				list($child_sub, $child_specs) = $this->export_graphviz_dot_namespace($child_namespace, $colors, $indent."\t");
				$subgraph .= "\n"
					.$indent."subgraph cluster_".get_ident($id)." {\n"
					.$indent."\tlabel = \"".$id."\";\n\n"
					.$child_sub
					.$indent."}\n"
					."\n";
				$gv .= $child_specs;
			}
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

		return array($subgraph, $gv);
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

