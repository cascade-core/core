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

function TPL_html5__core__table($t, $id, $table, $so)
{
	$col_renderer = array();

	echo "<table id=\"".htmlspecialchars($id)."\" class=\"table\">\n";

	/* prepare renderers */
	foreach ($table->columns() as $c => $col) {
		$cn = __FUNCTION__.'__'.$col[0];
		if (class_exists($cn)) {
			$col_renderer[] = new $cn($col[1]);
		} else {
			error_msg('%s: Unknown column type "%s"', $c, $col[0]);
		}
	}

	/* columns */
	foreach ($col_renderer as $col) {
		$col->col();
	}

	/* header */
	echo "<thead>\n<tr>\n";
	foreach ($col_renderer as $col) {
		$col->th();
	}
	echo "</tr>\n</thead>\n";

	/* data */
	echo "<tbody>\n";
	while (($row_data = $table->get_next_row_data())) {
		echo "<tr>\n";
		foreach ($col_renderer as $col) {
			$col->td($row_data);
		}
		echo "</tr>\n";
	}
	echo "</tbody>\n";

	echo "</table>\n";
}

class tpl_html5__core__table__text {
	protected $opts;
	
	function __construct($opts)
	{
		$this->opts = $opts;
	}

	function col()
	{
		echo "<col ",
			(isset($this->opts['width']) ? ' width="'.htmlspecialchars($this->opts['width']).'"' : ''),
			"/>\n";
	}

	function th()
	{
		if (isset($this->opts['title_tooltip'])) {
			$title = ' title="'.htmlspecialchars($this->opts['title_tooltip']).'"';
		} else {
			$title = '';
		}

		echo "<th align=\"left\"", $title, ">", nl2br(htmlspecialchars(@$this->opts['title'])), "</th>\n";
	}

	function td($row_data)
	{
		echo "<td ", !empty($this->opts['nowrap']) ? ' nowrap' : '', ">", $this->fmt_value($row_data), "</td>\n";
	}

	function fmt_value($row_data)
	{
		if (isset($this->opts['value'])) {
			if (is_callable($this->opts['value'])) {
				$value = $this->opts['value']($row_data);
			} else {
				$value = $this->opts['value'];
			}
		} else if (isset($this->opts['key'])) {
			if (is_array($this->opts['key'])) {
				$value = $row_data;
				foreach ($this->opts['key'] as $k) {
					if (isset($value[$k])) {
						$value = $value[$k];
					} else {
						$value = null;
						break;
					}
				}
			} else {
				$value = $row_data[$this->opts['key']];
			}
		} else {
			$value = null;
		}
		$fmt = @$this->opts['format'];

		if ($value === null) {
			return '';	// keep missing values missing
		} else if ($fmt) {
			$fmt_val = htmlspecialchars(sprintf($fmt, $value));
		} else {
			$fmt_val = htmlspecialchars($value);
		}

		if (isset($this->opts['link'])) {
			$args = array();
			foreach ($this->opts['link_arg'] as $a) {
				$args[] = $row_data[$a];
			}
			$a = '<a href="'.str_replace(array('@', '.'), array('&#64;', '&#46;'), htmlspecialchars(vsprintf($this->opts['link'], $args))).'">';
			$_a = '</a>';
		} else {
			$a = '';
			$_a = '';
		}

		return $a.$fmt_val.$_a;
	}
}

class tpl_html5__core__table__number extends tpl_html5__core__table__text {

	function th()
	{
		echo "<th align=\"right\">", htmlspecialchars($this->opts['title']), "</th>\n";
	}

	function td($row_data)
	{
		echo "<td align=\"right\">", $this->fmt_value($row_data), "</td>\n";
	}
}

class tpl_html5__core__table__checkbox extends tpl_html5__core__table__text {

	function th()
	{
		echo "<th></th>\n";
	}

	function col()
	{
		echo "<col width=\"1\">\n";
	}

	function td($row_data)
	{
		echo "<td><input type=\"checkbox\" name=\"", $this->fmt_value($row_data), "\"></td>\n";
	}

}
