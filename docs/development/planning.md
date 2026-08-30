# Planning

The Markdown MVP is implemented as a reusable library: deterministic CommonMark discovery, typed example metadata,
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
`composer test:parallel` runs both variants in a mutable checkout; the authoritative Nix gate exposes each mode as an
independent PHP 8.2 check while sequential tests cover the supported PHP matrix.

The normal development stack remains on PHP 8.2 with PHPUnit 11.5. The exhaustive Nix gate runs the full sequential
suite independently on PHP 8.1 through 8.5. Its committed PHP 8.1 closure selects PHPUnit 10.5, ParaTest 7.3, Infection
0.28, and Symfony 6.4 without changing the normal `composer.lock`. A contributor doing mutable PHP 8.1 work still needs
`composer update` instead of installing the PHP 8.2 lock and should not commit that result as the normal lock. An
isolated consumer fixture installs the current Composer archive with PHPUnit 10.5 and verifies the runtime data-provider
trait, authored skips, both execution backends, in-memory synchronization rewriting, and the PHPStan `RuleTestCase`
adapter. The Nix consumer check installs from an immutable local Composer repository; `composer test:phpunit10` retains
the conventional online path.

The normal static-analysis stack remains on PHPStan 2.x and PHP-Parser 5. An additional packaged-consumer gate installs
PHPStan 1.12 with an explicit PHP-Parser 4.19.5 pin and exercises the same PHPUnit `RuleTestCase` adapter. Akashi keeps
its parser-facing token representation independent of PHP-Parser's version-specific token class. The Nix gate exposes
this as its own immutable consumer check; `composer test:phpstan1` retains the conventional online path.

Routine validation is `nix flake check --keep-going -L`. Checks are independent derivations so failures remain visible
and successful dependency closures can be reused from the Nix store. A canonical PHP 8.2 check repeats the suite in
reverse discovery order to expose undeclared state or order coupling without making the ordinary local command
nondeterministic. Mutation testing is an explicit `nix build .#mutation -L` target and a generated Nix CI entry, not a
routine flake check. GitHub also retains three small conventional PHP 8.2 baseline jobs for PHPUnit, PHPStan, and
PHP-CS-Fixer as an independent control over the Nix harness.

Source maps now compose each transformation's generated-line relation through the preceding map. End-to-end coverage
guards maintained runtime failure locations for Markdown fences, inline PHPDoc fences, whole external PHP files, and
named regions. Synthetic generated lines remain unmapped, and a future general multi-origin mapping model is deferred
until hidden support code or another transformation requires it. The accepted hidden-support contract and its staged
multi-origin requirement are recorded in [hidden-support-code.md](hidden-support-code.md); no runtime behavior is
implemented yet.

Compile-only is implemented as a runtime disposition independent of backend selection. PHPUnit parses the maintained
example against the host PHP version, retains source-aware parse failures, records one successful assertion, and does
not apply runtime transforms, load a configured bootstrap, or execute code. Runtime skip remains higher precedence;
compile-only rejects authored separate-process selection and expected-exception contracts. The example remains available
to PHPStan and extraction.

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

Canonical authoring now uses one comma-separated `akashi:` grammar across associated HTML comments and token-aware PHP
line comments. Stable `example` identity, runtime flags, and expected-exception values merge into the existing typed
model, with every property accepted at most once per example. The CLI recognizes canonical identity without
configuration. Legacy one-property directives and one configured marker-comment dialect remain compatibility inputs;
structural region and synchronization delimiters and PHPDoc external references remain separate.

## External PHPStan verification

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
2. **Implemented:** `PhpStanResultVerifier` compares an explicit per-file expectation map with decoded diagnostics and
   returns `PhpStanVerificationResult`, preserving typed successful matches, complete mismatches, and analyzer-wide
   errors without PHPUnit or `RuleTestCase`.
3. **Implemented:** `PhpStanCommandRunner` executes an explicit, boundary-preserving executable and argument vector from
   an explicit project root without constructing a command string. `PhpStanCommandResult` retains exit status, standard
   streams, elapsed time, timeout, signal, and infrastructure-failure evidence without interpreting nonzero analysis
   exits as launch failures.
4. **Implemented:** `PhpStanCommandVerifier` composes command completion, JSON decoding, and expectation verification.
   Its result variants keep non-completion, malformed analyzer output, and completed diagnostic verification distinct.
5. **Implemented:** the isolated PHPStan 1.12 consumer fixture runs its real analyzer command through the composed
   verifier and checks the structured result before completing the package-boundary compatibility suite.
6. **Implemented:** `PhpStanExternalFixturePlanner` projects selected external canonical examples and stable named
   regions into direct analysis paths and grouped expectation maps. Ordinary PHP files carry diagnostic expectations
   while remaining directly usable by IDEs, formatters, PHP and PHPStan.
7. **Implemented:** `// @akashi-phpstan-error IDENTIFIER[: optional text]` expectations associate exact PHPStan
   identifiers and optional message-or-tip constraints with the next PHP statement. Legacy `//!` substring expectations
   remain supported without statement-line constraints.

The low-level command, decoder, framework-neutral verification, and command-verification outcome types are settled
pre-1.0 public contracts. Akashi must not become responsible for constructing temporary Composer projects, adding
Composer repositories, resolving or installing dependencies, creating or inspecting archives, defining another package's
compatibility matrix, or running package-specific runtime assertions.

The previously passing standalone-decoder consumer gate supplied the migration baseline for step 5. The composed
verifier replaced that path directly; the fixture did not retain two duplicate parsers in the same invocation. The same
fixture now builds its analysis paths and expectations from a referenced canonical PHP file, completing step 6 without
moving disposable-project preparation into Akashi.

This work begins after 0.1 and must not expand the existing Markdown MVP.

The current public architecture is documented in `docs/pages/project/architecture.md`. The initial proposal is preserved
as `docs/development/initial-architecture-plan.md`; detailed historical requirements and clean-room constraints remain
in `docs/IMPLEMENTATION_HANDOFF.md` and `docs/CLEAN_ROOM.md`.
