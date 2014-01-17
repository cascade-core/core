--TEST--
Cascade with Dummy block
--FILE--
<?php
define('DEBUG_LOGGING_ENABLED', false);
require dirname(__FILE__).'/../init.php';

/* initialize template engine */
$template = new Cascade\Core\Template();

/* Initialize default context */
$default_context = new Cascade\Core\Context();
$default_context->setLocale(DEFAULT_LOCALE);
$default_context->setTemplateEngine($template);

$auth = null;

/* Initialize cascade controller */
$cascade = new Cascade\Core\CascadeController($auth, array());

/* Initialize some block storage */
$cascade->addBlockStorage(new Cascade\Core\ClassBlockStorage(true, $default_context), 'Cascade\Core\ClassBlockStorage');

/* Add dummy */
$cascade->addBlock(null, 'foo', 'core/dummy', true, array(), $default_context);

/* Execute cascade */
$cascade->start();

echo "Namespaces:\n  ", str_replace("\n", "\n  ", $cascade->dumpNamespaces()), "\n\n";

?>
--EXPECT--
Namespaces:
  foo (core/dummy)

