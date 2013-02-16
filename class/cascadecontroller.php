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

class CascadeController {

	private $queue = array();	// waiting blocks
	private $blocks = array();	// all existing blocks
	private $evaluation_step = 0;	// step counter - time for cascade animations and slot weight penalty
	private $replacement = array();	// block replacement table (aliases)
	private $root_namespace = array();

	private $execution_time = null;	// time [ms] spent in start()
	private $memory_usage = null;	// memory used by cascade (after minus before)

	private $auth = null;
	private $block_storages = array();


	public function __construct(IAuth $auth = null, $replacement_table)
	{
		$this->auth = $auth;

		if ($replacement_table != null) {
			$this->replacement = $replacement_table;
		}
	}


	/**
	 * Create new instance of cascade controller and copy current 
	 * configuration. Useful for making preview of cascade.
	 */
	public function cloneEmpty()
	{
		$other = new CascadeController($this->auth, $this->replacement);
		$other->block_storages = $this->block_storages;
		return $other;
	}


	public function start()
	{
		$mem_usage_before = memory_get_usage();
		$t_start = microtime(TRUE);
		reset($this->queue);
		while((list($id, $b) = each($this->queue))) {
			$b->cc_execute();
		}
		$this->execution_time = (microtime(TRUE) - $t_start) * 1000;
		$this->memory_usage = memory_get_usage() - $mem_usage_before;
	}


	public function getAuth()
	{
		return $this->auth;
	}


	public function getReplacementTable()
	{
		return $this->replacement;
	}


	public function addBlockStorage(IBlockStorage $storage, $storage_id)
	{
		$this->block_storages[$storage_id] = $storage;
	}


	public function getBlockStorages()
	{
		return $this->block_storages;
	}


	public function resolveBlockName($block_name)
	{
		if (($b = @$this->root_namespace[$block_name])) {
			return $b;
		} else {
			error_msg('Block "%s" not found in root namespace!', $block_name);
		}
	}


	public function rootNamespaceBlockNames()
	{
		return array_keys($this->root_namespace);
	}


	public function currentStep($increment = true)
	{
		if ($increment) {
			return $this->evaluation_step++;
		} else {
			return $this->evaluation_step;
		}
	}


	private function createBlockInstance($block, & $errors = null, & $storage_name = null)
	{
		/* check replacement table */
		for ($step = 32; is_string($block) && isset($this->replacement[$block]) && $step > 0; $step--) {
			$block = $this->replacement[$block];
		}

		/* check block name */
		if (!is_string($block) || strpos($block, '.') !== FALSE || !ctype_graph($block)) {
			error_msg('Invalid block name: %s', $block);
			$errors[] = array(
				'error'   => 'Invalid block name.',
				'id'      => $id,
				'block'   => $block,
			);
			return false;
		}

		/* check permissions */
		if ($this->auth !== null && !$this->auth->isBlockAllowed($block, $details)) {
			if ($details != '') {
				error_msg('Permission denied to block %s (%s).', $block, $details);
			} else {
				error_msg('Permission denied to block %s.', $block);
			}
			$errors[] = array(
				'error'   => 'Permission denied.',
				'id'      => $id,
				'full_id' => $full_id,
				'block'   => $block,
				'details' => $details,
			);
			$this->addFailedBlock($parent, $id, $full_id, $real_block !== null ? $real_block : $block, $connections);
			return false;
		}

		/* ask storages in specified order until block is created */
		foreach ($this->block_storages as $s_name => $s) {
			/* create instance or try next storage */
			$b = $s->createBlockInstance($block);
			if ($b) {
				$storage_name = $s_name;
				return $b;
			}
		}

		return false;
	}


