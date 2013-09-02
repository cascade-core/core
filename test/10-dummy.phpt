--TEST--
Cascade with Dummy block
--FILE--
<?php
define('DEBUG_LOGGING_ENABLED', false);
require dirname(__FILE__).'/../init.php';

/* initialize template engine */
$template = new Template();

/* Initialize default context */
$default_context = new Context();
$default_context->setLocale(DEFAULT_LOCALE);
$default_context->setTemplateEngine($template);

$auth = null;

/* Initialize cascade controller */
$cascade = new CascadeController($auth, array());

/* Initialize some block storage */
$cascade->addBlockStorage(new ClassBlockStorage(true, $default_context), 'ClassBlockStorage');

/* Add dummy */
$cascade->addBlock(null, 'foo', 'core/dummy', true, array(), $default_context);

/* Execute cascade */
$cascade->start();

echo "Namespaces:\n  ", str_replace("\n", "\n  ", $cascade->dumpNamespaces()), "\n\n";

?>
--EXPECT--
Namespaces:
  foo (core/dummy)

