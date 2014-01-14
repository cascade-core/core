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

/*
 * Yes, you can freely skip this file if you set your web server
 * directly to core/index.php.
 */


/* Define directory and file names.
 * Each DIR_* must be slash-terminated!
 * These constanst will be defined (but not overwritten) in core/index.php.
 */
//define('DIR_ROOT',		dirname(dirname(__FILE__)).'/');
//define('DIR_CORE',		DIR_ROOT.'core/');
//define('DIR_APP',		DIR_ROOT.'app/');
//define('DIR_PLUGIN',		DIR_ROOT.'plugin/');

/* bootstrap core */
require(defined('DIR_CORE') ? DIR_CORE.'index.php' : './core/index.php');

