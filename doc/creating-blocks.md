Creating Blocks
===============

When a block is inserted into cascade, a factory method
[CascadeController::addBlock()](@ref Cascade::Core::CascadeController::addBlock)
is called to create requested block. This factory asks all registered block
storages for the block and if the block storage returns the block, search is
over. However, block storage may return only a block configuration instead of
instance of [Block] class. In this case the [CascadeController] looks for a
shebang in the block configuration and calls specified shebang handler to
create [Block] instance.

[CascadeController]: @ref Cascade::Core::CascadeController
[Block]: @ref Cascade::Core::Block
[CascadeController::addBlock()]: @ref Cascade::Core::CascadeController::addBlock


Block Storages
--------------

Block storages are objects implementing [IBlockStorage] interface. All
block storages are instantiated during initialization, and their configuration
is loaded from `block_storage` section of `core.json.php` file.

Block storage may be used to both load and store blocks. However, many block
storages are read-only. Additionaly, block storages can provide a list of all
known blocks with some elementary informations, like time of last modification.

There is no limitation on how blocks should be stored. They may not be stored
at all, but generated from some metadata instead.

All block storages must respect `block_storage_write_allowed` option (located
at the same level as `block_storage` section in `core.json.php`), which
globally disables any modifications to all blocks by default. Set this option
to true in `core.local.json.php` at your local development installation.

[IBlockStorage]: @ref Cascade::Core::IBlockStorage


Shebangs
---------

In unix a shebang is the first line of executable file starting with `#!`.
This line says which interpreter should be used to execute the file. In cascade
shebang is the same, but there are little technical differences.

The shebang is a top-level key `#!` in a block configuration. When
[IBlockStorage::createBlockInstance()] returns such configuration instead of [Block]
instance, the [CascadeController] looks up specified shebang in its configuration
and uses specified shebang handler class (which implements [IShebangHandler])
as factory of the new block.

Shebangs in a block configuration are refered by name. Details, like which
class should handle specified shebang, are stored in core configuration.
Therefore, it is easy to replace hasbang handler implementation.

Shebang handler is class implementing [IShebangHandler] interface. Most
important piece of this interface is [createFromShebang()],
the factory method to create new block instance.

Primary motivation for shebangs is to eliminate unnecessary block storages
which differ only in the interpretation of the configuration.

@note Shebangs are not good tool if set of blocks should be generated.
However, it is possible to create block storage which generates configuration
interpreted by shebang handlers. If exceptions are required, the generated
blocks can be easily modified and stored in a block storage with higher
priority (lesser weight). Shebang handler cannot create block -- it only
interprets it.

[IBlockStorage::createBlockInstance()]: @ref Cascade::Core::IBlockStorage::createBlockInstance()
[IShebangHandler]: @ref Cascade::Core::IShebangHandler
[createFromShebang()]: @ref Cascade::Core::IShebangHandler::createFromShebang()


Shebang Use-Cases
------------------

The most important shebang is 'proxy'. This shebang represents default
interpreter of composed blocks.

Another user of shebangs is DUF plugin. It stores form configuration as blocks
and uses shebang handler to create blocks from this configuration.

More advanced use-case can be content management system, where shebangs can be
used to interpret very minimalistic description of what should be on the page
(one page is represented by one block).



