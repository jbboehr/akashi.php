# Compatibility and Limitations

Akashi is a reusable documentation-example library for PHP projects. Its implemented Markdown, runtime, PHPUnit, and
PHPStan workflows are usable outside Yumemi. It remains under active development and has not published its first tagged
release; project-specific migration gates are recorded separately below rather than defining the library's architecture.

## Supported Integrations

| Component         | Current boundary                                                                  |
| ----------------- | --------------------------------------------------------------------------------- |
| PHP               | 8.2 and later                                                                     |
| Composer          | Runtime API 2.2 or later                                                          |
| Markdown          | CommonMark fenced PHP blocks through `league/commonmark` 2.8                      |
| PHPUnit           | Optional consumer integration targeting PHPUnit 11.5                              |
| PHPStan           | Optional consumer integration targeting PHPStan 2.x                               |
| Operating systems | Unix-like CI is exercised; Windows-specific discovery identity remains unverified |

Akashi's core model, discovery, Markdown extraction, transformation, execution, and CLI do not require PHPUnit or
PHPStan to autoload. Integration namespaces require the corresponding optional consumer package when used.

## Current Authoring Boundary

- Markdown is the only implemented documentation source. PHPDoc fences, external canonical example files, named regions,
  and synchronized copies are deferred.
- Every fence whose first info-string word is `php` enters the corpus. General inclusion modes such as “all code blocks”
  or language inference are not implemented.
- The only directive is `<!-- akashi: separate-process -->`.
- Ignore, compile-only, expected compilation failure, expected runtime failure, platform condition, and hidden support
  code semantics are deferred.
- There is no per-fence escape hatch for a non-executable `php` fragment. Select only documents whose PHP fences belong
  to the configured workflow, or use another fence language for illustrative fragments.
- There is no expected-output contract yet. Stdout and stderr are captured for diagnostics but do not fail a successful
  execution by themselves.
- Native expected exceptions do not yet have an Akashi authoring API equivalent to PHPUnit's `expectException()`.

## Runtime Boundary

Neither backend is a security sandbox. In-process execution protects common reversible host state and rejects known
unsafe syntax, but fatal errors and dynamic behavior can escape those guards. Separate-process execution protects the
PHPUnit process from ordinary child failure while retaining the child's inherited environment, filesystem access,
network access, and other operating-system permissions.

The separate-process backend currently uses the running test process's `PHP_BINARY`, fixed assertion and diagnostic INI
settings, inherited environment, and a fixed 60-second timeout. Alternate PHP binaries, custom INI profiles, environment
filtering, and configurable timeouts are deferred.

Authored namespaces, closing tags, inline HTML, and relocation-sensitive constants require separate-process execution.
They are intentionally rejected in-process rather than silently rerouted.

## PHPStan Boundary

The `//!` syntax is retained for Yumemi compatibility. It matches mutable diagnostic prose, not PHPStan identifiers, and
must occupy its own line. Akashi requires exact diagnostic counts and a one-to-one substring assignment.

PHPStan verification loads all relevant example files into the hosting test process before analysis. Persistent
declarations cannot be unloaded, so the preflight rejects collisions and built-in `define()`. Consumers should run one
corpus-level verification test per declaration set.

## Platform Notes

Discovery rejects symlinked directory traversal and documents resolving outside the configured project root. Duplicate
physical files normally use device and inode identity. On platforms reporting inode `0`, Akashi falls back to canonical
paths, so distinct hard-link aliases may not be recognized as duplicates.

ParaTest compatibility is planned but not yet verified. Corpus-level PHPStan declaration loading and any shared
in-process state deserve particular attention before parallel execution is advertised.

## Migration Status

Akashi has local compatibility fixtures for Yumemi's Markdown manifest, transform behavior, and Apocrypha's marked
extraction contract. End-to-end consumer migration remains pending, including execution of every Yumemi runtime example
and verification of its complete PHPStan diagnostic corpus. Until those gates pass, compatibility claims remain
provisional and duplicated consumer helpers should not be removed.
