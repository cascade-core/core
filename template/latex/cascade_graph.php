<?php
/*
 * Copyright (c) 2011, Josef Kufner  <jk@frozen-doe.net>
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

function TPL_latex__core__cascade_graph($t, $id, $d, $so)
{
	extract($d);

	// FIXME: this should not be done here

	if (!is_dir(dirname($dot_name))) {
		@mkdir(dirname($dot_name));
	}

	if (!isset($whitelist)) {
		$whitelist = array();
	}

	$dot = $cascade->exportGraphvizDot($link, $whitelist);
	$hash = md5($dot);

	$dot_file = sprintf($dot_name, $hash, 'dot');
	$movie_file = sprintf($dot_name, $hash, '%06d.dot.gz');
	$pdf_file = sprintf($dot_name, $hash, 'pdf');
	debug_msg('Cascade graph file: %s', $png_file);

	$dot_mtime = @filemtime($dot_file);
	$pdf_mtime = @filemtime($pdf_file);

	if (!$dot_mtime || !$pdf_mtime
			|| $dot_mtime > $pdf_mtime
			|| $pdf_mtime <= filemtime(__FILE__)
			|| ($animate && !file_exists(sprintf($movie_file, 0))))
	{
		// store dot file, it will be rendered later
		file_put_contents($dot_file, $dot);

		// prepare dot files for animation, but do not render them, becouse core/animate-cascade.sh will do
		if ($animate) {
			$steps = $cascade->currentStep(false) + 1;
			for ($t = 0; $t <= $steps; $t++) {
				$f = sprintf($movie_file, $t);
				file_put_contents($f, gzencode($cascade->exportGraphvizDot($link, $whitelist, $t), 2));
			}
		}
		
		// render graph
		$cascade->execDot($dot, 'pdf', $pdf_file);
	}


	switch ($style) {
		case false:
			// no link
			break;

		default:
		case 'image':
		case 'link':
			echo "\n% Cascade hash: ", $hash, "\n";
			break;

		case 'page_content':
			echo	"\n% core/cascade_graph: ", $id, "\n",
				"\\begin{center}\n",
				"\\includegraphics{", '/'.$pdf_file, "}\n",
				"\\end{center}\n";

			if ($style == 'page_content' && !empty($errors)) {
				echo "\\noindent{\\bf Errors:}\n";
				echo "\\begin{itemize}\n";
				foreach($errors as $e) {
					printf("\\item {\bf %s} ({\it %s }): %s\n",
							latex_escape($e['id']),
							latex_escape($e['block']),
							latex_escape($e['error']));
				}
				echo "\\end{itemize}\n";
				echo "% end of core/cascade_graph\n";
			}
			break;
	}
}

