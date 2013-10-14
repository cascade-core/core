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

function TPL_html5__core__menu($t, $id, $d, $so)
{
	extract($d);

	// if $title_fmt does not start with '{a}' and end with '{/a}', wrap it with span
	if (isset($title_fmt)) {
		if (strncmp($title_fmt, '{a}', 3) != 0 || substr($title_fmt, -4) != '{/a}') {
			$title_fmt = '<span>'.$title_fmt.'</span>';
		}
	} else {
		$title_fmt = null;
	}

	if (!isset($max_depth)) {
		$max_depth = PHP_INT_MAX;
	}

	switch ($layout) {
		default:
		case 'tree':
			echo "<ul id=\"", htmlspecialchars($id), "\" class=\"menu", isset($class) ? ' '.$class : '', "\">\n";
			tpl_html5__core__menu__tree($id, $items, $title_fmt, $max_depth);
			echo "</ul>\n";
			break;

		case 'row':
			echo "<div id=\"", htmlspecialchars($id), "\" class=\"menu", isset($class) ? ' '.$class : '', "\">\n";
			tpl_html5__core__menu__row($id, $items, $title_fmt, $max_depth);
			echo "</div>\n";
			break;
	}
}


function tpl_html5__core__menu__label($id, $item, $title_fmt)
{
	if (isset($title_fmt)) {
		if (isset($item['link'])) {
			$open =  "<a href=\"".str_replace(array('@', '.'), array('&#64;', '&#46;'), htmlspecialchars($item['link']))."\">";
			$close = "</a>";
		} else {
			$open =  "<span>";
			$close = "</span>";
		}
		$arg = $item;
		$arg['a'] = $open;
		$arg['/a'] = $close;
		echo template_format($title_fmt, $item, 'htmlspecialchars', $arg);
	} else {
		$label = isset($item['label']) ? $item['label'] : @$item['title'];
		if (isset($item['link'])) {
			echo "<a href=\"", str_replace(array('@', '.'), array('&#64;', '&#46;'), htmlspecialchars($item['link'])),
					"\">", htmlspecialchars($label), "</a>";
		} else {
			echo "<span>", htmlspecialchars($label), "</span>";
		}
	}
}


function tpl_html5__core__menu__tree($id, $items, $title_fmt, $max_depth = PHP_INT_MAX)
{
	/* show menu */
	foreach ($items as $i => $item) {
		if (!empty($item['hidden'])) {
			continue;
		}

		$classes = (array) @ $item['classes'];

		/* are there children nodes? */
		if (!empty($item['children']) && $max_depth > 0) {
			$has_children = true;
			$classes[] = 'has_children';
		} else {
			$has_children = false;
		}

		/* build class attribute */
		$class = empty($classes) ? '' : ' class="'.join($classes, ' ').'"';

		/* show non-numeric id */
		if (is_numeric($i)) {
			echo "<li$class>";
		} else {
			echo "<li id=\"", htmlspecialchars($id.'_'.$i), "\"$class>";
		}

		/* show label */
		tpl_html5__core__menu__label($id, $item, $title_fmt);

		/* recursively show children */
		if ($has_children) {
			echo "\n<ul>\n";
			tpl_html5__core__menu__tree($id, $item['children'], $title_fmt, $max_depth - 1);
			echo "</ul>\n";
		}

		echo "</li>\n";
	}
}


function tpl_html5__core__menu__row($id, $items, $title_fmt, $max_depth = PHP_INT_MAX, $first = true)
{

	foreach ($items as $i => $item) {
		if (!empty($item['hidden'])) {
			continue;
		}

		$classes = (array) @ $item['classes'];

		/* build class attribute */
		$class = empty($classes) ? '' : ' class="'.join($classes, ' ').'"';

		/* separator */
		if ($first) {
			$first = false;
		} else {
			echo "\n<span class=\"separator\">|</span> ";
		}

		/* show non-numeric id */
		if (is_numeric($i)) {
			echo "<span$class>";
		} else {
			echo "<span id=\"", htmlspecialchars($id.'_'.$i), "\"$class>";
		}

		/* show label */
		tpl_html5__core__menu__label($id, $item, $title_fmt);

		echo "</span>";

		/* recursively show children */
		if (isset($item['children']) && $max_depth > 0) {
			tpl_html5__core__menu__row($id, $item['children'], $title_fmt, $max_depth - 1, false);
		}

	}
}

