all: doc

.PHONY: all clean doc figures

doc: figures
	( cat ./Doxyfile \
		&& echo "PROJECT_NAME=\"`head -n 1 ../README.md`\"" \
		&& echo "PROJECT_BRIEF=" \
		&& echo "PROJECT_NUMBER=\"`git describe --tag --match "v[0-9]*"`\"" \
		&& echo "INPUT += init.php" \
		&& echo "INPUT += utils.php" \
		) | ( cd .. && doxygen - )

figures:
	make -C figures all


clean:
	rm -rf doxygen/
	make -C figures clean

