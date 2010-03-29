<?

class M_core__output extends Module {

	protected $inputs = array(
		'template' => null,
		'data' => null,
		'slot' => 'default',
		'slot-weight' => 50,
	);

	function main()
	{
		$this->template_add(null, $this->in('template'), $this->in('data'));
	}

}

// vim:encoding=utf8:

