--TEST--
JsonDatabase: Initialization
--FILE--
<?php
require dirname(__FILE__).'/../init.php';
$db = new \JsonDatabase\JsonDatabase(DIR_CORE);
echo get_class($db), "\n";
--EXPECT--
JsonDatabase\JsonDatabase

