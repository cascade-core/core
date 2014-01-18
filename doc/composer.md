Composer configuration
======================

@note Examples in this page are available in the `core/doc/examples/` directory.


Application
-----------

The root `composer.json` must:

  - Define repository, where all this stuff is located.
  - Require the core and all plugins. It can also require any other composer package as usual.
  - Direct PSR-4 autoloader to app/class directory.
  - Change vendor directory to lib.

Everything else is as usual.

@include doc/examples/composer.app.json


Plugins
-------

Each plugin is also Composer package, so it has its own `composer.json`. The
package is of type `cascade-plugin` and it must require
`cascade/composer-plugin` package. It can require any other plugin or 3<sup>rd</sup> party
package as well.

In addition, the plugin name must be set in `extra.plugin` option. This plugin
name is the same as plugin directory.

Like the core and the application, the plugin also has PSR-4 autoloader directed to
its `class/` directory.

@include doc/examples/composer.plugin.json


