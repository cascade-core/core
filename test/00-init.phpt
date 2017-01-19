--TEST--
Core Initialization
--FILE--
<?php
// Load Composer's class loader
require __DIR__."/../vendor/autoload.php";

// Initialize framework
list($plugin_manager, $default_context) = \Cascade\Core\Application::initialize(__DIR__);
$core_cfg = $plugin_manager->loadCoreConfig();

echo "Hello World\n";
?>
--EXPECT--
Hello World

