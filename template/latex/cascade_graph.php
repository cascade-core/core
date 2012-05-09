<?php
/*
 * Copyright (c) 2011, Josef Kufner  <jk@frozen-doe.net>
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

	$dot = $cascade->export_graphviz_dot($link, $whitelist);
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
			$steps = $cascade->current_step(false) + 1;
			for ($t = 0; $t <= $steps; $t++) {
				$f = sprintf($movie_file, $t);
				file_put_contents($f, gzencode($cascade->export_graphviz_dot($link, $whitelist, $t), 2));
			}
		}
		
		// render graph
		$cascade->exec_dot($dot, 'pdf', $pdf_file);
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

