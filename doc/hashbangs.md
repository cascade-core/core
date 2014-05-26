Hashbangs
=========

In unix a hashbang is the first line of executable file starting with `#!`.
This line says which interpreter should be used to execute the file. In cascade
hashbang is the same, but there are little technical differences.

The hashbang is a top-level key `#!` in a block configuration. When
BlockStorage::createBlockInstance() returns such configuration instead of block
instance, the CascadeController looks up specified hashbang in its configuration
and uses specified class as factory of the new block.

Hashbangs in a block configuration are refered by name. Details, like which
class should handle specified hashbang, are stored in core configuration.

Hashbang handler is class implementing IHashbangHandler interface. Most
important piece of this interface is the factory method to create new block
instance.

Primary motivation for hashbangs is to eliminate unnecessary block storages
which differ only in the interpretation of the configuration.

Hashbangs are not good tool if set of blocks should be generated. However it is
possible to create block storage which generates configuration interpreted by
hashbang handlers. Hashbang handler cannot create block -- it only interprets it.


Use-cases
---------

The most important hashbang is 'proxy'. This hashbang represents default
interpreter of composed blocks.

Another user of hashbangs is DUF plugin. It stores form configuration as blocks
and uses hashbang handler to create blocks from this configuration.

More advanced use-case can be content management system, where hashbangs can be
used to interpret very minimalistic description of what should be on the page
(one page is represented by one block).

