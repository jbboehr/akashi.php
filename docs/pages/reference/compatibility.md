# Compatibility and Safety

<figure class="logion" data-logion="AWC 55:2">
<div class="logion-text">
<blockquote>
<p>The wardens walked the whole circumference before admitting the procession, marking each broken hinge and hidden
passage; the singers waited without complaint, for ceremony cannot restore a gate while passing through it.</p>
</blockquote>
<p class="logion-citation">— <cite>Acts of the Western Court 55:2</cite></p>
</div>
<img src="../images/logia/AWC-55_2.webp" alt="Wardens inspecting the complete luminous circumference of a closed gate while singers wait" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

Akashi is a reusable documentation-example library for PHP projects. Its Markdown/PHPDoc, runtime, PHPUnit, and PHPStan
workflows are usable outside its original consumers. The 0.2 release remains under active pre-1.0 development, so its
public API may change between minor releases before 1.0.

## Supported Platforms and Integrations

| Component         | Current boundary                                                                              |
| ----------------- | --------------------------------------------------------------------------------------------- |
| PHP               | 8.1 and later                                                                                 |
| Composer          | Runtime API 2.2 and later                                                                     |
| Documentation     | CommonMark PHP fences in Markdown/PHPDoc; PHPDoc references to canonical PHP files or regions |
| PHPUnit           | Optional consumer integration supporting the PHPUnit 10.5 and 11.5 release lines              |
| PHPStan           | Optional integration supporting 1.12 with a PHP-Parser 4 pin, and 2.x by default              |
| PHP-CS-Fixer      | Optional executable adapter, tested with the repository's installed 3.x development release   |
| ParaTest          | Development-only verified runner; not required by consumers                                   |
| Operating systems | Linux is primary and gating; macOS and Windows have advisory PHP 8.2 CI                       |

Akashi's core model, Markdown/PHPDoc discovery and extraction, transformation, execution, and CLI do not require
PHPUnit, PHPStan, or PHP-CS-Fixer to autoload. Integration namespaces require the corresponding optional packages when
used; the formatter adapter invokes the configured Composer binary proxy without loading PHP-CS-Fixer classes.

PHP 8.1 no longer receives upstream security fixes. Akashi verifies compatibility for maintained downstream runtimes and
legacy development environments; this compatibility statement does not make an unpatched PHP 8.1 runtime suitable for a
public-facing service.

Linux has two deliberately overlapping CI paths. A small conventional PHP 8.2 matrix uses `setup-php`, Composer, and the
locked `vendor/` to run PHPUnit, PHPStan, and PHP-CS-Fixer independently of Nix. The exhaustive generated Nix matrix
repeats those three checks and adds PHP 8.1 through 8.5 runtime coverage, consumer fixtures, package and documentation
checks, both ParaTest modes, repository checks, and explicit mutation testing. On pushes to `master`, separate advisory
macOS and Windows jobs run Composer validation, PHPStan, PHPUnit, package validation, and a CLI smoke test.

Akashi develops against PHPUnit 11.5 on PHP 8.2 and later. Its Nix PHP 8.1 closure selects PHPUnit 10.5 and runs the
full suite. `composer test:phpunit10` additionally builds the current Composer archive, installs it into an isolated
consumer project, and exercises runtime assertions, authored skips, both execution backends, and the PHPStan
`RuleTestCase` adapter on PHP 8.1. The packaged library rewrites a synchronized consumer document in memory, and the
packaged CLI checks that document against a canonical named region and exercises the optional formatter process boundary
through a consumer-provided executable.

Akashi develops and performs its normal static analysis with PHPStan 2.x. `composer test:phpstan1` builds the current
Composer archive and verifies the same consumer integration independently with PHPStan 1.12, PHPUnit 10.5, and
PHP-Parser 4.19.5. That gate runs a real PHPStan 1.12 command through Akashi's composed command verifier and checks its
structured result. Its analysis path and expectations come from an external canonical PHP file referenced through
PHPDoc. The normal unit suite covers the documented PHPStan 2.x JSON shape. PHPStan 1 consumers must explicitly require
`nikic/php-parser:^4.19.5`; PHPStan 2 consumers use the normal PHP-Parser 5 dependency resolution. PHPStan 2 projects
using `composer update --prefer-lowest` must explicitly require `nikic/php-parser:^5.8` so the dual Akashi constraint
does not select Parser 4 for PHPStan 2's process.

