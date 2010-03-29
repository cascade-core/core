<?

class M_core__page extends Module {

	protected $outputs = array(
		'done' => true,
	);

	function main()
	{
		$this->template_add_to_slot('page', 'root',    50, 'core/main');
		$this->out('done', true);
	}

}

// vim:encoding=utf8:

