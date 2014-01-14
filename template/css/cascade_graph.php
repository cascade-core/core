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

function TPL_css__core__cascade_graph($t, $id, $d, $so)
{
	extract($d);

	// FIXME: this should not be done here

	if (!is_dir(dirname($dot_name))) {
		@mkdir(dirname($dot_name));
	}

	$dot = $cascade->exportGraphvizDot();
	$hash = md5($dot);

	$dot_file = sprintf($dot_name, $hash, 'dot');
	$png_file = sprintf($dot_name, $hash, 'png');

	$dot_mtime = @filemtime($dot_file);
	$png_mtime = @filemtime($png_file);

	if (!$dot_mtime || !$png_mtime || $dot_mtime > $png_mtime || $png_mtime <= filemtime(__FILE__)) {
		file_put_contents($dot_file, $dot);
		$cascade->execDot($dot, 'png', $png_file);
	}

	echo "/*\n",
		" * Cascade visualization:\n",
		" *\n",
		" * PNG: /", $png_file, "\n",
		" * Dot: /", $dot_file, "\n",
		" *\n",
		" */\n\n";
}

