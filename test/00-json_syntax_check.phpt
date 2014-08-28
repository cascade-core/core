--TEST--
JSON syntax check
--FILE--
<?php

echo "# jq version: ";
passthru("jq --version");
echo "#\n";

function scan($dirname) {
	foreach (new \FilesystemIterator($dirname) as $f) {
		if ($f->isDir()) {
			scan($f->getPathname());
		} else if (preg_match('/\.json(\.php)?$/', $f->getBasename())) {
			json_decode(file_get_contents($f->getPathname()), TRUE);
			if (json_last_error() != JSON_ERROR_NONE) {
				echo $f->getPathname(), ": ";
				passthru("jq false ".escapeshellcmd($f->getPathname())." 2>&1");
			}
		}
	}
}

scan(dirname(dirname(dirname(__FILE__))));

?>
--EXPECTF--
# jq version: %s
#

