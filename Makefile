.DEFAULT: all
.PHONY: all check coverage-branch docs docs-check docs-serve

BRANCH_COVERAGE_OUTPUT ?= coverage/branch
BRANCH_COVERAGE_SOURCE ?= src
BRANCH_COVERAGE_TESTS ?=
BRANCH_COVERAGE_XDEBUG_ERROR := Xdebug is not loaded; enter nix develop .\#xdebug.
DOCS_HOST ?= 127.0.0.1
DOCS_PORT ?= 3000

all: check

check:
	composer check

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
	php tools/finalize-docs.php

docs-check: docs

docs-serve: docs
	@mdbook watch docs & watcher_pid=$$!; \
		cleanup() { kill "$$watcher_pid" 2>/dev/null || true; wait "$$watcher_pid" 2>/dev/null || true; }; \
		trap cleanup EXIT HUP INT TERM; \
		php -S "$(DOCS_HOST):$(DOCS_PORT)" -t build/docs tools/serve-docs.php
