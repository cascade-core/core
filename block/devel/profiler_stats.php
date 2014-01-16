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
 * Show data collected by integrated profiler in nice table.
 */
class B_core__devel__profiler_stats extends \Cascade\Core\Block
{
	const force_exec = true;

	protected $inputs = array(
		'filename' => DEBUG_PROFILER_STATS_FILE,	// Location of profiler's statistics.
		'slot' => 'default',
		'slot_weight' => 50,
	);

	protected $outputs = array(
		'done' => true,
	);

	private $blocks_stats, $cascade_stats;
	private $stats_row = 0;
	private $pct_base = 0;

	public function main()
	{
		$data = file_get_contents(filename_format($this->in('filename')));
		if ($data === FALSE) {
			return;
		}

		$stats = unserialize(gzuncompress($data));
		$this->cascade_stats = $stats;
		$this->blocks_stats = & $this->cascade_stats['blocks'];
		arsort($this->blocks_stats);

		$table = new \Cascade\Core\TableView();
		$table->addColumn('text', array(
				'title'  => 'Block',
				'key'    => 'block',
			));
		$table->addColumn('percentage', array(
				'title'  => 'Total time [%]',
				'key'    => 'sum_pct',
				'format' => '%1.1f',
			));
		$table->addColumn('number', array(
				'title'  => 'Count',
				'key'    => 'cnt',
			));
		$table->addColumn('number', array(
				'title'  => 'Total time',
				'key'    => 'sum',
				'format' => '%1.2f',
			));
		$table->addColumn('number', array(
				'title'  => 'Average time',
				'key'    => 'avg',
				'format' => '%1.2f',
			));
		$table->addColumn('number', array(
				'title'  => 'Min. time',
				'key'    => 'min',
				'format' => '%1.2f',
			));
		$table->addColumn('number', array(
				'title'  => 'Max. time',
				'key'    => 'max',
				'format' => '%1.2f',
			));
		$table->setDataIteratorFunction($this, 'nextStat');
	
		$this->templateAdd(null, 'core/table', $table);

		$this->out('done', true);
	}


	public function nextStat()
	{
		switch ($this->stats_row++) {
			case 0:
				$this->pct_base = $this->cascade_stats['total_time'];
				return array(
					'block' => '∑',
					'sum' => $this->cascade_stats['total_time'],
					'sum_pct' => 100 * $this->cascade_stats['total_time'] / $this->pct_base,
					'cnt' => $this->cascade_stats['cascade_count'],
					'avg' => $this->cascade_stats['cascade_count']
							? $this->cascade_stats['total_time'] / $this->cascade_stats['cascade_count']
							: null,
					'min' => null,
					'max' => null,
				);

			case 1:
				return array(
					'block' => 'Cascade controller',
					'sum' => $this->cascade_stats['cascade_time'],
					'sum_pct' => 100 * $this->cascade_stats['cascade_time'] / $this->pct_base,
					'cnt' => $this->cascade_stats['cascade_count'],
					'avg' => $this->cascade_stats['cascade_count']
							? $this->cascade_stats['cascade_time'] / $this->cascade_stats['cascade_count']
							: null,
					'min' => null,
					'max' => null,
				);

			case 2:
				reset($this->blocks_stats);
			default:
				if ((list($key, $row) = each($this->blocks_stats))) {
					$row['block'] = $key;
					$row['avg'] = $row['cnt'] ? (float) $row['sum'] / $row['cnt'] : null;
					$row['sum_pct'] = 100 * $row['sum'] / $this->pct_base;
					return $row;
				} else {
					return null;
				}
		}
	}
}

