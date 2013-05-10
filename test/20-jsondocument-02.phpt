--TEST--
JsonDatabase: Basic usage
--SKIPIF--
<?php
$tmpdir = dirname(__FILE__).'/smalldb';
if (file_exists($tmpdir)) {
	die('Temporary dir "'.$tmpdir.'" already exists.');
}
--FILE--
<?php
require dirname(__FILE__).'/../init.php';

// Prepare data
$tmpdir = dirname(__FILE__).'/smalldb';
mkdir($tmpdir);
mkdir($tmpdir.'/a');
mkdir($tmpdir.'/b');
mkdir($tmpdir.'/a/c');
file_put_contents($tmpdir.'/a/c/a.json.php', '{
        "_": "<?php printf(\'_%c%c}%c\',34,10,10);__halt_compiler();?>",
        "a": {
		"a": 1
	}
}');
file_put_contents($tmpdir.'/hello.json.php', '{
        "_": "<?php printf(\'_%c%c}%c\',34,10,10);__halt_compiler();?>",
        "foo": {
		"bar": 123
	}
}');

$db = new \JsonDatabase\JsonDatabase($tmpdir);

debug_dump($db->getBaseLocation(), 'Base location');

// Show few listings
debug_dump($db->listFolders('/'), 'Base folders');
debug_dump($db->listFoldersRecursive('/'), 'Base folders (recursive)');
debug_dump($db->listDocuments('/'), 'Documents');

// Load prepared file
$hello = $db->openDocument('/', 'hello');
debug_dump($hello->getData(), 'Original', true);

// Create World
$world = $db->createDocument('/', 'world');
$world->foo['bar'] = 123;
debug_dump($world->foo, 'World before write', true);
$world->close();

// Load created World
$reloaded = $db->openDocument('/', 'world');
debug_dump($reloaded->foo, 'World reloaded', true);
$reloaded->close();

// List documents again to see created world
debug_dump($db->listDocuments('/'), 'Documents');

//debug_dump((file_get_contents($db->getDocumentLocation('/', 'world'))), 'file');

// Delete World
if ($db->documentExists('/', 'world')) {
	$db->deleteDocument('/', 'world');
} else {
	die('World is missing.');
}

// List documents again to see no world
debug_dump($db->listDocuments('/'), 'Documents');

--CLEAN--
<?php
$tmpdir = dirname(__FILE__).'/smalldb';
array_map('unlink', glob($tmpdir.'/*.json.php'));
array_map('unlink', glob($tmpdir.'/*/*.json.php'));
array_map('unlink', glob($tmpdir.'/*/*/*.json.php'));
array_map('rmdir',  glob($tmpdir.'/*/*/'));
array_map('rmdir',  glob($tmpdir.'/*/'));
rmdir($tmpdir);

--EXPECTF--
Base location: '%s'
Base folders: array (
  'a' => '/a/',
  'b' => '/b/',
)
Base folders (recursive): array (
  'a' => '/a/',
  'a/c' => '/a/c/',
  'b' => '/b/',
)
Documents: array (
  0 => 'hello',
)
Original: Array
(
    [foo] => Array
        (
            [bar] => 123
        )

)

World before write: Array
(
    [bar] => 123
)

World reloaded: Array
(
    [bar] => 123
)

Documents: array (
  0 => 'world',
  1 => 'hello',
)
Documents: array (
  0 => 'hello',
)
