# Migrating Yumemi documentation tests to Akashi

This document inventories the documentation-example behavior that Akashi must preserve as the local Yumemi projects
remove or reduce their existing test helpers. It also records completed acceptance evidence. Yumemi's runtime and
PHPStan migrations and the Yumemi Apocrypha marked-extraction migration are complete.

## Compatibility target, not architecture template

Akashi is a standalone reusable library for unrelated PHP projects. Yumemi and Yumemi Apocrypha provide concrete
compatibility requirements and integration fixtures; their current test-helper structure does not define Akashi's public
API or internal architecture.

In particular:

- core namespaces and types must describe generic documents, examples, sources, transforms, executors, and verifiers;
- document roots, exclusions, marker names, relevance predicates, bootstraps, PHPStan rules, and analyzer configuration
  must be supplied by consumers rather than embedded as Yumemi defaults;
- an extracted example must remain reusable by independent runtime and static-analysis integrations without reparsing;
- migration adapters and reduced real-world fixtures may mention Yumemi, but generic production code must not contain
  dimensional-analysis concepts;
- Akashi must not reproduce the shape of the existing test classes when a smaller, sounder, PHP-idiomatic public API
  serves both these migrations and other projects.

The behaviors below are therefore the minimum compatibility floor for the eventual migrations, not the limit of the
library's applicability.

## Reference snapshots

The inventory was taken from the user-owned local checkouts on their `develop` branches:

- `tmp/imm.php` (`jbboehr/yumemi`) at `eea3e49f1d5a991271f692a8ba22d3149ceb905c`;
- `tmp/yumemi-apocrypha.php` (`jbboehr/yumemi-apocrypha`) at `80cc826de87db44ab10c87abe3a32baf5c27614a`.

Only these allowed reference implementations and Akashi's implementation handoff were consulted. Existing doctest
framework implementations remain outside the clean-room boundary described in `CLEAN_ROOM.md`.

## Yumemi behavior inventory

### Document discovery and fenced blocks

`tests/Documentation/MarkdownExamples.php` currently owns both discovery and extraction.

- The root `README.md` is always first.
- Markdown files under `docs/pages/` are discovered recursively.
- `docs/pages/SUMMARY.md` and non-Markdown files are excluded.
- Discovered pages are sorted lexically using their slash-normalized project-relative paths.
- A missing `docs/pages/` directory fails with a `RuntimeException`.
- An unreadable document fails with a `RuntimeException`.
- An empty PHP example corpus fails with a `RuntimeException`.
- Only triple-backtick fences whose info string is `php` plus optional whitespace are selected by the current helper.
- Captured code is the text between the opening and closing fences. An authored `<?php` opening tag remains part of the
  code.

At the reference snapshot, the manifest contains 10 documents and 37 PHP fences:

| Document                              | PHP fences |
| ------------------------------------- | ---------: |
| `README.md`                           |          2 |
| `docs/pages/README.md`                |          1 |
| `docs/pages/core-concepts.md`         |          1 |
| `docs/pages/getting-started.md`       |          2 |
| `docs/pages/recipes.md`               |          5 |
| `docs/pages/reference/catalog.md`     |          3 |
| `docs/pages/reference/phpstan.md`     |          7 |
| `docs/pages/reference/runtime.md`     |         15 |
| `docs/pages/reference/unit-syntax.md` |          1 |

The current implicit identity is
`example-{first 12 hexadecimal characters of sha1(project-relative document path)}-{one-based decimal block ordinal padded to at least two digits}`.
Its label is `{project-relative document path} PHP example {ordinal}`. Akashi must preserve compatible deterministic
identity behavior while retaining an explicit marker ID as separate metadata.

The existing regular expression is not the desired Akashi parser. In particular, Akashi must correctly handle longer or
alternate fences and fence-like text inside another fence while continuing to accept every current Yumemi example.

### In-process runtime contract

