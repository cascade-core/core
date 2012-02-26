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
	private $evaluation_step = 0;	// step counter - time for cascade animations and slot weight penalty
	private $replacement = array();	// module replacement table (aliases)
	private $root_namespace = array();

	private $execution_time = null;	// time [ms] spent in start()
	private $memory_usage = null;	// memory used by pipeline (after minus before)


	public function __construct()
	{
	}


	public function start()
	{
		$mem_usage_before = memory_get_usage();
		$t_start = microtime(TRUE);
		reset($this->queue);
		while((list($id, $m) = each($this->queue))) {
			$m->pc_execute();
		}
		$this->execution_time = (microtime(TRUE) - $t_start) * 1000;
		$this->memory_usage = memory_get_usage() - $mem_usage_before;
	}


	public function set_replacement_table($table)
	{
		if (is_array($table)) {
			$this->replacement = $table;
		} else {
			$this->replacement = array();
		}
	}


	public function get_replacement_table()
	{
		return $this->replacement;
	}


	public function resolve_module_name($mod_name)
	{
		if (($m = @$this->root_namespace[$mod_name])) {
			return $m;
		} else {
			error_msg('Module "%s" not found in root namespace!', $mod_name);
		}
	}


	public function root_namespace_module_names()
	{
		return array_keys($this->root_namespace);
	}


	public function current_step($increment = true)
	{
		if ($increment) {
			return $this->evaluation_step++;
		} else {
			return $this->evaluation_step;
		}
	}


	public function add_module($parent, $id, $module, $force_exec, array $connections, Context $context, & $errors = null, $real_module = null)
	{
		/* check replacement table */
		for ($step = 32; isset($this->replacement[$module]) && $step > 0; $step--) {
			$module = $this->replacement[$module];
		}

		/* check module name */
		if (!is_string($module) || strpos($module, '.') !== FALSE || !ctype_graph($module)) {
			error_msg('Invalid module name: %s', $module);
			$errors[] = array(
				'error'   => 'Invalid module name.',
				'id'      => $id,
				'module'  => $module,
			);
			return false;
		}

		/* check malformed IDs */
		if (!is_string($id) || $id == '' || !ctype_alpha($id[0]) || !ctype_graph($id)) {
			error_msg('Invalid module ID: %s', $id);
			$errors[] = array(
				'error'   => 'Invalid module ID.',
				'id'      => $id,
				'module'  => $module,
			);
			return false;
		}

		/* build full ID */
		$full_id = ($parent ? $parent->id().'.' : '').$id;

		/* check for duplicate IDs */
		if (array_key_exists($full_id, $this->modules)) {
			error_msg('Module ID "%s" already exists in pipeline!', $id);
			$errors[] = array(
				'error'   => 'Module ID already exists in pipeline.',
				'id'      => $id,
				'module'  => $module,
			);
			return false;
		}

		/* build class name */
		$class = 'M_'.str_replace('/', '__', $module);

		/* check permissions */
		if (!$context->is_allowed($module, $details)) {
			if ($details != '') {
				error_msg('Permission denied to module %s (%s).', $module, $details);
			} else {
				error_msg('Permission denied to module %s.', $module);
			}
			$errors[] = array(
				'error'   => 'Permission denied.',
				'id'      => $id,
				'module'  => $module,
				'details' => $details,
			);
			$this->add_failed_module($parent, $id, $full_id, $real_module !== null ? $real_module : $module, $connections);
			return false;
		}


		/* kick autoloader */
		if (class_exists($class)) {

			/* initialize module */
			$m = new $class();
			$m->pc_init($parent, $id, $full_id, $this, $real_module !== null ? $real_module : $module, $context);
			if (!$m->pc_connect($connections, $this->modules)) {
				error_msg('Module "%s": Can\'t connect inputs!', $id);
				$errors[] = array(
					'error'   => 'Can\'t connect inputs.',
					'id'      => $id,
					'module'  => $module,
					'inputs'  => $connections,
				);
				$this->add_failed_module($parent, $id, $full_id, $real_module !== null ? $real_module : $module, $connections);
				return false;
			}

			/* put module to parent's namespace */
			if ($parent) {
				$parent->pc_register_module($m);
			} else {
				$this->root_namespace[$id] = $m;
			}

			/* add module to queue */
			$this->modules[$id] = $m;
			if ($force_exec === null ? $class::force_exec : $force_exec) {
				$this->queue[] = $m;
			}

			return true;

		} else {
			/* class not found, check if ini file exists */
			$f = get_module_filename($module, '.ini.php');

			if ($module != 'core/ini/proxy' && is_file($f)) {
				/* load core/ini/proxy for this ini file */
				debug_msg('Loading core/ini/proxy for "%s".', $f);
				return $this->add_module($parent, $id, 'core/ini/proxy', $force_exec, $connections, $context, $errors, $module);

			} else {
				/* module not found */
				error_msg('Module "%s" not found.', $module);
				$errors[] = array(
					'error'   => 'Module not found.',
					'id'      => $id,
					'module'  => $module,
				);
				$this->add_failed_module($parent, $id, $full_id, $module, $connections);
				return false;
			}
		}
	}


	private function add_failed_module($parent, $id, $full_id, $real_module, array $connections)
	{
		/* create dummy module */
		$m = new M_core__dummy();
		$m->pc_init($parent, $id, $full_id, $this, $real_module, null, Module::FAILED);
		$m->pc_connect($connections, $this->modules);

		/* put module to parent's namespace */
		if ($parent) {
			$parent->pc_register_module($m);
		} else {
			$this->root_namespace[$id] = $m;
		}

		return true;
	}


	public function add_modules_from_ini($parent, $parsed_ini_with_sections, Context $context, & $errors = null)
	{
		$all_good = true;

		/* walk thru ini and take 'module:*' sections */
		foreach ($parsed_ini_with_sections as $section => $opts) {
			@list($keyword, $id) = explode(':', $section, 2);
			if ($keyword == 'module' && isset($id) && @($module = $opts['.module']) !== null) {
				$force_exec = @ $opts['.force-exec'];

				/* drop module options and keep only connections */
				unset($opts['.module']);
				unset($opts['.force-exec']);

				/* parse connections */
				foreach($opts as & $out) {
					if (is_array($out)) {
						if (count($out) == 1) {
							// single connection
							$out = explode(':', $out[0], 2);
						} else {
							// multiple connections
							$outs = array(null);
							foreach ($out as $o) {
								if ($o[0] == ':') {
									$outs[0] = $o;
								} else {
									list($o_mod, $o_out) = explode(':', $o, 2);
									$outs[] = $o_mod;
									$outs[] = $o_out;
								}
							}
							$out = $outs;
						}
					}
				}

				$all_good &= $this->add_module($parent, $id, $module, $force_exec, $opts, $context, $errors);
			}
		}

		return $all_good;
	}


	public function dump_namespaces()
	{
		$str = '';
		foreach ($this->root_namespace as $name => $m) {
			$str .= $name."\n".$m->pc_dump_namespace(1);
		}
		return $str;
	}


	public function get_execution_times($old_stats = null)
	{
		$cnt = 0;
		$sum = 0.0;

		if (is_array($old_stats)) {
			$by_module = $old_stats['modules'];
		} else {
			$by_module = array();
		}

		foreach($this->modules as $m) {
			$t = $m->pc_execution_time();
			if ($t > 0) {
				$cnt++;
				$sum += $t;
				$bm = & $by_module[$m->module_name()];
				@$bm['sum'] += $t;		// arsort() uses first field in array
				@$bm['cnt']++;
				@$bm['min'] = $bm['min'] === null ? $t : min($t, $bm['min']);
				@$bm['max'] = max($t, $bm['max']);
			}
		}

		if (is_array($old_stats)) {
			return array(
				'total_time' => $this->execution_time + $old_stats['total_time'],
				'pipeline_time' => ($this->execution_time - $sum) + $old_stats['pipeline_time'],
				'pipeline_count' => 1 + $old_stats['pipeline_count'],
				'modules_time' => $sum + $old_stats['modules_time'],
				'modules_count' => $cnt + $old_stats['modules_count'],
				'modules' => $by_module
			);
		} else {
			return array(
				'total_time' => $this->execution_time,
				'pipeline_time' => ($this->execution_time - $sum),
				'pipeline_count' => 1,
				'modules_time' => $sum,
				'modules_count' => $cnt,
				'modules' => $by_module
			);
		}
	}


	public function get_memory_usage()
	{
		return $this->memory_usage;
	}


	public function export_graphviz_dot($doc_link, $whitelist = array(), $step = null)
	{
		$colors = array(
			Module::QUEUED   => '#eeeeee',	// grey
			Module::RUNNING  => '#aaccff',	// blue -- never used
			Module::ZOMBIE   => '#ccffaa',	// green
			Module::DISABLED => '#cccccc',	// dark grey
			Module::FAILED   => '#ffccaa',	// red
		);

		if ($step === null) {
			$step = $this->current_step(false) + 1;
		}

		$gv =	 "#\n"
			."# Pipeline visualization\n"
			."#\n"
			."# Step: ".$step."\n"
			."#\n"
			."# Use \"dot -Tpng this-file.gv -o this-file.png\" to compile.\n"
			."#\n"
			."digraph structs {\n"
			."	rankdir = LR;\n"
			."	margin = 0;\n"
			."	bgcolor = transparent;\n"
			."	edge [ arrowtail=none, arrowhead=normal, arrowsize=0.6 ];\n"
			."	node [ shape=none, fontsize=7, fontname=\"sans\" ];\n"
			."	graph [ shape=none, color=blueviolet, fontcolor=blueviolet, fontsize=9, fontname=\"sans\" ];\n"
			."\n";

		list($clusters, $specs) = $this->export_graphviz_dot_namespace($this->root_namespace, $colors, $doc_link, array_flip($whitelist), $step);
		$gv .= $clusters;
		$gv .= $specs;
		$gv .= "}\n";

		return $gv;
	}

	private function export_graphviz_dot_namespace($namespace, $colors, $doc_link, $whitelist = array(), $step = null, $indent = "\t")
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
			list($t_create, $t_start, $t_finish) = $module->get_timestamps();

			$is_created  = $t_create < $step;
			$is_started  = $t_start < $step;
			$is_running  = $t_start < $step && $t_finish >= $step;
			$is_finished = $t_finish < $step;

			/* add module header */
			$subgraph .= $indent."m_".get_ident($id).";\n";
			$gv .=	 "\tm_".get_ident($id)." [URL=\"".sprintf($doc_link, $module->module_name())."\",target=\"_blank\","
						.($is_created ? '':'fontcolor="#eeeeee",')
						."label=<<table border=\"1\"".($is_created ? '':' color="#eeeeee"')
								." bgcolor=\"#ffffff\" cellborder=\"0\" cellspacing=\"0\">\n"
				."	<tr>\n"
				."		<td bgcolor=\"".($is_created
									? $colors[$is_running
										? Module::RUNNING
										: ($is_finished ? $module->status() : Module::QUEUED)]
									:'#ffffff'
								)."\" colspan=\"2\">\n"
				."			<font face=\"sans bold\">".htmlspecialchars($module->id())."</font><br/>\n"
				."			<font face=\"sans italic\">".htmlspecialchars($module->module_name())."</font>\n"
				."		</td>\n"
				."	</tr>\n";


			$gv_inputs = '';
			$inputs  = $module->pc_inputs();
			$input_names = array();
			$input_functions = array();
			$output_names = $module->pc_outputs();

			/* connect inputs */
			foreach ($inputs as $in => $out) {
				if (is_array($out)) {
					$input_names[] = $in;
					if (!is_object($out[0]) && $out[0][0] == ':') {
						$function = $out[0];
						$input_functions[$in] = $function;
					} else {
						$function = null;
					}
					$n = count($out);
					for ($i = $function !== null ? 1 : 0; $i < $n - 1; $i += 2) {
						$out_mod = $out[$i];
						$out_name = $out[$i + 1];

						if (!is_object($out_mod) && ($resolved = $module->pc_resolve_module_name($out_mod))) {
							$out_mod = $resolved;
						}

						$out_mod_id = is_object($out_mod) ? $out_mod->full_id() : $out_mod;

						$missing = true;
						$zero = true;
						$big = false;

						if (!is_object($out_mod)) {
							$missing_modules[$out_mod] = true;
							$missing = !array_key_exists($out_mod, $whitelist);
						} else if ($out_mod->pc_output_exists($out_name)) {
							$missing = false;
							$v = $out_mod->pc_output_cache();
							if (array_key_exists($out_name, $v)) {
								$v = $v[$out_name];
								$exists = true;
								$zero = empty($v);
								$big = is_array($v) || is_object($v);
							} else {
								$v = null;
								$exists = false;
								$zero = true;
								$big = false;
							}
						}

						$gv_inputs .= "\tm_".get_ident($out_mod_id).($exists ? ':o_'.get_ident($out_name).':e' : '')
							.' -> m_'.get_ident($id).':i_'.get_ident($in).':w'
							.($is_created
								? ($missing
									? " [color=red]"
									: ($zero
										? ' [color=dimgrey,penwidth=0.8]'
										: ($big ? ' [penwidth=2]':''))
									)
								: '[color="#eeeeee"]'
							).";\n";
					}
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
						$gv .= "\t\t\t<td align=\"left\"  port=\"i_".get_ident($in)."\">"
							.htmlspecialchars($in)
							.(isset($input_functions[$in]) ? ' ('.htmlspecialchars($input_functions[$in]).')' : '')
							."</td>\n";
					} else {
						$gv .= "\t\t\t<td></td>\n";
					}
					if ($out !== false) {
						$gv .= "\t\t\t<td align=\"right\" port=\"o_".get_ident($out)."\">"
							.htmlspecialchars($out)."</td>\n";
					} else {
						$gv .= "\t\t\t<td></td>\n";
					}
					$gv .= "\t\t</tr>\n";
				}

				$in = next($input_names);
				$in_fn = next($input_functions);
				$out = next($output_names);
			}

			/*
			$et = $module->pc_execution_time();
			if ($et > 10) {
				$gv .=   "	<tr>\n"
					."		<td align=\"center\" colspan=\"2\">\n"
					."			<font point-size=\"6\" color=\"dimgrey\">"
									.sprintf('~%d ms', round($et, -log($et, 10)))."</font>\n"
					."		</td>\n"
					."	</tr>\n";
			}
			// */

			$gv .=	"\t\t</table>>];\n";
			$gv .=	$gv_inputs;

			/* connect forwarded outputs */
			foreach ($module->pc_forwarded_outputs() as $name => $src) {
				list($src_mod, $src_out) = $src;
				if (!is_object($src_mod) && ($resolved = $module->pc_resolve_module_name($src_mod))) {
					$src_mod = $resolved;
				}
				$src_mod_id = is_object($src_mod) ? $src_mod->full_id() : $src_mod;
				$v = is_object($src_mod) ? $src_mod->pc_output_cache($src_out) : array();
				$gv .= "\tm_".get_ident($id).":o_".get_ident($name).":e -> m_".get_ident($src_mod_id)
						.(array_key_exists($src_out, $v) ? ":o_".get_ident($src_out).":e":'')
					.($is_created ? "[color=royalblue" : '[color="#eeeeee"').",arrowhead=dot,arrowtail=none,dir=both,weight=0];\n";
			}

			$gv .= "\n";

			/* recursively draw sub-namespaces */
			$child_namespace = $module->pc_get_namespace();
			if (!empty($child_namespace)) {
				list($child_sub, $child_specs) = $this->export_graphviz_dot_namespace($child_namespace, $colors, $doc_link,
										$whitelist, $step, $indent."\t");
				$subgraph .= "\n"
					.$indent."subgraph cluster_".get_ident($id)." {\n"
					.$indent."\tlabel = \"".$id."\";\n\n"
					.($is_started ? '' : $indent."\tcolor=\"#eeeeee\"; fontcolor=\"#eeeeee\";\n")
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
				$gv .= "\t m_".get_ident($module)." [color=".(array_key_exists($module, $whitelist) ? "dimgrey" : "red")
						.", shape=ellipse, label=$label, padding=0];\n";
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

