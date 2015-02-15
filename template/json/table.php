<?php
/*
 * Copyright (c) 2015, Josef Kufner  <jk@frozen-doe.net>
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

function TPL_json__core__table($t, $id, $table, $so)
{
	$keys = array();
	foreach ($table->columns() as $c => $col) {
		$keys[] = 0;
	}

	$data = array();
	$columns = $table->columns();
	while (($row_data = $table->getNextRowData())) {
		$row = array();
		// FIXME: This is not fully compatible with HTML version of this template
		foreach ($columns as $c) {
			$k = $c[1]['key'];
			$row[$k] = isset($row_data[$k]) ? $row_data[$k] : null;
		}
		$data[] = $row;
	}

	echo json_encode($data, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

