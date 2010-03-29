<?

function TPL_core__main($t, $d)
{
	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n"
		,"  \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
	echo "<html>\n";

	echo "<head>\n";
	echo "\t<title>".htmlspecialchars($page_title)."</title>\n"; // FIXME
	echo "\t<meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=UTF-8\" />\n";
	$t->process_slot('html-head');
	echo "</head>\n";
	
	echo "<body>\n";
	$t->process_slot('html-body');
	$t->process_slot('default');	// fallback
	echo "</body>\n";
	
	echo "</html>\n";
}

// vim:encoding=utf8:

