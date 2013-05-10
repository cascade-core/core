<?php
/*
 * Copyright (c) 2013, Josef Kufner  <jk@frozen-doe.net>
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

namespace JsonDatabase;

/**
 * Opened document in JsonDatabase. You can open each document only once at the 
 * time. Close documents before throwing them away.
 */
class JsonDocument
{
	protected $folder;
	protected $id;
	protected $location;
	protected $file = FALSE;
	protected $read_only = TRUE;

	protected $info = null;
	protected $data = null;


	public function __construct($folder, $id, $location)
	{
		$this->folder = $folder;
		$this->id = $id;
		$this->location = $location;
	}


	public function __destruct()
	{
		$this->close();
	}


	public function getFolder()
	{
		return $this->folder;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getLocation()
	{
		return $this->location;
	}


	public function isOpened()
	{
		return $this->file !== FALSE;
	}


	public function isReadOnly()
	{
		return $this->read_only;
	}


	/**
	 * Open document as read_only. This will lock it's file using shared lock.
	 */
	public function open($writable = FALSE)
	{
		if ($this->file !== FALSE) {
			throw new \RuntimeException('Document is already opened.');
		}

		$this->file = fopen($this->location, $writable ? 'r+' : 'r');
		if ($this->file === FALSE) {
			$err = error_get_last();
			throw new \DomainException('Cannot open document: '.$err['message']);
		}

		if (flock($this->file, $writable ? LOCK_EX : LOCK_SH) === FALSE) {
			$err = error_get_last();
			$this->close();
			throw new \Exception('Cannot lock document: '.$err['message']);
		}

		$this->read_only = ! $writable;

		$this->load();
	}


	/**
	 * Create document. This will create and lock it's file using exclusive lock.
	 */
	public function create()
	{
		if ($this->file !== FALSE) {
			throw new \RuntimeException('Document is already opened.');
		}

		$this->file = fopen($this->location, 'x+');
		if ($this->file === FALSE) {
			$err = error_get_last();
			throw new \DomainException('Cannot open document: '.$err['message']);
		}

		if (flock($this->file, LOCK_EX) === FALSE) {
			$err = error_get_last();
			$this->close();
			throw new \Exception('Cannot lock document: '.$err['message']);
		}

		$this->read_only = FALSE;

		// Initialize metadata section
		$this->data = array();
		$now = strftime('%Y-%m-%d %H:%M:%S %z');
		$this->data['info']['ctime'] = $now;
		$this->data['info']['mtime'] = $now;
		$this->write();
	}


	/**
	 * Close document's file. Write all data if changed.
	 */
	public function close()
	{
		if ($this->file !== FALSE) {
			if (!$this->read_only) {
				$this->write();
			}
			fclose($this->file);
			$this->file = FALSE;
			$this->data = null;
		}
	}


	protected function load()
	{
		if ($this->file === FALSE) {
			throw new \RuntimeException('Document is not opened.');
		}

		if (fseek($this->file, 0) === FALSE) {
			$err = error_get_last();
			throw new \Exception('Cannot write document: '.$err['message']);
		}

		$json = stream_get_contents($this->file);

		$this->data = json_decode($json, TRUE, 512, JSON_BIGINT_AS_STRING);
		unset($this->data['_']);
	}


	/**
	 * Write data to file.
	 */
	public function write()
	{
		if ($this->read_only) {
			throw new \RuntimeException('Cannot write read-only document.');
		}

		if ($this->file === FALSE) {
			throw new \RuntimeException('Document is not opened.');
		}

		if ($this->data === null) {
			throw new \RuntimeException('Document is not loaded.');
		}

		// Remove old content
		if (ftruncate($this->file, 0) === FALSE) {
			$err = error_get_last();
			throw new \Exception('Cannot write document: '.$err['message']);
		}
		if (fseek($this->file, 0) === FALSE) {
			$err = error_get_last();
			throw new \Exception('Cannot write document: '.$err['message']);
		}

		// Update mtime
		$this->data['info']['mtime'] = strftime('%Y-%m-%d %H:%M:%S %z');

		// Put stop snipped at marked position (it is here to prevent 
		// overwriting from $json_array).
		$data = array_merge(array('_' => "<?php printf('_%c%c}%c',34,10,10);__halt_compiler();?>"), $this->data);

		// Encode data to JSON
		$json = json_encode($data,
				(defined('JSON_PRETTY_PRINT')
					? JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
					: JSON_NUMERIC_CHECK));

		// Write new data
		if (fwrite($this->file, $json) === FALSE || fflush($this->file) === FALSE) {
			$err = error_get_last();
			throw new \Exception('Cannot write document: '.$err['message']);
		}
	}


	/**
	 * Enumerates all existing sections in document.
	 */
	public function getSections()
	{
		return array_keys($this->data);
	}


	/**
	 * Get copy of all sections at once.
	 */
	public function getData()
	{
		return $this->data;
	}


	public function & __get($section)
	{
		if ($section == 'info') {
			// return a copy, so it cannot be changed
			$x = $this->data['info'];
			return $x;
		}
		return $this->data[$section];
	}


	public function __set($section, $value)
	{
		if ($section == 'info') {
			throw new \InvalidArgumentException('Info section is read only.');
		}
		return $this->data[$section] = $value;
	}


	public function __isset($section)
	{
		return isset($this->data[$section]);
	}


	public function __unset($section)
	{
		if ($section == 'info') {
			throw new \InvalidArgumentException('Info section is read only.');
		}
		unset($this->data[$section]);
	}

}

