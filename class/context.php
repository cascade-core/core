<?php
/*
 * Copyright (c) 2010, Josef Kufner  <jk@frozen-doe.net>
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

class Context {

	private $locale = DEFAULT_LOCALE;
	private $template_engine = null;

	private static $last_context_enviroment = false;


	public function __construct()
	{
		// Nothing to do... yet.
		//
		// Don't forget to call this from derived classes, even if this
		// is empty now.
	}


	/****************************************************************************
	 *	For blocks
	 */

	public function setLocale($locale)
	{
		$this->locale = $locale !== null ? preg_replace('/[^.]*$/', '', $locale).'UTF8' : null;
	}


	public function setTemplateEngine($template_engine)
	{
		$this->template_engine = $template_engine;
	}


	/****************************************************************************
	 *	For Cascade controller
	 */

	/* update enviroment from context, returns true if changes required (for child classes) */
	public function updateEnviroment()
	{
		/* do not update if not changed */
		if (self::$last_context_enviroment === $this) {
			return false;
		} else {
			self::$last_context_enviroment = $this;

			debug_msg('Updating enviroment: locale = "%s"', $this->locale);

			if ($this->locale !== null) {
				$this->locale = setlocale(LC_ALL, $this->locale, DEFAULT_LOCALE, 'C');
				putenv('LANG='.$this->locale);
			}
			return true;
		}
	}


	public function getTemplateEngine() {
		return $this->template_engine;
	}

}