	public function addBlock($parent, $id, $block, $force_exec, array $connections, Context $context, & $errors = null, $real_block = null)
	{
		/* check malformed IDs */
		if (!is_string($id) || $id == '' || !ctype_alpha($id[0]) || !ctype_graph($id)) {
			error_msg('Invalid block ID: %s', $id);
			$errors[] = array(
				'error'   => 'Invalid block ID.',
				'id'      => $id,
				'block'   => $block,
			);
			return false;
		}

		/* build full ID */
		$full_id = ($parent ? $parent->fullId() : '').'.'.$id;

		/* check for duplicate IDs */
		if (array_key_exists($full_id, $this->blocks)) {
			error_msg('Block ID "%s" already exists in cascade!', $full_id);
			$errors[] = array(
				'error'   => 'Block ID already exists in cascade.',
				'id'      => $id,
				'full_id' => $full_id,
				'block'   => $block,
			);
			return false;
		}

		/* create block instance */
		$b = $this->createBlockInstance($block, $errors, $storage_name);
		if (!$b) {
			/* block not found */
			error_msg('Block "%s" not found.', $block);
			$errors[] = array(
				'error'   => 'Block not found.',
				'id'      => $id,
				'full_id' => $full_id,
				'block'   => $block,
			);
			$this->addFailedBlock($parent, $id, $full_id, $block, $connections);
			return false;
		}

		debug_msg('Adding block "%s" (%s) from %s', $full_id, $block, $storage_name);

		/* initialize and connect block */
		$b->cc_init($parent, $id, $full_id, $this, $real_block !== null ? $real_block : $block, $context);
		if (!$b->cc_connect($connections, $this->blocks)) {
			error_msg('Block "%s": Can\'t connect inputs!', $full_id);
			$errors[] = array(
				'error'   => 'Can\'t connect inputs.',
				'id'      => $id,
				'full_id' => $full_id,
				'block'   => $block,
				'inputs'  => $connections,
			);
			$this->addFailedBlock($parent, $id, $full_id, $real_block !== null ? $real_block : $block, $connections);
			return false;
		}

		/* put block to parent's namespace */
		if ($parent) {
			$parent->cc_registerBlock($b);
		} else {
			$this->root_namespace[$id] = $b;
		}

		/* add block to queue */
		$this->blocks[$full_id] = $b;
		if ($force_exec === null ? $b::force_exec : $force_exec) {
			$this->queue[] = $b;
		}

		return true;
	}


	private function addFailedBlock($parent, $id, $full_id, $real_block, array $connections)
	{
		/* create dummy block */
		$b = new B_core__dummy();
		$b->cc_init($parent, $id, $full_id, $this, $real_block, null, Block::FAILED);
		$b->cc_connect($connections, $this->blocks);

		/* put block to parent's namespace */
		if ($parent) {
			$parent->cc_registerBlock($b);
		} else {
			$this->root_namespace[$id] = $b;
		}

		return true;
	}


