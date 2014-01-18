
all: doc


clean:
	make clean -C doc/

doc:
	make -C doc/

tests:
	pear run-tests ./test  ../plugin/*/test ../app/test


.PHONY: all clean doc tests

