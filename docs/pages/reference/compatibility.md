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
workflows are usable outside its original consumers. The 0.1 release remains under active pre-1.0 development, so its
public API may change between minor releases before 1.0.

## Supported Platforms and Integrations

| Component         | Current boundary                                                                              |
| ----------------- | --------------------------------------------------------------------------------------------- |
| PHP               | 8.1 and later                                                                                 |
| Composer          | Runtime API 2.2 and later                                                                     |
| Documentation     | CommonMark PHP fences in Markdown/PHPDoc; PHPDoc references to canonical PHP files or regions |
| PHPUnit           | Optional consumer integration supporting the PHPUnit 10.5 and 11.5 release lines              |
| PHPStan           | Optional integration supporting 1.12 with a PHP-Parser 4 pin, and 2.x by default              |
| ParaTest          | Development-only verified runner; not required by consumers                                   |
| Operating systems | Linux is primary and gating; macOS and Windows have advisory PHP 8.2 CI                       |

Akashi's core model, Markdown/PHPDoc discovery and extraction, transformation, execution, and CLI do not require PHPUnit
or PHPStan to autoload. Integration namespaces require the corresponding optional packages when used.

PHP 8.1 no longer receives upstream security fixes. Akashi verifies compatibility for maintained downstream runtimes and
legacy development environments; this compatibility statement does not make an unpatched PHP 8.1 runtime suitable for a
public-facing service.

On pushes to `master`, the advisory macOS and Windows jobs run Composer validation, PHPStan, PHPUnit, package
validation, and a CLI smoke test.

Akashi develops against PHPUnit 11.5 on PHP 8.2 and later. Its PHP 8.1 CI resolves PHPUnit 10.5 and runs the full suite.
`composer test:phpunit10` additionally builds the current Composer archive, installs it into an isolated consumer
project, and exercises runtime assertions, authored skips, both execution backends, and the PHPStan `RuleTestCase`
adapter on PHP 8.1.

Akashi develops and performs its normal static analysis with PHPStan 2.x. `composer test:phpstan1` builds the current
Composer archive and verifies the same consumer integration independently with PHPStan 1.12, PHPUnit 10.5, and
PHP-Parser 4.19.5. PHPStan 1 consumers must explicitly require `nikic/php-parser:^4.19.5`; PHPStan 2 consumers use the
normal PHP-Parser 5 dependency resolution. PHPStan 2 projects using `composer update --prefer-lowest` must explicitly
require `nikic/php-parser:^5.8` so the dual Akashi constraint does not select Parser 4 for PHPStan 2's process.

## Authoring Boundary

- Markdown and PHPDoc fences plus PHPDoc references to external canonical PHP files and named regions are implemented.
  Synchronized copies remain deferred.
- Every fence whose first info-string word is `php` enters the corpus. General language inference and “all code blocks”
  modes are not implemented.
- PHPDoc extraction inspects every `T_DOC_COMMENT` in selected `.php` files. Only interior docblock lines participate;
  content beside `/**` or `*/` is not interpreted as Markdown, and symbol attachment is not exposed as model metadata.
- Runtime directives are `skip`, `separate-process`, and the typed in-process `expect-exception ThrowableClass`.
  Documentation fences accept associated HTML forms or token-aware PHP line comments; canonical external examples use
  PHP line comments. Combining both forms of the same directive is invalid.
- Global ignore, compile-only, expected compilation failure, general expected runtime failure, platform conditions,
  custom skip reasons, and hidden support code are deferred.
- There is no expected-output contract. Stdout and stderr are captured for diagnostics but do not fail an otherwise
  successful execution.
- Expected exceptions currently match an available `Throwable` type and its subtypes. Message and code constraints and
  separate-process support are deferred.

A runtime-skipped fence remains in the corpus and may still participate in PHPStan or extraction. For a fragment that
should enter no workflow, select a narrower document set or use another fence language.

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

An expected exception changes only the interpretation of a clean in-process execution result. A matching execution
exception passes; normal completion, a mismatched type, an unavailable or non-`Throwable` class, and any cleanup failure
fail. It does not make infrastructure, transformation, or arbitrary process failure successful.

## PHPStan Boundary

The `//!` syntax is retained for current consumer compatibility. It matches mutable diagnostic message and tip text, not
PHPStan identifiers, and must occupy its own line. Akashi requires exact diagnostic counts and a deterministic,
one-to-one substring assignment.

PHPStan verification loads every relevant example into the hosting test process before analysis. Persistent declarations
cannot be unloaded, so preflight rejects collisions and built-in `define()`. Use one corpus-level verification test per
declaration set and provide only trusted, runtime-safe top-level code.

## ParaTest and Platform Notes

In this repository's PHP 8.2 CI, ParaTest runs the full suite with two workers in both default TestCase-level mode and
`--functional` test-level mode. The gate covers consumer-shaped data sets, both runtime backends, and the PHPStan
`RuleTestCase` adapter. Each PHPStan corpus assertion still runs wholly inside one worker; do not split one declaration
set across test methods or repeat it in the same worker process.

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