	public function addBlocksFromIni($parent, $parsed_ini_with_sections, Context $context, & $errors = null)
	{
		$all_good = true;

		/* walk thru ini and take 'block:*' sections */
		foreach ($parsed_ini_with_sections as $section => $opts) {
			@list($keyword, $id) = explode(':', $section, 2);
			if ($keyword == 'block' && isset($id) && @($block = $opts['.block']) !== null) {
				$force_exec = @ $opts['.force_exec'];

				/* parse connections */
				foreach($opts as $in => & $out) {
					if ($in[0] == '.') {
						/* drop block options and keep only connections */
						unset($opts[$in]);
					} else if (is_array($out)) {
						if (count($out) == 1) {
							/* single connection */
							$out = explode(':', $out[0], 2);
						} else {
							/* multiple connections */
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

				$all_good &= $this->addBlock($parent, $id, $block, $force_exec, $opts, $context, $errors);
			}
		}

		return $all_good;
	}


	public function dumpNamespaces()
	{
		$str = '';
		foreach ($this->root_namespace as $name => $b) {
			$str .= $name."\n".$b->cc_dumpNamespace(1);
		}
		return $str;
	}


	public function getExecutionTimes($old_stats = null)
	{
		$cnt = 0;
		$sum = 0.0;

		if (is_array($old_stats)) {
			$by_block = $old_stats['blocks'];
		} else {
			$by_block = array();
		}

		foreach($this->blocks as $b) {
			$t = $b->cc_executionTime();
			if ($t > 0) {
				$cnt++;
				$sum += $t;
				$bm = & $by_block[$b->blockName()];
				@$bm['sum'] += $t;		// arsort() uses first field in array
				@$bm['cnt']++;
				@$bm['min'] = $bm['min'] === null ? $t : min($t, $bm['min']);
				@$bm['max'] = max($t, $bm['max']);
			}
		}

		if (is_array($old_stats)) {
			return array(
				'total_time' => $this->execution_time + $old_stats['total_time'],
				'cascade_time' => ($this->execution_time - $sum) + $old_stats['cascade_time'],
				'cascade_count' => 1 + $old_stats['cascade_count'],
				'blocks_time' => $sum + $old_stats['blocks_time'],
				'blocks_count' => $cnt + $old_stats['blocks_count'],
				'blocks' => $by_block
			);
		} else {
			return array(
				'total_time' => $this->execution_time,
				'cascade_time' => ($this->execution_time - $sum),
				'cascade_count' => 1,
				'blocks_time' => $sum,
				'blocks_count' => $cnt,
				'blocks' => $by_block
			);
		}
	}


	public function getMemoryUsage()
	{
		return $this->memory_usage;
	}


	public function exportGraphvizDot($doc_link, $whitelist = array(), $step = null)
	{
		$colors = array(
			Block::QUEUED   => '#eeeeee',	// grey
			Block::RUNNING  => '#aaccff',	// blue -- never used
			Block::ZOMBIE   => '#ccffaa',	// green
			Block::DISABLED => '#cccccc',	// dark grey
			Block::FAILED   => '#ffccaa',	// red
		);

		if ($step === null) {
			$step = $this->currentStep(false) + 1;
		}

		$gv =	 "#\n"
			."# Cascade visualization\n"
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

		list($clusters, $specs) = $this->exportGraphvizDotNamespace($this->root_namespace, $colors, $doc_link, array_flip($whitelist), $step);
		$gv .= $clusters;
		$gv .= $specs;
		$gv .= "}\n";

		return $gv;
	}

	private function exportGraphvizDotNamespace($namespace, $colors, $doc_link, $whitelist = array(), $step = null, $indent = "\t")
	{
		$missing_blocks = array();
		$gv = '';
		$clusters = '';
		$subgraph = '';

		foreach ($namespace as $id => & $block) {
			/* skip specials */
			if ($id == 'parent' || $id == 'self') {
				continue;
			}

			$id = $block->fullId();
			list($t_create, $t_start, $t_finish) = $block->getTimestamps();

			$is_created  = $t_create < $step;
			$is_started  = $t_start < $step;
			$is_running  = $t_start < $step && $t_finish >= $step;
			$is_finished = $t_finish < $step;

			/* add block header */
			$subgraph .= $indent."m_".get_ident($id).";\n";
			$gv .=	 "\tm_".get_ident($id)." [URL=\"".template_format($doc_link, array('block' => str_replace('_', '-', $block->blockName())))."\","
						."target=\"_blank\","
						.($is_created ? '':'fontcolor="#eeeeee",')
						."label=<<table border=\"1\"".($is_created ? '':' color="#eeeeee"')
								." bgcolor=\"#ffffff\" cellborder=\"0\" cellspacing=\"0\">\n"
				."	<tr>\n"
				."		<td bgcolor=\"".($is_created
									? $colors[$is_running
										? Block::RUNNING
										: ($is_finished ? $block->status() : Block::QUEUED)]
									:'#ffffff'
								)."\" colspan=\"2\">\n"
				."			<font face=\"sans bold\">".htmlspecialchars($block->id())."</font><br/>\n"
				."			<font face=\"sans italic\">".htmlspecialchars($block->blockName())."</font>\n"
				."		</td>\n"
				."	</tr>\n";


			$gv_inputs = '';
			$inputs  = $block->cc_inputs();
			$input_names = array();
			$input_functions = array();
			$output_functions = array();
			$output_names = $block->cc_outputs();

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

						if (!is_object($out_mod) && ($resolved = $block->cc_resolveBlockName($out_mod))) {
							$out_mod = $resolved;
						}

						$out_block_id = is_object($out_mod) ? $out_mod->fullId() : $out_mod;

						$missing = true;
						$zero = true;
						$big = false;

						if (!is_object($out_mod)) {
							$missing_blocks[$out_mod] = true;
							$missing = !array_key_exists($out_mod, $whitelist);
						} else if ($out_mod->cc_outputExists($out_name)) {
							$missing = false;
							if ($out_mod->cc_outputExists($out_name, false)) {
								$v = $out_mod->cc_getOutput($out_name);
								$exists = true;
								$zero = empty($v);
								$big = is_array($v) || is_object($v);
							} else {
								$exists = false;
								$zero = true;
								$big = false;
							}
						}

						$gv_inputs .= "\tm_".get_ident($out_block_id).($exists ? ':o_'.get_ident($out_name).':e' : '')
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

			/* connect forwarded outputs */
			foreach ($block->cc_forwardedOutputs() as $name => $src) {
				$n = count($src);
				if (!is_object($src[0]) && $src[0][0] == ':') {
					$function = $src[0];
					$output_functions[$name] = $function;
				} else {
					$function = null;
				}
				for ($i = $function !== null ? 1 : 0; $i < $n - 1; $i += 2) {
					$src_mod = $src[$i];
					$src_out = $src[$i + 1];
					$src_block_id = $src_mod;
					$has_output = false;
					if (is_object($src_mod) || ($src_mod = $block->cc_resolveBlockName($src_mod))) {
						$src_block_id = $src_mod->fullId();
						$has_output = $src_mod->cc_outputExists($src_out, false);
					}
					$gv_inputs .= "\tm_".get_ident($id).":o_".get_ident($name).":e -> m_".get_ident($src_block_id)
							.($has_output ? ":o_".get_ident($src_out).":e":'')
							.($is_created ? "[color=royalblue" : '[color="#eeeeee"').",arrowhead=dot,arrowtail=none,dir=both,weight=0];\n";
				}
			}
			$gv_inputs .= "\n";

			reset($input_names);
			reset($output_names);
			$in = current($input_names);
			$out = current($output_names);

			/* add block inputs and outputs */
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
							.htmlspecialchars($out)
							.(isset($output_functions[$out]) ? ' ('.htmlspecialchars($output_functions[$out]).')' : '')
							."</td>\n";
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
			$et = $block->cc_executionTime();
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

			/* recursively draw sub-namespaces */
			$child_namespace = $block->cc_getNamespace();
			if (!empty($child_namespace)) {
				list($child_sub, $child_specs) = $this->exportGraphvizDotNamespace($child_namespace, $colors, $doc_link,
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

		/* add missing blocks */
		if (!empty($missing_blocks)) {
			foreach ($missing_blocks as $block => $t) {
				if ((string) $block == '') {
					$label = '<<font face="Sans Italic">null</font>>';
				} else {
					$label = '"'.addcslashes($block, '"\\').'"';
				}
				$gv .= "\t m_".get_ident($block)." [color=".(array_key_exists($block, $whitelist) ? "dimgrey" : "red")
						.", shape=ellipse, label=$label, padding=0];\n";
			}
		}

		return array($subgraph, $gv);
	}

	public function execDot($dot_source, $out_type, $out_file = null)
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


	/**
	 * Get names of all existing blocks grouped by their prefix (plugin).
	 */
	public function getKnownBlocks($writable_only = false)
	{
		$blocks = array('' => array(), 'core' => array());

		foreach ($this->block_storages as $s) {
			if (!$writable_only || !$s->isReadOnly()) {
				$s->getKnownBlocks($blocks);
			}
		}

		foreach ($blocks as $plugin => $b) {
			sort($blocks[$plugin]);
		}

		return $blocks;
	}


	/**
	 * Returns description of block.
	 *
	 * Description contains inputs and outputs with their default values,
	 * force_exec flag and real block type.
	 */
	public function describeBlock($block)
	{
		/* create instance of the block */
		$b = $this->createBlockInstance($block);
		if (!$b) {
			return false;
		}

		/* get description */
		$desc = $b->cc_describeBlock();
		unset($b);

		return $desc;
	}

}

