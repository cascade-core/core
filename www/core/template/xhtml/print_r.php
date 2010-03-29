<?

function TPL_core__print_r($t, $d)
{
	ob_start();
	print_r($d);
	$str = ob_get_clean();

	echo "<pre>".htmlspecialchars($str)."</pre>\n";
}

// vim:encoding=utf8:

