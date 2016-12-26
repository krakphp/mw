PERIDOT = ./vendor/bin/peridot

.PHONY: test doc

test:
	$(PERIDOT) test

doc:
	cd doc; make html
