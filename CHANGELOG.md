# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-08-10

### Added

- Discover fenced PHP examples from configured Markdown files and directories as a reusable corpus with maintained
  source locations.
- Execute each example as a named PHPUnit test through guarded in-process execution or opt-in child-process isolation.
- Rewrite supported native `assert()` calls into unconditional PHPUnit assertions and report parse, execution, and
  assertion failures against their originating documentation.
- Author runtime skips, separate-process selection, and expected in-process exception types with explicit directives.
- Verify selected examples through PHPStan `RuleTestCase` integration with deterministic matching for authored `//!`
  diagnostics.
- Extract stable, explicitly named examples for consumer fixtures through the configurable `vendor/bin/akashi extract`
  command.
- Configure project roots, bootstrap files, and default execution modes through immutable runtime configuration.
- Support PHP 8.2 and later, PHPUnit 10.5 and 11.5, optional PHPStan 2.x integration, and verified ParaTest scheduling.

[Unreleased]: https://github.com/jbboehr/akashi.php/compare/v0.1.0...master
[0.1.0]: https://github.com/jbboehr/akashi.php/releases/tag/v0.1.0
