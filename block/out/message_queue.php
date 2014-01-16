<?php
/*
 * Copyright (c) 2010, Josef Kufner  <jk@frozen-doe.net>
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
 * Show messages after redirect. Messages are stored in session.
 */

class B_core__out__message_queue extends \Cascade\Core\Block
{
	const force_exec = true;

	protected $inputs = array(
		'slot' => 'default',
		'slot_weight' => 20,
	);

	protected $outputs = array(
	);

	public function main()
	{
		if (is_array(@$_SESSION['message_queue'])) {
			foreach ($_SESSION['message_queue'] as $id => $msg_data) {
				$msg_data['msg_id'] = $id;
				$this->templateAdd($id, 'core/message', $msg_data);
			}
		}
	}
}

