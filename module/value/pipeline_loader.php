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

class M_core__value__pipeline_loader extends Module
{
	const force_exec = true;

	protected $inputs = array(
		'*' => null,
		'output-forward' => 'done',
	);

	protected $outputs = array(
		'*' => true,
	);


	public function main()
	{
		$output_forward = $this->in('output-forward');
		if (!is_array($output_forward)) {
			$output_forward = preg_split('/[^a-zA-Z0-9_-]+/', $output_forward, -1, PREG_SPLIT_NO_EMPTY);
		}

		foreach ((array) $this->input_names() as $i) {
			if ($i == 'output-forward') {
				continue;
			}

			$mod = $this->in($i);
			foreach ((array) $mod as $m => $mod2) {
				$id = preg_replace('/[^a-zA-Z0-9_]+/', '_', $i.'_'.$m);
				$this->pipeline_add($id, $mod2, true, array(
						'enable' => array('parent', 'done'),
					));
				foreach ($output_forward as $out) {
					$this->out_forward($id.'_'.$out, $id, $out);
				}
			}
		}
		$this->out('done', true);
	}
}



