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

	//debug_dump($items, $id);

	switch ($layout) {
		default:
		case 'tree':
			echo "<ul id=\"", htmlspecialchars($id), "\" class=\"menu", isset($class) ? ' '.$class : '', "\">\n";
			tpl_html5__core__menu__tree($id, $items, $title_fmt, $active_only - 1, $max_depth);
			echo "</ul>\n";
			break;

		case 'row':
			echo "<div id=\"", htmlspecialchars($id), "\" class=\"menu", isset($class) ? ' '.$class : '', "\">\n";
			tpl_html5__core__menu__row($id, $items, $title_fmt, $max_depth);
			echo "</div>\n";
			break;

		case 'columns':
			if (!isset($column_count)) {
				$column_count = 4;
			}
			echo "<table id=\"", htmlspecialchars($id), "\" class=\"menu", isset($class) ? ' '.$class : '', "\">\n";
			for ($c = 0; $c < $column_count; $c++) {
				echo "<col width=\"", floor(100. / $column_count), "%\">\n";
			}
			tpl_html5__core__menu__columns($id, $items, $title_fmt, $column_count, $active_only - 1, $max_depth);
			echo "</table>\n";
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
		$label = isset($item['label']) ? $item['label'] : (isset($item['title']) ? $item['title'] : null);
		if ($label !== null) {
			if (isset($item['link'])) {
				echo "<a href=\"", str_replace(array('@', '.'), array('&#64;', '&#46;'), htmlspecialchars($item['link'])),
						"\">", htmlspecialchars($label), "</a>";
			} else {
				echo "<span>", htmlspecialchars($label), "</span>";
			}
		}
	}
}


function tpl_html5__core__menu__tree($id, $items, $title_fmt, $active_only, $max_depth = PHP_INT_MAX)
{
	/* show menu */
	foreach ($items as $i => $item) {
		if (!empty($item['hidden'])) {
			continue;
		}

		$classes = (array) @ $item['classes'];
		$classes[] = 'mni-'.str_replace(' ', '_', $i);

		/* are there children nodes? */
		if (!empty($item['children']) && $max_depth > 0) {
			$has_children = true;
			$classes[] = 'has_children';
		} else {
			$has_children = false;
		}

		/* build open tag with class attribute */
		echo '<li class="', htmlspecialchars(join($classes, ' ')), '">';

		/* show label */
		tpl_html5__core__menu__label($id, $item, $title_fmt);

		/* recursively show children */
		if ($has_children && ($active_only <= 0 || !empty($item['has_active_child']) || !empty($item['is_active']))) {
			echo "\n<ul>\n";
			tpl_html5__core__menu__tree($id, $item['children'], $title_fmt, $active_only - 1, $max_depth - 1);
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
		$classes[] = 'mni-'.str_replace(' ', '_', $i);

		/* separator */
		if ($first) {
			$first = false;
		} else {
			echo "\n<span class=\"separator\">|</span> ";
		}

		/* build open tag with class attribute */
		echo '<span class="', htmlspecialchars(join($classes, ' ')), '">';

		/* show label */
		tpl_html5__core__menu__label($id, $item, $title_fmt);

		echo "</span>";

		/* recursively show children */
		if (isset($item['children']) && $max_depth > 0) {
			tpl_html5__core__menu__row($id, $item['children'], $title_fmt, $max_depth - 1, false);
		}

	}
}


function tpl_html5__core__menu__columns($id, $items, $title_fmt, $column_count, $active_only, $max_depth = PHP_INT_MAX)
{
	$column = 0;

	echo "<tr>\n";

	foreach ($items as $i => $item) {
		if (!empty($item['hidden'])) {
			continue;
		}

		if ($column >= $column_count) {
			echo "</tr>\n<tr>\n";
			$column = 0;
		}

		$classes = (array) @ $item['classes'];
		$classes[] = 'mni-'.str_replace(' ', '_', $i);

		/* are there children nodes? */
		if (!empty($item['children']) && $max_depth > 0) {
			$has_children = true;
			$classes[] = 'has_children';
		} else {
			$has_children = false;
		}

		/* build open tag with class attribute */
		echo '<td class="', htmlspecialchars(join($classes, ' ')), '">';

		/* show label */
		echo "<div>";
		tpl_html5__core__menu__label($id, $item, $title_fmt);
		echo "</div>\n";

		/* recursively show children */
		if ($has_children && ($active_only <= 0 || !empty($item['has_active_child']) || !empty($item['is_active']))) {
			echo "\n<ul>\n";
			tpl_html5__core__menu__tree($id, $item['children'], $title_fmt, $active_only - 1, $max_depth - 1);
			echo "</ul>\n";
		}

		echo "</td>\n";

		$column++;
	}

	while ($column < $column_count) {
		echo "<td></td>\n";
		$column++;
	}

	echo "</tr>\n";
}

