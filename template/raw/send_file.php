<?php
/*
 * Copyright (c) 2011-2014, Josef Kufner  <jk@frozen-doe.net>
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


function TPL_raw__core__send_file($t, $id, $d, $so)
{
	extract($d);

	if (isset($content_type)) {
		header('Content-Type: '.$content_type);
	} else {
		switch(pathinfo($filename, PATHINFO_EXTENSION)) {
			case 'css' : header('Content-Type: text/css'); break;
			case 'flv' : header('Content-Type: video/flv'); break;
			case 'gif' : header('Content-Type: image/gif'); break;
			case 'html': header('Content-Type: text/html'); break;
			case 'jpeg': header('Content-Type: image/jpeg'); break;
			case 'jpg' : header('Content-Type: image/jpeg'); break;
			case 'js'  : header('Content-Type: text/javascript'); break;
			case 'mp4' : header('Content-Type: video/mp4'); break;
			case 'png' : header('Content-Type: image/png'); break;
			case 'txt' : header('Content-Type: text/plain; encoding=utf-8'); break;
			case 'webm': header('Content-Type: video/webm'); break;
			default: header('Content-Type: application/octet-stream'); break;
		}
	}

	if (!file_exists($filename)) {
		throw new \Exception('File not found: '.$filename);
	}

	$full_filename = ($filename[0] == '/' ? $filename : getcwd().'/'.$filename);
	$relative_filename = strpos($full_filename, DIR_ROOT) === 0 ? substr($full_filename, strlen(DIR_ROOT) - 1) : $full_filename;

	// Expire header
	if (!empty($expires)) {
		// FIXME: Make this better
		$mtime = filemtime($full_filename);
		header('Expires: '.gmdate('D, d M Y H:i:s', strtotime($expires)).' GMT');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', $mtime).' GMT');
		header('Cache-Control: public');
		header('Pragma: cache');
	}

	// TODO: Resumable downloads, check mtime
	header("X-Sendfile: ".$full_filename);       // Lighttpd and Apache
	header("X-Accel-Redirect: ".$relative_filename); // Nginx
}

