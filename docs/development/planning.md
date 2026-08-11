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

The normal development stack remains on PHPUnit 11.5. An isolated consumer fixture installs the current Composer archive
with PHPUnit 10.5, outside the root ParaTest constraint, then verifies the runtime data-provider trait, authored skips,
both execution backends, and the PHPStan `RuleTestCase` adapter. `composer test:phpunit10` runs that compatibility gate,
and `composer check:full` includes it.

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

Planned Akashi work is:

1. A PHPStan JSON decoder that produces typed data. It must preserve top-level errors, file association and available
   diagnostic details instead of flattening every outcome directly into `AnalyzerDiagnostic`.
2. Structured command and verification results usable without PHPUnit or `RuleTestCase`. The existing
   `DiagnosticMatcher`, `DiagnosticsMatched` and `DiagnosticsMismatched` types already provide the lower-level matching
   result.
3. A thin PHPStan command adapter that accepts an explicit project root and a typed argument vector or equivalent
   injection-safe command value. It should retain the exit status, standard output, standard error, timeout, signal and
   launch-failure evidence, and distinguish a diagnostic mismatch from malformed analyzer output or command failure.
4. A compatibility migration of one consumer fixture, comparing Akashi's result with the existing harness before any
   duplicate parser is removed.
5. Later integration with external canonical PHP examples and stable named regions. That source-model work may let
   ordinary PHP files carry diagnostic expectations while remaining directly usable by IDEs, formatters, PHP and
   PHPStan, but it is not a prerequisite for decoding or command execution.

Illustrative API names such as `PhpStanJsonDiagnostics::decode()` are placeholders, not settled public contracts. Akashi
must not become responsible for constructing temporary Composer projects, adding Composer repositories, resolving or
installing dependencies, creating or inspecting archives, defining another package's compatibility matrix, or running
package-specific runtime assertions.

This work begins after 0.1 and must not expand the existing Markdown MVP.

The current public architecture is documented in `docs/pages/project/architecture.md`. The initial proposal is preserved
as `docs/development/initial-architecture-plan.md`; detailed historical requirements and clean-room constraints remain
in `docs/IMPLEMENTATION_HANDOFF.md` and `docs/CLEAN_ROOM.md`.
