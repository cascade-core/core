
all: doc test


clean:
	make clean -C doc/

doc:
	make -C doc/

test:
	pear run-tests ./test


.PHONY: all clean doc test

