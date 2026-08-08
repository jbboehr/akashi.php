# Compatibility and Safety

Akashi is a reusable documentation-example library for PHP projects. Its Markdown, runtime, PHPUnit, and PHPStan
workflows are usable outside its original consumers. It remains under active development and has not published its first
tagged release.

## Supported Platforms and Integrations

| Component         | Current boundary                                                                  |
| ----------------- | --------------------------------------------------------------------------------- |
| PHP               | 8.2 and later                                                                     |
| Composer          | Runtime API 2.2 and later                                                         |
| Markdown          | CommonMark fenced PHP blocks through `league/commonmark` 2.8                      |
| PHPUnit           | Optional consumer integration targeting PHPUnit 11.5                              |
| PHPStan           | Optional consumer integration targeting PHPStan 2.x                               |
| ParaTest          | Development-only verified runner; not required by consumers                       |
| Operating systems | Unix-like CI is exercised; Windows-specific discovery identity remains unverified |

Akashi's core model, discovery, Markdown extraction, transformation, execution, and CLI do not require PHPUnit or
PHPStan to autoload. Integration namespaces require the corresponding optional packages when used.

## Authoring Boundary

- Markdown is the only implemented documentation source. PHPDoc fences, external canonical example files, named regions,
  and synchronized copies are deferred.
- Every fence whose first info-string word is `php` enters the corpus. General language inference and “all code blocks”
  modes are not implemented.
- Runtime directives are `<!-- akashi: skip -->` and `<!-- akashi: separate-process -->`.
- Global ignore, compile-only, expected compilation failure, expected runtime failure, platform conditions, custom skip
  reasons, and hidden support code are deferred.
- There is no expected-output contract. Stdout and stderr are captured for diagnostics but do not fail an otherwise
  successful execution.
- Native expected exceptions do not yet have an Akashi authoring API equivalent to PHPUnit's `expectException()`.

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
transforms. Parse, assertion, runtime, and PHPStan reports prefer a maintained Markdown line when the underlying tool
supplies a usable generated line. When it cannot establish an exact mapping, it reports the example start explicitly;
low-level metadata may still contain a temporary-file path.

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

These two migrations complete the recorded MVP consumer acceptance gates. Akashi remains pre-1.0 while its public API
and documented limitations receive a stability review.
