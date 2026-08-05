.DEFAULT: all
.PHONY: all coverage-branch docs docs-check docs-serve

BRANCH_COVERAGE_OUTPUT ?= coverage/branch
BRANCH_COVERAGE_SOURCE ?= src
BRANCH_COVERAGE_TESTS ?=
BRANCH_COVERAGE_XDEBUG_ERROR := Xdebug is not loaded; enter nix develop .\#xdebug.

all:

coverage-branch:
	@php -r 'if (!extension_loaded("xdebug")) { fwrite(STDERR, "$(BRANCH_COVERAGE_XDEBUG_ERROR)\n"); exit(1); }'
	@mkdir -p "$(BRANCH_COVERAGE_OUTPUT)"
	php -d xdebug.mode=coverage vendor/bin/phpunit \
		--configuration phpunit.branch.xml.dist \
		--path-coverage \
		--coverage-filter "$(BRANCH_COVERAGE_SOURCE)" \
		--coverage-html "$(BRANCH_COVERAGE_OUTPUT)/html" \
		--coverage-text="$(BRANCH_COVERAGE_OUTPUT)/coverage.txt" \
		$(BRANCH_COVERAGE_TESTS)

docs:
	mdbook build docs

docs-check: docs

docs-serve:
	mdbook serve docs --hostname 127.0.0.1
