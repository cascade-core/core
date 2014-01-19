Generating a plugin documentation
=================================


The `core/bin/build-plugin-documentation.sh` script can be used to generate
plugin documentation using Doxygen and core's Doxyfile. The script loads
`core/doc/Doxyfile`, appends plugin-specific options and passes it to Doxygen.
If everything works, a plugin documentation should be generated automatically
with no configuration required.

To invoke `build-plugin-documentation.sh` create following `Makefile` in a
`doc` directory of your plugin (don't forget to use tabs):

~~~
doc:
	../../../core/bin/build-plugin-documentation.sh

.PHONY: doc
~~~

Then simply run `make doc` to update the documentation.

You may want to add more rules into this `Makefile` to process other files too.


Extra options for Doxygen
-------------------------

If a dash is passed as an argument to `build-plugin-documentation.sh`, its
stdin is appended to Doxygen configuration:

~~~
doc:
	echo "EXTRA_OPTION=\"Hello World\"" | ../../../core/bin/build-plugin-documentation.sh -

.PHONY: doc
~~~


