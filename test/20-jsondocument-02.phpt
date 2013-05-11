--TEST--
JsonDatabase: Basic usage
--SKIPIF--
<?php
$tmpdir = dirname(__FILE__).'/smalldb';
if (file_exists($tmpdir)) {
	die('Temporary dir "'.$tmpdir.'" already exists.');
}
?>
--FILE--
<?php
require dirname(__FILE__).'/../init.php';

// Prepare data
$tmpdir = dirname(__FILE__).'/smalldb';
mkdir($tmpdir);
mkdir($tmpdir.'/a');
mkdir($tmpdir.'/b');
chmod($tmpdir.'/b', 0111);
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

echo "Base location: ";
print_r($db->getBaseLocation());

echo "\n\n== Folder operations ==\n\n";

// Show few listings
echo "Base folders: ";
print_r($db->listFolders('/'));
echo "Base folders (recursive): ";
print_r($db->listFoldersRecursive('/'));
echo "Documents: ";
print_r($db->listDocuments('/'));

// List non-existent folder
try {
	echo "List missing folder: ";
	print_r($db->listFolders('/missing/'));
}
catch (\Exception $e) {
	echo "Exception: ", $e->getMessage(), "\n";
}

// List inaccessible folder
try {
	echo "List inaccessible folder: ";
	print_r($db->listFolders('/b/'));
}
catch (\Exception $e) {
	echo "Exception: ", $e->getMessage(), "\n";
}

// List non-existent folder recursively
try {
	echo "List missing folder (recursive): ";
	print_r($db->listFoldersRecursive('/missing/'));
}
catch (\Exception $e) {
	echo "Exception: ", $e->getMessage(), "\n";
}

echo "\n== Document operations ==\n\n";

// Load prepared file
echo "Original: ";
$hello = $db->openDocument('/', 'hello');
print_r($hello->getData());

// Create World
echo "World before write: ";
$world = $db->createDocument('/', 'world');
$world->foo['bar'] = 123;
print_r($world->foo);
$world->close();

// Load created World
echo "World reloaded: ";
$reloaded = $db->openDocument('/', 'world');
print_r($reloaded->foo);
$reloaded->close();

// List documents again to see created world
echo "Documents: ";
print_r($db->listDocuments('/'));

//print_r(file_get_contents($db->getDocumentLocation('/', 'world')));

// Delete World
echo "Delete world ...\n";
if ($db->documentExists('/', 'world')) {
	$db->deleteDocument('/', 'world');
} else {
	die('World is missing.');
}

// List documents again to see no world
echo "Documents: ";
print_r($db->listDocuments('/'));

?>
--CLEAN--
<?php
$tmpdir = dirname(__FILE__).'/smalldb';
array_map('unlink', glob($tmpdir.'/*.json.php'));
array_map('unlink', glob($tmpdir.'/*/*.json.php'));
array_map('unlink', glob($tmpdir.'/*/*/*.json.php'));
array_map('rmdir',  glob($tmpdir.'/*/*/'));
array_map('rmdir',  glob($tmpdir.'/*/'));
rmdir($tmpdir);
?>
--EXPECTF--
Base location: %s

== Folder operations ==

Base folders: Array
(
    [a] => /a/
    [b] => /b/
)
Base folders (recursive): Array
(
    [a] => /a/
    [a/c] => /a/c/
)
Documents: Array
(
    [0] => hello
)
List missing folder: Exception: Cannot open folder: No such directory.
List inaccessible folder: Exception: Cannot open folder: Directory permission denied.
List missing folder (recursive): Exception: Cannot open folder: No such directory.

== Document operations ==

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
Documents: Array
(
    [0] => world
    [1] => hello
)
Delete world ...
Documents: Array
(
    [0] => hello
)