## Authoring Boundary

- Markdown and PHPDoc fences plus PHPDoc references to external canonical PHP files and named regions are implemented.
  Strict synchronized presentations can be compared or corrected in memory through the library API and checked or
  atomically updated through the CLI. The filesystem writer rejects stale maintained bytes and symbolic-link paths.
- Optional PHP-CS-Fixer checks cover inline Markdown and PHPDoc examples without modifying maintained documents. The
  library can apply checked mismatches to a validated immutable `Document`; `format --write` repeats the complete
  formatter pass before atomically updating current, nonsymlink documents. Referenced external PHP remains the
  responsibility of ordinary project formatter commands. The formatter process and any selected PHP-CS-Fixer
  configuration execute as trusted project tooling; this boundary is not a sandbox for untrusted configuration.
- Every fence whose first info-string word is `php` enters the corpus. General language inference and “all code blocks”
  modes are not implemented.
- PHPDoc extraction inspects every `T_DOC_COMMENT` in selected `.php` files. Only interior docblock lines participate;
  content beside `/**` or `*/` is not interpreted as Markdown, and symbol attachment is not exposed as model metadata.
- Canonical example metadata uses comma-separated flags and `key=value` properties in associated `<!-- akashi: ... -->`
  comments or token-aware `// akashi: ...` PHP comments. It covers `example`, `skip`, `compile-only`,
  `separate-process`, typed `expect-exception`, and optional message and integer-code constraints. Adjacent HTML and
  inline properties merge, but every property may occur at most once. Legacy one-property directives and one explicitly
  configured marker-comment dialect remain accepted for compatibility.
- Exact `expect-output` metadata compares captured stdout bytes after successful execution or a satisfied expected
  exception contract. Akashi does not normalize line endings, trim whitespace, or match patterns. Expected stderr is not
  implemented.
- Global ignore, expected compilation failure, general expected runtime failure, platform conditions, custom skip
  reasons, and hidden support code are deferred.
- Expected exceptions match an available `Throwable` type and its subtypes. An optional message constraint uses a
  case-sensitive substring, and an optional signed base-10 integer code uses exact comparison. Both execution backends
  support this contract. A runtime string code, such as a PDO SQLSTATE, remains valid for type or message matching but
  cannot satisfy the integer code constraint.

A runtime-skipped or compile-only fence remains in the corpus and may still participate in PHPStan or extraction.
Compile-only validates host-version PHP syntax but does not execute, bootstrap, transform, or select a backend. For a
fragment that should enter no workflow, select a narrower document set or use another fence language.

## In-Process Execution Model

In-process is the default because it avoids process startup, uses PHPUnit's already-loaded project environment, and
turns rewritten native assertions into ordinary PHPUnit assertions. Akashi gives declarations a generated namespace,
evaluates code without caller local variables, captures output, and restores the working directory, error-reporting
level, and output-buffer depth.

The validator rejects known persistent-state and relocation hazards, including direct process termination, authored
namespaces, global-variable statements, writes through `$GLOBALS` or superglobals, persistent handler, environment,
locale, INI, autoloader, and shutdown mutations, and ambiguous string reflection involving local declarations.

This is best-effort isolation for trusted project code. Resource exhaustion, native-extension crashes, dynamically
reached `exit` or `die`, filesystem and network effects, and other fatal or external behavior can escape the guard.

## Separate-Process Boundary

Child execution protects the hosting PHPUnit process from ordinary parse errors, fatal behavior, signals, and nonzero
exits. It does not restrict the child's operating-system permissions and is not a security sandbox. The child inherits
the parent environment, filesystem and network access, and uses the runner's `PHP_BINARY`, a fixed Akashi INI profile,
and a fixed 60-second timeout.