`tests/Documentation/DocumentationExamplesTest.php` exposes every PHP block as an independently labeled PHPUnit data set
and executes it in-process.

The current behavior to preserve or improve is:

- parse each block with `nikic/php-parser` before execution;
- rewrite a standalone native `assert(EXPR)` call to `PHPUnit\Framework\Assert::assertTrue((bool) EXPR, DESCRIPTION)` so
  assertions do not depend on `zend.assertions`;
- generate the current assertion description from the pretty-printed expression;
- wrap each block in a namespace derived deterministically from the example label, preventing declaration collisions;
- evaluate inside a closure so top-level variables do not enter `$GLOBALS`;
- capture and discard example output;
- catch `Throwable` and fail the data set with the document label, throwable class, and message;
- always restore the output buffer;
- register an assertion for examples without native assertions so PHPUnit does not report them as risky.

Akashi must retain original source and location metadata separately from transformed source. It must diagnose constructs
that cannot be safely wrapped instead of silently skipping them. The existing helper's source reporting is limited to
its label; the migration should improve it to include the Markdown path, identity, and source line.

The transform-foundation compatibility check on 2026-08-05 parsed and namespace-isolated 35 of the 37 reference
examples. The remaining two examples—`docs/pages/recipes.md` example 4 and `docs/pages/reference/phpstan.md` example
6—contain authored namespace declarations and received the precise unsupported-example failure required by the current
owner-approved MVP policy.

The completed migration resolves those two cases with explicit `<!-- akashi: separate-process -->` directives. At the
2026-08-07 acceptance snapshot, Yumemi's documentation had grown to 43 PHP fences. All 43 were exposed as named PHPUnit
data sets and executed through Akashi; 41 ran in-process and the two authored-namespace examples ran in child processes.
No PHP fence was omitted or runtime-skipped.

### PHPStan contract

`tests/Documentation/DocumentationPhpStanExamplesTest.php` is a `RuleTestCase` using Yumemi's real
`CallToFunctionParametersRule` from the PHPStan container. Yumemi remains responsible for selecting that rule and for
providing `extension.neon` and `yumemi-tags.neon`.

A PHP block is currently PHPStan-relevant when its source contains at least one of:

- `unit_int<`;
- `unit_float<`;
- `Quantity<'`;
- `@yumemi-`;
- `//!`.

There are eight nonempty `//!` expectation lines in the current documentation. Expectations are extracted in source
order. Each expectation is the nonempty substring following `//!`, allowing leading whitespace before the marker and one
optional space after it.

For every relevant block, the current harness:

1. writes the unmodified code to a temporary file named from the generated example ID;
2. changes the working directory to the Yumemi project root so relative bootstrap paths continue to work;
3. requires every relevant temporary file before analysis so example-local declarations are visible to PHP reflection;
4. analyzes each file independently through `RuleTestCase::gatherAnalyserErrors()`;
5. requires the diagnostic count to equal the expectation count;
6. requires each expected substring to occur in at least one combined diagnostic message and tip;
7. therefore requires a relevant block without markers to analyze cleanly;
8. reports expected substrings and actual messages and tips on failure;
9. restores the working directory and removes temporary files in a `finally` block.

The current substring check is not one-to-one and does not pair expectations with diagnostics by position. Akashi must
not weaken the exact-count or substring guarantees; it should preserve expectation order in its model and reports.
PHPStan classes must remain outside Akashi's core example model.

At the 2026-08-07 acceptance snapshot, Yumemi selected 15 PHPStan-relevant examples through its five project-owned
tokens. Akashi verified all 15 through Yumemi's real `CallToFunctionParametersRule`, `extension.neon`, and
`yumemi-tags.neon`, preserving all eight authored expectations and requiring every relevant unmarked example to analyze
cleanly.

### Marked extraction helper

