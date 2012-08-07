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

	echo "<table id=\"".htmlspecialchars($id)."\" class=\"table", (($c = $table->get_table_class()) ? ' '.str_replace('/', '__', $c) : ''), "\">\n";

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
	$column_count = 0;
	foreach ($col_renderer as $col) {
		$col->col();
		$column_count += $col->column_span();
	}

	/* header */
	if ($table->show_header) {
		echo "<thead>\n<tr>\n";
		foreach ($col_renderer as $col) {
			$col->th();
		}
		echo "</tr>\n</thead>\n";
	}

	/* footer */
	if ($table->show_footer) {
		echo "<tfoot>\n<tr>\n";
		foreach ($col_renderer as $col) {
			$col->th();
		}
		echo "</tr>\n";

		$actions = $table->get_actions();
		if (!empty($actions)) {
			echo "<tr>\n<td class=\"action_bar\" colspan=\"", $column_count, "\">";
			$first = true;
			foreach ($actions as $action => $opts) {
				if ($first) {
					$first = false;
				} else {
					echo " | ";
				}
				echo "<a class=\"", htmlspecialchars($action), "\" data-action=\"", htmlspecialchars($action), "\" ",
						"href=\"", isset($opts['href']) ? htmlspecialchars($opts['href']) : '#', "\">",
						htmlspecialchars($opts['label']), "</a>";
			}
			echo "</td>\n</tr>\n";
		}
		echo "</tfoot>\n";
	}

	/* data */
	echo "<tbody>\n";
	while (($row_data = $table->get_next_row_data())) {
		$row_attr = '';
		$fn = $table->row_data;
		if (is_callable($fn)) {
			foreach($fn($row_data) as $k => $v) {
				$row_attr .= ' data-'.$k.'="'.htmlspecialchars($v).'"';
			}
		}

		$row_class = $table->get_row_class();
		if (is_callable($row_class)) {
			$row_attr .= ' class="'.htmlspecialchars($row_class($row_data)).'"';
		} else if ($row_class != '') {
			$row_attr .= ' class="'.htmlspecialchars($row_class).'"';
		}

		echo "<tr", $row_attr, ">\n";
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
	protected $th_attr = '';
	protected $td_attr = '';
	protected $td_tag = 'td';
	
	function __construct($opts)
	{
		$this->opts = $opts;

		if (!empty($this->opts['class'])) {
			$this->th_attr .= ' class="'.htmlspecialchars($this->opts['class']).'"';
			$this->td_attr .= ' class="'.htmlspecialchars($this->opts['class']).'"';
		}
		if (!empty($this->opts['nowrap'])) {
			$this->td_attr .= ' nowrap';
		}
	}


	function column_span()
	{
		return 1;
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

		$a1 = '';
		$a2 = '';

		if (isset($this->opts['title_link'])) {
			if (is_callable($this->opts['title_link'])) {
				$href = $this->opts['title_link']($row_data);
			} else {
				if (isset($this->opts['title_link_arg'])) {
					$args = array();
					foreach ($this->opts['title_link_arg'] as $a) {
						$args[] = $row_data[$a];
					} 
					$href = vsprintf($this->opts['title_link'], $args);
				} else {
					$href = $this->opts['title_link'];
				}
			}

			if ($href != '') {
				$a1 = '<a href="'.str_replace(array('@', '.'), array('&#64;', '&#46;'), htmlspecialchars($href)).'">';
				$a2 = '</a>';
			}
		}

		echo "<th", $this->th_attr, " align=\"left\"", $title, ">", $a1, nl2br(htmlspecialchars(@$this->opts['title'])), $a2, "</th>\n";
	}

	function td($row_data)
	{
		$title = null;
		if (isset($this->opts['tooltip'])) {
			if (is_callable($this->opts['tooltip'])) {
				$title = $this->opts['tooltip']($row_data);
			} else {
				$title = $this->opts['tooltip'];
			}
		}

		if (isset($this->opts['data'])) {
			$data = '';
			if (is_callable($this->opts['data'])) {
				$data_array = $this->opts['data']($row_data);
			} else {
				$data_array = $this->opts['data'];
			}
			foreach ($data_array as $k => $v) {
				$data .= ' data-'.htmlspecialchars($k).'="'.htmlspecialchars($v).'"';
			}
		} else {
			$data = '';
		}

		echo "<", $this->td_tag, $this->td_attr, $title != '' ? ' title="'.htmlspecialchars($title).'"' : '', $data, ">",
			$this->fmt_value($this->raw_value($row_data), $row_data), "</", $this->td_tag, ">\n";
	}

	function raw_value($row_data)
	{
		if (isset($this->opts['value'])) {
			if (is_callable($this->opts['value'])) {
				return $this->opts['value']($row_data);
			} else {
				return $this->opts['value'];
			}
		} else if (isset($this->opts['key'])) {
			if (is_array($this->opts['key'])) {
				return $row_data;
				foreach ($this->opts['key'] as $k) {
					if (isset($value[$k])) {
						return $value[$k];
					} else {
						return null;
					}
				}
			} else {
				return $row_data[$this->opts['key']];
			}
		} else {
			return null;
		}
	}

	function fmt_value($value, $row_data)
	{
		$fmt = @$this->opts['format'];

		if ($value === null) {
			$fmt_val = null;	// keep missing values missing
		} else if ($fmt) {
			if ($fmt == 'raw_html') {
				$fmt_val = $value;
			} else if (is_callable($fmt)) {
				$fmt_val = htmlspecialchars($fmt($value));
			} else {
				$fmt_val = htmlspecialchars(sprintf($fmt, $value));
			}
		} else {
			$fmt_val = htmlspecialchars($value);
		}

		if (isset($this->opts['value_class']) && $fmt_val !== null) {
			if (is_callable($this->opts['value_class'])) {
				$c = $this->opts['value_class']($row_data);
			} else {
				$c = $this->opts['value_class'];
			}
			$fmt_val = '<span class="'.htmlspecialchars($c).'">'.$fmt_val.'</span>';
		}

		if (isset($this->opts['link'])) {
			if (is_callable($this->opts['link'])) {
				$href = $this->opts['link']($row_data);
			} else {
				if (isset($this->opts['link_arg'])) {
					$args = array();
					foreach ($this->opts['link_arg'] as $a) {
						$args[] = $row_data[$a];
					} 
					$href = vsprintf($this->opts['link'], $args);
				} else {
					$href = $this->opts['link'];
				}
			}

			if ($href != '') {
				if ($fmt_val === null) {
					if (empty($this->opts['link_empty'])) {
						return '';
					} else {
						return '<a href="'.str_replace(array('@', '.'), array('&#64;', '&#46;'), htmlspecialchars($href)).'"'
							.' class="empty">'
							.'&nbsp;'
							.'</a>';
					}
				} else {
					return '<a href="'.str_replace(array('@', '.'), array('&#64;', '&#46;'), htmlspecialchars($href)).'">'
						.$fmt_val
						.'</a>';
				}
			}
		}

		return $fmt_val;
	}
}

class tpl_html5__core__table__heading extends tpl_html5__core__table__text {
	protected $td_tag = 'th';

	function __construct($opts)
	{
		parent::__construct($opts);
	}
}

class tpl_html5__core__table__number extends tpl_html5__core__table__text {

	function __construct($opts)
	{
		parent::__construct($opts);
		$this->td_attr .= ' align="right"';
		$this->th_attr .= ' align="right"';
	}
}

class tpl_html5__core__table__percentage extends tpl_html5__core__table__number {

	function __construct($opts)
	{
		parent::__construct($opts);
	}

	function fmt_value($value, $row_data)
	{
		if ($value == null) {
			return parent::fmt_value($value, $row_data);
		} else {
			return '<span class="value">' . parent::fmt_value($value, $row_data) . '</span>'
				.'<span class="percent_bar" style="width:'.ceil($value).'%"></span>';
		}
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
		echo "<td", $this->td_attr, "><input type=\"checkbox\" value=\"", $this->fmt_value($this->raw_value($row_data), $row_data), "\" tabindex=\"1\"></td>\n";
	}

}
