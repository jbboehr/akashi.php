# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Discover PHP examples in Markdown files and directories as a reusable, source-aware example corpus.
- Execute documentation examples through PHPUnit, using guarded in-process execution by default and opt-in child
  processes for examples that require process isolation.
- Rewrite supported native `assert()` calls into unconditional PHPUnit assertions while preserving maintained Markdown
  locations in failure reports.
- Verify selected documentation examples through PHPStan with deterministic matching for authored `//!` diagnostics.
- Mark runtime examples as skipped, select separate-process execution, and declare expected in-process exceptions with
  explicit authoring directives.
- Extract stable, explicitly named PHP examples through the `vendor/bin/akashi extract` command for reuse in consumer
  fixtures.
- Support PHP 8.2 and later, PHPUnit 10.5 and 11.5, and optional PHPStan 2.x integration.

[Unreleased]: https://github.com/jbboehr/akashi.php/commits/master
