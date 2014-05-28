Creating Blocks
===============

When a block is inserted into cascade, a factory method
[CascadeController::addBlock()](@ref Cascade::Core::CascadeController::addBlock)
is called to create requested block. This factory asks all registered block
storages for the block and if the block storage returns the block, search is
over. However, block storage may return only a block configuration instead of
instance of [Block] class. In this case the [CascadeController] looks for a
hashbang in the block configuration and calls specified hashbang handler to
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

[IBlockStorage]: @ref Cascade::Core::IBlockStorage


Hashbangs
---------

In unix a hashbang is the first line of executable file starting with `#!`.
This line says which interpreter should be used to execute the file. In cascade
hashbang is the same, but there are little technical differences.

The hashbang is a top-level key `#!` in a block configuration. When
[IBlockStorage::createBlockInstance()] returns such configuration instead of [Block]
instance, the [CascadeController] looks up specified hashbang in its configuration
and uses specified hashbang handler class (which implements [IHashbangHandler])
as factory of the new block.

Hashbangs in a block configuration are refered by name. Details, like which
class should handle specified hashbang, are stored in core configuration.
Therefore, it is easy to replace hasbang handler implementation.

Hashbang handler is class implementing [IHashbangHandler] interface. Most
important piece of this interface is [createFromHashbang()],
the factory method to create new block instance.

Primary motivation for hashbangs is to eliminate unnecessary block storages
which differ only in the interpretation of the configuration.

@note Hashbangs are not good tool if set of blocks should be generated.
However, it is possible to create block storage which generates configuration
interpreted by hashbang handlers. If exceptions are required, the generated
blocks can be easily modified and stored in a block storage with higher
priority (lesser weight). Hashbang handler cannot create block -- it only
interprets it.

[IBlockStorage::createBlockInstance()]: @ref Cascade::Core::IBlockStorage::createBlockInstance()
[IHashbangHandler]: @ref Cascade::Core::IHashbangHandler
[createFromHashbang()]: @ref Cascade::Core::IHashbangHandler::createFromHashbang()


Hashbang Use-Cases
------------------

The most important hashbang is 'proxy'. This hashbang represents default
interpreter of composed blocks.

Another user of hashbangs is DUF plugin. It stores form configuration as blocks
and uses hashbang handler to create blocks from this configuration.

More advanced use-case can be content management system, where hashbangs can be
used to interpret very minimalistic description of what should be on the page
(one page is represented by one block).