The reference Yumemi snapshot contained the duplicated `MarkedCodeBlockExtractor` and a direct CLI wrapper, although no
current Yumemi consumer script invoked that wrapper. The completed Yumemi migration removed both after Akashi's
equivalent extraction behavior was established. Apocrypha later removed the same duplicated implementation after its
live consumer harness migrated successfully.

## Yumemi Apocrypha behavior inventory

### Marked block selection

`tests/Documentation/MarkedCodeBlockExtractor.php` recognizes an HTML marker followed only by horizontal whitespace and
blank lines before a triple-backtick PHP fence:

```html
<!-- yumemi-example: selected-example -->
```

The current contract is:

- identifiers match `^[a-z0-9]+(?:-[a-z0-9]+)*$`;
- identifier validation occurs before attempting to read the document;
- the marker name is fixed as `yumemi-example` in the legacy helper;
- the selected fence must be a PHP fence;
- exactly one matching marked fence is required, so missing and duplicate IDs both fail;
- unreadable documents fail;
- the PHP opening tag is preserved;
- returned code ends with one appended LF newline;
- unrelated PHP fences are ignored.

Akashi must make the marker name configurable while preserving `yumemi-example` during both migrations. Its parser must
distinguish a missing marker, duplicate marker, and a marker not followed by a suitable PHP block more precisely than
the legacy regular expression does.

### CLI wrapper

Both repositories' `tests/Documentation/extract-markdown-example.php` wrappers currently accept exactly
`MARKDOWN-FILE EXAMPLE-ID` after the executable name.

- Success writes only the extracted source to stdout and exits successfully.
- Invalid invocation writes a usage line to stderr and exits `2`.
- Any validation, read, missing, or duplicate extraction failure writes the exception message to stderr and exits `1`.

Akashi may refine the command spelling, but the replacement must retain distinct stable usage and extraction statuses,
clean stdout, actionable stderr, `--help`, `--version`, and a configurable marker name.

### Consumer-generated fixtures

Apocrypha has eight markers and eight matching calls from `tests/Consumer/run`:

| Document                        | Marker ID                   | Consumer suite    |
| ------------------------------- | --------------------------- | ----------------- |
| `README.md`                     | `readme-cache-invalid`      | Illuminate Cache  |
| `docs/pages/getting-started.md` | `getting-started-invalid`   | Illuminate Cache  |
| `docs/pages/integrations.md`    | `illuminate-cache-invalid`  | Illuminate Cache  |
| `docs/pages/integrations.md`    | `illuminate-http-invalid`   | Illuminate HTTP   |
| `docs/pages/integrations.md`    | `symfony-stopwatch-invalid` | Symfony Stopwatch |
| `docs/pages/integrations.md`    | `guzzle-invalid`            | Guzzle            |
| `docs/pages/integrations.md`    | `phpgeo-invalid`            | phpgeo            |
| `docs/pages/integrations.md`    | `getid3-invalid`            | getID3 2.x only   |

Each call redirects stdout to a generated `cases/*documentation-invalid.php`, `getting-started-invalid.php`, or
`readme-invalid.php` fixture and then analyzes that fixture with the consumer's actual PHPStan configuration. Migration
acceptance therefore requires byte-equivalent generated PHP, including the opening tag and final newline.

The surrounding consumer harness remains owned by Apocrypha. Akashi must not absorb its Composer archive checks,
dependency/version matrices, package installation, extension autodetection, expected third-party diagnostics, or
source-versus-archive test modes.

## Ownership after migration

