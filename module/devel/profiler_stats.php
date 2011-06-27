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

class M_core__devel__profiler_stats extends Module {

	protected $inputs = array(
		'filename' => array(),
		'slot' => 'default',
		'slot-weight' => 50,
	);

	protected $outputs = array(
		'done' => true,
	);

	private $modules_stats, $pipeline_stats;
	private $stats_row = 0;
	private $pct_base = 0;

	public function main()
	{
		$data = file_get_contents($this->in('filename'));
		if ($data === FALSE) {
			return;
		}

		$stats = unserialize(gzuncompress($data));
		$this->pipeline_stats = $stats;
		$this->modules_stats = & $this->pipeline_stats['modules'];
		arsort($this->modules_stats);

		$table = new TableView();
		$table->add_column('text', array(
				'title'  => 'Module',
				'key'    => 'module',
			));
		$table->add_column('number', array(
				'title'  => 'Total time [%]',
				'key'    => 'sum_pct',
				'format' => '%1.1f',
			));
		$table->add_column('number', array(
				'title'  => 'Count',
				'key'    => 'cnt',
			));
		$table->add_column('number', array(
				'title'  => 'Total time',
				'key'    => 'sum',
				'format' => '%1.2f',
			));
		$table->add_column('number', array(
				'title'  => 'Average time',
				'key'    => 'avg',
				'format' => '%1.2f',
			));
		$table->add_column('number', array(
				'title'  => 'Min. time',
				'key'    => 'min',
				'format' => '%1.2f',
			));
		$table->add_column('number', array(
				'title'  => 'Max. time',
				'key'    => 'max',
				'format' => '%1.2f',
			));
		$table->set_data_iterator_function($this, 'next_stat');
	
		$this->template_add(null, 'core/table', $table);

		$this->out('done', true);
	}


	public function next_stat()
	{
		switch ($this->stats_row++) {
			case 0:
				$this->pct_base = $this->pipeline_stats['total_time'];
				return array(
					'module' => '∑',
					'sum' => $this->pipeline_stats['total_time'],
					'sum_pct' => 100 * $this->pipeline_stats['total_time'] / $this->pct_base,
					'cnt' => $this->pipeline_stats['pipeline_count'],
					'avg' => $this->pipeline_stats['pipeline_count']
							? $this->pipeline_stats['total_time'] / $this->pipeline_stats['pipeline_count']
							: null,
					'min' => null,
					'max' => null,
				);

			case 1:
				return array(
					'module' => 'Pipeline controller',
					'sum' => $this->pipeline_stats['pipeline_time'],
					'sum_pct' => 100 * $this->pipeline_stats['pipeline_time'] / $this->pct_base,
					'cnt' => $this->pipeline_stats['pipeline_count'],
					'avg' => $this->pipeline_stats['pipeline_count']
							? $this->pipeline_stats['pipeline_time'] / $this->pipeline_stats['pipeline_count']
							: null,
					'min' => null,
					'max' => null,
				);

			case 2:
				reset($this->modules_stats);
			default:
				if ((list($key, $row) = each($this->modules_stats))) {
					$row['module'] = $key;
					$row['avg'] = $row['cnt'] ? (float) $row['sum'] / $row['cnt'] : null;
					$row['sum_pct'] = 100 * $row['sum'] / $this->pct_base;
					return $row;
				} else {
					return null;
				}
		}
	}
}

