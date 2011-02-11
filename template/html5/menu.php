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

	switch ($layout) {
		default:
		case 'tree':
			echo "<ul id=\"", htmlspecialchars($id), "\" class=\"menu\">\n";
			tpl_html5__core__menu__tree($id, $items, $active_uri, $exact_match);
			echo "</ul>\n";
			break;

		case 'row':
			echo "<div id=\"", htmlspecialchars($id), "\" class=\"menu\">\n";
			tpl_html5__core__menu__row($id, $items, $active_uri, $exact_match);
			echo "</div>\n";
			break;
	}
}


function tpl_html5__core__menu__label($id, $item)
{
	$label = isset($item['label']) ? $item['label'] : @$item['title'];
	if (isset($item['link'])) {
		echo "<a href=\"", htmlspecialchars($item['link']), "\">", htmlspecialchars($label), "</a>";
	} else {
		echo "<span>", htmlspecialchars($label), "</span>";
	}
}


function tpl_html5__core__menu__tree($id, $items, $active_uri, $exact_match)
{
	foreach ($items as $i => $item) {

		/* is link active ? */
		if (isset($item['link'])) {
			$link = & $item['link'];

			if ($link == '/' || $exact_match) {
				$match = ($link == $active_uri);
			} else {
				$match = (strncmp($item['link'], $active_uri, strlen($item['link'])) == 0);
			}

			$class = $match ? ' class="active"' : '';
		} else {
			$class = '';
		}

		/* show non-numeric id */
		if (is_numeric($i)) {
			echo "<li$class>";
		} else {
			echo "<li id=\"", htmlspecialchars($id.'_'.$i), "\"$class>";
		}

		/* show label */
		tpl_html5__core__menu__label($id, $item);

		/* recursively show children */
		if (isset($item['children'])) {
			echo "\n<ul>\n";
			tpl_html5__core__menu__tree($id, $item['children'], $active_uri, $exact_match);
			echo "</ul>\n";
		}

		echo "</li>\n";
	}
}


function tpl_html5__core__menu__row($id, $items, $active_uri, $exact_match, $first = true)
{
	foreach ($items as $i => $item) {
		/* is link active ? */
		if (isset($item['link'])) {
			$link = & $item['link'];

			if ($link == '/' || $exact_match) {
				$match = ($link == $active_uri);
			} else {
				$match = (strncmp($item['link'], $active_uri, strlen($item['link'])) == 0);
			}

			$class = $match ? ' class="active"' : '';
		} else {
			$class = '';
		}

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
		tpl_html5__core__menu__label($id, $item);

		echo "</span>";

		/* recursively show children */
		if (isset($item['children'])) {
			tpl_html5__core__menu__tree($id, $item['children'], $active_uri, $exact_match, false);
		}

	}
}

