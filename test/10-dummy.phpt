--TEST--
Cascade with Dummy block
--FILE--
<?php
define('DEBUG_LOGGING_ENABLED', false);

// Load Composer's class loader
require __DIR__."/../vendor/autoload.php";

// Initialize framework
list($plugin_manager, $default_context) = \Cascade\Core\Application::initialize(__DIR__);
$core_cfg = $plugin_manager->loadCoreConfig();

/* Initialize default context */
$default_context = new Cascade\Core\Context(array(
		'template_engine' => array(
			'class' => '\\Cascade\\Core\\Template',
		),
	));

$auth = null;

/* Initialize cascade controller */
$cascade = new Cascade\Core\CascadeController($auth, array(), array());

/* Initialize some block storage */
$cascade->addBlockStorage(new Cascade\Core\ClassBlockStorage(true, $default_context, 'class'), 'Cascade\Core\ClassBlockStorage');

/* Add dummy */
$cascade->addBlock(null, 'foo', 'core/dummy', true, array(), array(), $default_context);

/* Execute cascade */
$cascade->start();

echo "Namespaces:\n  ", str_replace("\n", "\n  ", $cascade->dumpNamespaces()), "\n\n";

?>
--EXPECT--
Namespaces:
  foo (core/dummy)

