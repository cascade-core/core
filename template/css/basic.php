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


function TPL_css__core__basic($t, $id, $d, $so)
{

	echo <<<eof
.slot {
	/* clear inner floats */
	position: relative;
	overflow: hidden;
	width: 100%;

	/* no appearence */
	border: 0px;
	margin: 0px;
	padding: 0px;
}

.cascade_dump {
	clear: both;
	margin: 5em 0em 0em 0em;
	padding: 0em;
	background: #fff;
	color: #000;
	text-align: center;
}
	.cascade_dump hr {
		display: none;
	}
	.cascade_dump h2 {
		font-size: 1.3em;
		font-weight: normal;
		margin: 1ex 0em;
		padding: 1ex 0em;
		border-top: 1px solid #888;
		border-bottom: 1px solid #888;
	}
\n
eof;

}

