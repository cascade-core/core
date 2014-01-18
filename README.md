Cascade Core
============


@todo Write an introduction.


Quick Start
-----------

1. Install [Graphviz](http://www.graphviz.org/) and [Composer](http://getcomposer.org/).
   (Graphviz packages are in most Linux distributions.)
2. Create `composer.json` in a root directory of your application (see
   [doc/examples/composer.app.json](doc/composer.md)).
3. Let Composer to install everything for you (including this core package).
4. Run `./core/bin/skeleton-prepare-hier.php` to create basic structure of your application.
5. Make `data` and `var` directories writable by webserver.
6. Set webserver to rewrite all requests to `index.php` (see `examples` directory).


### Requirements

  - PHP 5.3.3
  - Virtualhost with its own domain.


Documentation
-------------

Documentation is built by Doxygen. The generated documentation is located in
`doc/doxygen/html/index.html`.

To generate documentation run `make doc`. Do not use Doxyfile directly,
otherwise links get broken. Doxygen 1.8.3 or newer and Graphviz are required.


License
-------

The most of the code is published under Apache 2.0 license. See [LICENSE](doc/license.md) file for details.



Contribution guidelines
-----------------------

There is no bug tracker yet, so send me an e-mail and we will figure it out.

If you wish to send me a patch, please create a Git pull request or send a Git formatted patch via email.


