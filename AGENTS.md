# Agent guidelines

Guidance for automated agents and humans working in this repository.

## Scaffold provenance and maintenance

The generic project scaffold was adapted from the user-owned `jbboehr/yumemi` and `jbboehr/yumemi-apocrypha`
repositories. Treat those projects as reference implementations for shared repository infrastructure, not as sources of
Akashi domain behavior.

When a task materially changes Composer packaging, CI, Nix, treefmt, pre-commit hooks, PHP quality configuration, mdBook
configuration or theming, legal and community documents, or agent and doctrine workflows, compare the corresponding
files with the reference checkouts under `tmp/` when they are available. Also make this comparison during broader
tooling or dependency-maintenance work. Do not perform it for unrelated feature changes.

If a reference contains a potentially useful material update, do not reconcile it automatically. Summarize the
differences, separate generic improvements from Yumemi-specific behavior, and ask the user whether to port them.
Preserve Akashi's package identity and project-specific design.

## Documentation

Documentation is part of the public API. Public mdBook sources live under `docs/pages/`, with chapter order defined by
`docs/pages/SUMMARY.md`. Build the book with `composer docs`, validate it with `composer docs:check`, and preview it
with `composer docs:serve`.

Examples must be small, technically correct, and executable where the repository supports it. Keep the README concise
and move detailed behavior, configuration, limitations, and contributor material into the documentation.

## Changelog

This project follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). Once the repository has a Git tag, record
every user-facing change under the `[Unreleased]` heading in `CHANGELOG.md`.

## Akashi Doctrine

New named declarations under `src/` and PHP command entry points under `bin/` must contain exactly one `@logion` PHPDoc
tag. Tests, fixtures, generated code, configuration, and external stubs are out of scope.

Follow [the style guide](docs/DOCTRINE-STYLE-GUIDE.md) when writing logia and
[the coding guide](docs/DOCTRINE-CODING-GUIDE.md) for placement, independence, and verification. Preserve existing
technical documentation and assigned references. References must use one of `OSD`, `RAS`, `AWC`, or `SFA`, must match
`BOOK C:V`, and must be unique in this repository.

Do not add or revise a logion on a preexisting declaration unless the user explicitly requests a doctrine pass.

The logia introduced or revised by commit `899ac420055502e3e0c12c3f9980f1863d0e13b9` are designated higher-effort logia.
Preserve their prose and assigned references during future general, repository-wide, or corpus-balancing rewrite passes.
Do not submit them for regeneration unless the user explicitly instructs that those higher-effort logia are in scope.