Alternate PHP binaries, custom INI profiles, environment filtering, operating-system sandboxing, and configurable
timeouts are deferred. Authored namespaces, closing tags, inline HTML, and relocation-sensitive constants require this
backend and are rejected in-process rather than silently rerouted.

## Assertion and Source-Location Boundary

In-process native `assert()` calls are rewritten and always evaluate their arguments. They are not affected by
`zend.assertions`. Separate-process assertions use child PHP's enabled assertion exceptions. The supported call forms
and semantic differences are documented under [Assertion Behavior](../using/phpunit.md#assertion-behavior).

Akashi keeps original example code separate from prepared code and retains line mappings through its implemented
transforms. Parse, assertion, runtime, and PHPStan reports prefer a maintained Markdown, PHPDoc, or canonical external
PHP source line when the underlying tool supplies a usable generated line. Referenced examples separately retain all
PHPDoc presentation locations. When Akashi cannot establish an exact mapping, it reports the canonical example start
explicitly; low-level metadata may still contain a temporary-file path.

An expected exception changes only the interpretation of a clean execution result. A matching authored exception passes;
normal completion, a mismatched type, optional message substring, or optional integer code, an unavailable or
non-`Throwable` type, and any cleanup failure fail. For child execution, Akashi records typed exception evidence through
a private file rather than scraping stderr. A nonzero exit, signal, timeout, startup failure, malformed evidence, or
other infrastructure failure remains a failure and cannot satisfy the expectation.

## PHPStan Boundary

The preferred `// @akashi-phpstan-error IDENTIFIER[: optional text]` syntax matches the identifier exactly, optionally
matches message or tip text, and requires the diagnostic line to fall within the next PHP statement. Repeated directives
may target that statement. The legacy standalone `//!` syntax remains available for current consumer compatibility; it
matches mutable message and tip text across the example without constraining an identifier or statement line. Akashi
requires exact diagnostic counts and a deterministic one-to-one assignment for both forms.

PHPStan verification loads every relevant example into the hosting test process before analysis. Persistent declarations
cannot be unloaded, so preflight rejects collisions and built-in `define()`. Use one corpus-level verification test per
declaration set and provide only trusted, runtime-safe top-level code.

## ParaTest and Platform Notes

In this repository's PHP 8.2 Nix checks, ParaTest runs the full suite with two workers in both default TestCase-level
mode and `--functional` test-level mode. The gate covers consumer-shaped data sets, both runtime backends, and the
PHPStan `RuleTestCase` adapter. Each PHPStan corpus assertion still runs wholly inside one worker; do not split one
declaration set across test methods or repeat it in the same worker process.

Discovery rejects symlinked directory traversal and documents resolving outside the project root. Duplicate physical
files normally use device and inode identity. On platforms reporting inode `0`, Akashi falls back to canonical paths, so
distinct hard-link aliases may not be recognized as duplicates.

## Recorded Consumer Acceptance

Yumemi's migration is complete. Its 43 current PHP fences run through Akashi as named PHPUnit data sets: 41 in-process
and two authored-namespace examples in child processes. Its real PHPStan rule and configuration verify 15 relevant
examples and eight authored `//!` expectations. The replacement gate passed before Yumemi removed its duplicated
Markdown discovery, runtime transform, diagnostic matcher, and unused extraction helpers.

Yumemi Apocrypha's migration is also complete. Commit
[`f617093`](https://github.com/jbboehr/yumemi-apocrypha.php/commit/f617093eeca3cf6be21907f596f15673c545927c) changed all
eight marked-example consumer calls to `vendor/bin/akashi`, retained Akashi's byte-equivalent extraction contract, and
removed the duplicated extractor and its tests. GitHub recorded 164 successful check runs for that commit, including its
normal and isolated consumer matrices.

These two migrations complete the recorded MVP consumer acceptance gates. Akashi's public API and documented limitations
have completed their initial classification review; the API may change between minor releases before 1.0.