| Concern                                                  | Akashi                       | Consuming project                                    |
| -------------------------------------------------------- | ---------------------------- | ---------------------------------------------------- |
| Configurable document files, directories, and exclusions | Implements discovery         | Supplies Yumemi's manifest                           |
| Markdown fence scanning and source locations             | Owns                         | None                                                 |
| Generated example identities and labels                  | Owns deterministic mechanism | May choose display context                           |
| Explicit marker parsing and selection                    | Owns                         | Supplies `yumemi-example`                            |
| Runtime transformation and execution                     | Owns                         | Supplies bootstrap/configuration                     |
| PHPUnit data-provider or fixture composition             | Owns generic integration     | Supplies a thin project test                         |
| PHPStan expectation parsing and comparison               | Owns reusable integration    | Supplies relevance predicate, rule, and config files |
| Consumer package/archive matrices                        | No                           | Retains                                              |
| Generated HTML link checking                             | No, deferred                 | Retains                                              |

## Migration sequence and gates

Akashi's implementation gates and both consumer migrations are complete. Self-contained compatibility fixtures preserve
the eight outputs captured from Apocrypha's legacy extractor and compare them byte-for-byte with the Akashi CLI
application.

1. **Complete:** Implement and test Akashi's immutable document and example models.
2. **Complete:** Implement discovery and a fence scanner against synthetic fixtures plus reduced Yumemi examples.
3. **Complete:** Implement configurable marked selection and the extraction CLI; prove byte equality for all eight
   Apocrypha markers.
4. **Complete:** Implement in-process transformation and execution before changing Yumemi tests.
5. **Complete:** Implement the composable PHPUnit integration and migrate Yumemi's runtime documentation test.
6. **Complete:** Implement the PHPStan `RuleTestCase` seam and migrate Yumemi without weakening exact diagnostic checks.
7. **Complete:** Change Apocrypha's consumer script to invoke `vendor/bin/akashi`, retaining every surrounding consumer
   assertion.
8. **Complete:** Remove duplicated helpers only after equivalent unit and consumer coverage passes.

`GeneratedDocumentationLinkChecker` and `check-generated-links.php` are explicitly excluded from the initial Akashi
work. They remain in both projects.

## Inventory and acceptance verification

The file lists, marker locations, consumer call sites, document count, PHP fence count, and expectation count were
checked directly at the reference commits above. Akashi retains repository-owned Yumemi transform fixtures and the eight
byte-for-byte legacy Apocrypha outputs under `tests/Fixtures/Compatibility/`; committed tests do not read from or depend
on local reference checkouts.

Yumemi adopted Akashi in commit `73eb37f67699940b4e5ae013c3bbf448850f1b46`. On 2026-08-07, its `develop` checkout at
`368badd0669faec7b46052e867bd9f40be49cd29` passed:

- all 47 documentation tests, including 43 independently named runtime examples and the corpus-level PHPStan test;
- all 15 PHPStan-relevant examples and eight authored diagnostic expectations;
- the complete `composer check:full` gate with 1,633 tests and 17,565 assertions, plus PHPStan, formatting, mdBook,
  generated-link checking, benchmark smoke testing, and the packaged consumer smoke test.

The documentation tests and PHPStan analysis also passed with the installed Akashi package temporarily redirected to the
current local Akashi commit `19bb989e8cf7ca250e334e789a1f8b93e424ace1`. The redirection was restored after the check.
Yumemi's committed lock file still records the earlier compatible Akashi commit
`225cc33f61d5779791112fb6c3b0f473e9c8e5ae`; refreshing that lock after current Akashi changes are published is
bookkeeping rather than a migration blocker.

Yumemi Apocrypha adopted Akashi in commit `f617093eeca3cf6be21907f596f15673c545927c`. All eight marked-example call
sites in `tests/Consumer/run` now invoke `vendor/bin/akashi`, while the surrounding package installation, archive,
PHPStan configuration, and diagnostic assertions remain owned by Apocrypha. The legacy extractor, wrapper, and their
dedicated tests were removed in the same commit.

GitHub recorded 164 completed, successful check runs for that commit, including the normal repository checks and the
isolated consumer matrices. Together with Akashi's self-contained byte-equality fixtures, this completes the recorded
Yumemi Apocrypha acceptance gate without introducing a committed dependency on a local checkout.
