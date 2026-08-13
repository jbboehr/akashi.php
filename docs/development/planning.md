# Planning

The Markdown MVP is implemented as a reusable library: deterministic CommonMark discovery, markers and directives,
in-process and child-process execution, PHPUnit data sets and reporting, PHPStan `RuleTestCase` verification, and the
marked-example extraction CLI all have typed contracts and repository coverage.

Yumemi's runtime and PHPStan migration is complete. All 43 current PHP fences execute through Akashi, including two
authored-namespace examples routed to child processes, and all 15 relevant PHPStan examples are verified. Akashi's local
compatibility fixtures are self-contained and preserve all eight Apocrypha extraction outputs byte-for-byte.

Yumemi Apocrypha completed its migration in commit `f617093eeca3cf6be21907f596f15673c545927c`. Its eight marked consumer
fixtures now use the Akashi CLI, and its duplicated extractor and tests were removed after compatibility was
established. GitHub recorded 164 completed, successful check runs for that commit across the normal and isolated
consumer matrices.

All recorded MVP consumer acceptance gates are therefore complete. The initial public API review classified every
autoloadable declaration and added a public-I/O conformance suite. No committed Akashi code or tests may depend on
workspace checkout paths during future consumer verification.

ParaTest compatibility is verified with two workers in both default TestCase-level and `--functional` test-level modes.
`composer test:parallel` runs both variants; CI exercises the gate on PHP 8.2 while sequential tests cover the remaining
PHP matrix.

The normal development stack remains on PHP 8.2 with PHPUnit 11.5. PHP 8.1 CI resolves the repository's compatible
development ranges to PHPUnit 10.5, ParaTest 7.3, Infection 0.28, and Symfony 6.4, then runs the full sequential suite.
The committed `composer.lock` records the normal PHP 8.2 toolchain. A contributor working under PHP 8.1 must run
`composer update` rather than `composer install`, as the PHP 8.1 CI job does, and should not commit the resulting
lockfile changes. An isolated consumer fixture also installs the current Composer archive with PHPUnit 10.5 and verifies
the runtime data-provider trait, authored skips, both execution backends, in-memory synchronization rewriting, and the
PHPStan `RuleTestCase` adapter. `composer test:phpunit10` runs that compatibility gate, and `composer check:full`
includes it.

The normal static-analysis stack remains on PHPStan 2.x and PHP-Parser 5. An additional packaged-consumer gate installs
PHPStan 1.12 with an explicit PHP-Parser 4.19.5 pin and exercises the same PHPUnit `RuleTestCase` adapter. Akashi keeps
its parser-facing token representation independent of PHP-Parser's version-specific token class. Run the gate with
`composer test:phpstan1`; `composer check:full` includes it.

Source maps now compose each transformation's generated-line relation through the preceding map. End-to-end coverage
guards maintained runtime failure locations for Markdown fences, inline PHPDoc fences, whole external PHP files, and
named regions. Synthetic generated lines remain unmapped, and a future general multi-origin mapping model is deferred
until hidden support code or another transformation requires it.

The synchronization library parses strictly delimited `akashi-sync` regions in Markdown and conventional multiline
PHPDoc, allows formatter-compatible blank separators, resolves through the existing canonical external-source rules, and
produces typed mismatches. It can also apply those mismatches to exact code spans in memory, preserve presentation
containers and line endings, and validate the rewritten immutable document without touching the filesystem.
`sync --check` checks explicit files, reports both maintained presentation and canonical locations, and uses stable exit
statuses. It renders deterministic unified diffs from stale presentations to canonical replacements through a direct PHP
8.1-compatible `sebastian/diff` dependency. `sync --write` validates the complete selected set and canonical snapshots
before mutation, then uses the public atomic writer to reject stale maintained bytes and symbolic-link paths, flush a
temporary sibling, preserve permission bits, and replace each changed file. It rejects selected whole-file canonical
dependencies whose selected source would change, while allowing unaffected named-region dependencies.

Optional formatting is implemented for inline Markdown and PHPDoc fences through a concrete PHP-CS-Fixer adapter. A
typed immutable configuration resolves the project-installed executable and optional config inside one canonical project
root. The checker skips external whole files and named regions, which remain directly compatible with ordinary formatter
commands. It uses a private temporary PHP file and protected body boundary so project-level header rules cannot enter a
fence, preserves authored opening tags, reports formatter-proposed body changes through typed mismatches, and removes
temporary artifacts after every outcome. `format --check` supplies explicit document selection, source-labelled unified
diffs, and stable statuses without changing maintained source. The public pure rewriter applies checked mismatches for
one current document to exact code spans, restores authored Markdown/PHPDoc prefixes, and re-extracts the complete
candidate before returning a new immutable document. It rejects stale, cross-document, duplicate, referenced, and
structurally unsafe inputs. `format --write` repeats the complete formatter pass and requires identical source and
formatter results before using the existing stale-byte-protected, symbolic-link-rejecting atomic writer. No generic
formatter registry is planned.

## Deferred external PHPStan verification

Yumemi-style consumer tests may create disposable Composer projects, install a package under test and another package,
then run runtime and PHPStan checks. Composer path repositories and archives remain the correct tools for preparing
those projects. Akashi may provide the narrower analyzer-verification portion so consumers do not each parse and compare
PHPStan command-line diagnostics themselves.

The intended division is:

```text
Consumer repository
    -> prepares and installs the disposable Composer project
    -> asks Akashi to execute and verify PHPStan fixtures
    -> runs package-specific runtime smoke tests
```

Akashi work is:

1. **Implemented:** `PhpStanJsonDecoder` produces a typed `PhpStanJsonResult` for PHPStan 1.12 and 2.x. It preserves
   top-level errors, file association, counts, and available diagnostic details instead of flattening every outcome
   directly into `AnalyzerDiagnostic`.
2. Structured command and verification results usable without PHPUnit or `RuleTestCase`. The existing
   `DiagnosticMatcher`, `DiagnosticsMatched` and `DiagnosticsMismatched` types already provide the lower-level matching
   result.
3. A thin PHPStan command adapter that accepts an explicit project root and a typed argument vector or equivalent
   injection-safe command value. It should retain the exit status, standard output, standard error, timeout, signal and
   launch-failure evidence, and distinguish a diagnostic mismatch from malformed analyzer output or command failure.
4. A compatibility migration of one consumer fixture, comparing Akashi's result with the existing harness before any
   duplicate parser is removed.
5. Reuse the implemented external canonical PHP examples and stable named regions. Ordinary PHP files can now carry
   diagnostic expectations while remaining directly usable by IDEs, formatters, PHP and PHPStan; this is still not a
   prerequisite for decoding or command execution.

The decoder types are now settled pre-1.0 public contracts; command and verification API names remain undecided. Akashi
must not become responsible for constructing temporary Composer projects, adding Composer repositories, resolving or
installing dependencies, creating or inspecting archives, defining another package's compatibility matrix, or running
package-specific runtime assertions.

This work begins after 0.1 and must not expand the existing Markdown MVP.

The current public architecture is documented in `docs/pages/project/architecture.md`. The initial proposal is preserved
as `docs/development/initial-architecture-plan.md`; detailed historical requirements and clean-room constraints remain
in `docs/IMPLEMENTATION_HANDOFF.md` and `docs/CLEAN_ROOM.md`.
