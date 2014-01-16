<?php
/*
 * Copyright (c) 2012, Josef Kufner  <jk@frozen-doe.net>
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

/**
 * Show version info, some useful informations about server, PHP, and list of
 * installed extensions in nice table. It is not supposed to replace phpinfo()
 * function, it only shows the most interesting data.
 */
class B_core__devel__phpinfo extends \Cascade\Core\Block {

	protected $inputs = array(
		'slot' => 'default',
		'slot_weight' => 50,
	);

	protected $outputs = array(
		'done' => true,
	);

	const force_exec = true;


	public function main()
	{
		$extensions = get_loaded_extensions();
		usort($extensions, 'strcoll');

		if (function_exists('posix_getuid')) {
			$uid = posix_getuid();
			$user = posix_getpwuid($uid);
		}

		if (function_exists('posix_getgid')) {
			$gid = posix_getgid();
			$group = posix_getgrgid($gid);
		}

		$info = array(
			array(_('PHP version:'), phpversion()),
			array(_('Zend engine version:'), zend_version()),
			array(_('Server system:'), php_uname()),
			array(_('Web root directory:'), DIR_ROOT),
			array(_('Effective user ID:'), isset($uid) ? sprintf(_('%s (%s)'), $user['name'], $uid) : null),
			array(_('Effective group ID:'), isset($gid) ? sprintf(_('%s (%s)'), $group['name'], $gid) : null),
			array(_('Installed extensions:'), join(', ', $extensions)),
		);

		$table = new \Cascade\Core\TableView();
		$table->show_header = false;
		$table->addColumn('heading', array(
				'title' => null,
				'key' => 0,
				'nowrap' => true,
			));
		$table->addColumn('text', array(
				'title' => null,
				'key' => 1,
			));

		$table->setData($info);

		$this->templateAdd(null, 'core/table', $table);

		$this->out('done', true);
	}
}

