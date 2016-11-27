PERIDOT = ./vendor/bin/peridot

.PHONY: test docs

test:
	$(PERIDOT) test

docs:
	cd docs; make html
