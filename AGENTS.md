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

## Temporary and reference checkouts

Never commit source code, tests, or scripts that references `tmp/`, `/work/tmp`, or any other workspace-local temporary
or reference-checkout path. Committed configuration must not use such a checkout as a build, test, or runtime input.
Committed tests must not conditionally skip or change behavior according to whether such a checkout exists. Ignore and
archive-exclusion rules may defensively prevent temporary directories from entering commits or packages.

Temporary reference checkouts may be inspected when another repository rule explicitly permits it, but any compatibility
evidence needed by committed code must be represented by repository-owned fixtures, public package boundaries, or other
self-contained inputs. Record fixture provenance and licensing where applicable. Existing violations must be removed
when discovered and must not be treated as precedent.

This restriction does not prohibit runtime-created temporary directories obtained through platform APIs, provided they
do not encode or depend on a workspace-local reference checkout.

## Documentation

Documentation is part of the public API. Public mdBook sources live under `docs/pages/`, with chapter order defined by
`docs/pages/SUMMARY.md`. Build the book with `composer docs`, validate it with `composer docs:check`, and preview it
with `composer docs:serve`.

Examples must be small, technically correct, and executable where the repository supports it. Keep the README concise
and move detailed behavior, configuration, limitations, and contributor material into the documentation.

## Changelog

This project follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). Record every user-facing change after the
latest tagged release under the `[Unreleased]` heading in `CHANGELOG.md`.

## Doctrine of the Second Sun

This repository adopts the literary style, coding, image, generation, and gold-exemplar guides, as well as the Measure
of Words and Ruinenwert, from `jbboehr/doctrine-of-the-second-sun`, pinned through Composer. The installed guides live
under `vendor/jbboehr/doctrine-of-the-second-sun/`. The gold exemplars are a nonnormative quality ceiling. Akashi does
not adopt the package's Code of Sovereignty.

This file remains authoritative for Akashi's source scope, placement, citation allocation, preservation rules, and
verification. The installed guides govern literary style, safe insertion, visual interpretation, and generation within
their stated responsibilities. `vendor/jbboehr/doctrine-of-the-second-sun/MEASURE-OF-WORDS.md` governs technical
documentation, design records, comments and docblocks, commit and change descriptions, and technical reviews and
summaries. Put the result or decision first. Use the shortest wording that remains direct and unambiguous while
preserving necessary constraints, reasoning, evidence, risks, tradeoffs, and unresolved questions. Do not apply the
Measure of Words to logia, ceremonial or creative prose, project naming, or visual language. Akashi adopts the technical
guidance in `vendor/jbboehr/doctrine-of-the-second-sun/RUINENWERT.md` for preserving software knowledge, conformance
evidence, reproducibility, and explicit replacement boundaries; apply it proportionately without inventing speculative
abstractions or documents. Formal succession, stewardship, ownership-transfer, account-custody, project-freezing, and
other governance recommendations in Ruinenwert are optional and are not adopted unless an Akashi document states so
independently. The committed Codex writer and reviewer adapters under `.codex/agents/` are reviewed copies of the
package adapters and should be compared with upstream whenever the Composer pin advances.

New named declarations under `src/` and PHP command entry points under `bin/` must contain exactly one `@logion` PHPDoc
tag. Tests, fixtures, generated code, configuration, and external stubs are out of scope.

Follow `vendor/jbboehr/doctrine-of-the-second-sun/DOCTRINE-STYLE-GUIDE.md` when writing logia and
`vendor/jbboehr/doctrine-of-the-second-sun/DOCTRINE-CODING-GUIDE.md` for placement, independence, and verification. Use
the installed generation guide and gold exemplars for generation and review. Preserve existing technical documentation
and assigned references. References must use one of `OSD`, `RAS`, `AWC`, or `SFA`, must match `BOOK C:V`, and must be
unique in this repository.

For new or explicitly revised logia, preserve a fixed declaration-to-opaque-ID mapping through separate code-blind
writer and reviewer passes. The parent agent owns entropy-backed length-pressure sampling, citation allocation and
collision checks, the reject-only implementation-leakage review, insertion, and repository verification. Do not select
or remap a candidate because it appears relevant to a declaration.

Do not add or revise a logion on a preexisting declaration unless the user explicitly requests a doctrine pass.

The logia introduced or revised by commit `899ac420055502e3e0c12c3f9980f1863d0e13b9` are designated higher-effort logia.
Preserve their prose and assigned references during future general, repository-wide, or corpus-balancing rewrite passes.
Do not submit them for regeneration unless the user explicitly instructs that those higher-effort logia are in scope.
