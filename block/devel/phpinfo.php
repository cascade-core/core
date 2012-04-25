<?php
/*
 * Copyright (c) 2012, Josef Kufner  <jk@frozen-doe.net>
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

/**
 * Show version info, some useful informations about server, PHP, and list of
 * installed extensions in nice table. It is not supposed to replace phpinfo()
 * function, it only shows the most interesting data.
 */
class B_core__devel__phpinfo extends Block {

	protected $inputs = array(
		'slot' => 'default',
		'slot_weight' => 50,
	);

	protected $outputs = array(
		'done' => true,
	);


	public function main()
	{
		$extensions = get_loaded_extensions();
		usort($extensions, 'strcoll');

		$uid = posix_getuid();
		$user = posix_getpwuid($uid);

		$gid = posix_getgid();
		$group = posix_getgrgid($gid);

		$info = array(
			array(_('PHP version:'), phpversion()),
			array(_('Zend engine version:'), zend_version()),
			array(_('Server system:'), php_uname()),
			array(_('Web root directory:'), DIR_ROOT),
			array(_('Effective user ID:'), sprintf(_('%s (%s)'), $user['name'], $uid)),
			array(_('Effective group ID:'), sprintf(_('%s (%s)'), $group['name'], $gid)),
			array(_('Installed extensions:'), join(', ', $extensions)),
		);

		$table = new TableView();
		$table->show_header = false;
		$table->add_column('heading', array(
				'title' => null,
				'key' => 0,
				'nowrap' => true,
			));
		$table->add_column('text', array(
				'title' => null,
				'key' => 1,
			));

		$table->set_data($info);

		$this->template_add(null, 'core/table', $table);

		$this->out('done', true);
	}
}

