<?

class Context {

	private $locale = DEFAULT_LOCALE;
	private $template_engine = null;

	private static $last_context_enviroment = false;


	/****************************************************************************
	 *	For modules
	 */

	public function set_locale($locale)
	{
		$this->locale = $locale !== null ? preg_replace('/[^.]*$/', '', $locale).'UTF8' : null;
	}


	public function set_template_engine($template_engine)
	{
		$this->template_engine = $template_engine;
	}


	/****************************************************************************
	 *	For Pipeline controller
	 */

	/* update enviroment from context, returns true if changes required (for child classes) */
	public function update_enviroment()
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


	public function get_template_engine() {
		return $this->template_engine;
	}
}


// vim:encoding=utf8:

