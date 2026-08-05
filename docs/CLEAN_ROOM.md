# Clean-room record

This record tracks the materials used to design and implement Akashi. It should be updated in the same change that
introduces a new external design reference.

## Boundary and confirmations

Akashi's MVP is being designed independently from competing PHP documentation-test frameworks. During the MVP:

- no source code, tests, package archives, installed files, documentation, READMEs, examples, CLI help, configuration
  references, issue discussions, or third-party summaries were examined for `testflowlabs/doctest`, `texthtml/doctest`,
  `monadial/phpunit-docrunner`, `hoaproject/Kitab`, or any other competing PHP documentation-test framework;
- no rustdoc, Cargo, compiler, or related doctest implementation source, internal tests, contributor-oriented design
  material, source-level architecture description, or summary of such implementation material was examined;
- no prohibited implementation code was examined; and
- the Rust and Cargo review was limited to official public user-facing behavior.

The only implementation material used as prior art is the user-owned Yumemi material explicitly allowed by the handoff.
The accidental PHPStan API-reference exposure recorded below is not a competing doctest implementation and did not
influence Akashi's design.

## User-owned implementation materials

These local materials are allowed inputs rather than external clean-room references:

| Material                                                | Purpose                                                                                |
| ------------------------------------------------------- | -------------------------------------------------------------------------------------- |
| `tmp/imm.php` (`jbboehr/yumemi`)                        | Generic project scaffold and the current documentation-test behavior to migrate        |
| `tmp/yumemi-apocrypha.php` (`jbboehr/yumemi-apocrypha`) | Generic project scaffold and the second current documentation-test behavior to migrate |
| `docs/IMPLEMENTATION_HANDOFF.md`                        | Project scope, requirements, compatibility targets, and clean-room policy              |

The behavior inventory and independently derived compatibility decisions are recorded in `MIGRATING_YUMEMI.md`.

## External documents consulted

The allowed classifications are **specification**, **integration guide**, and **user-facing behavioral reference**.
Dates record the review date, not the document publication date.

### PHP

| Date       | Document                                                                                              | Classification                   | Design use                                                                |
| ---------- | ----------------------------------------------------------------------------------------------------- | -------------------------------- | ------------------------------------------------------------------------- |
| 2026-08-04 | [Assertions](https://www.php.net/manual/en/function.assert.php)                                       | User-facing behavioral reference | Native assertion behavior and configuration constraints                   |
| 2026-08-04 | [`eval`](https://www.php.net/manual/en/function.eval.php)                                             | User-facing behavioral reference | Evaluation scope, tag handling, return values, and throwable parse errors |
| 2026-08-04 | [Namespaces overview](https://www.php.net/manual/en/language.namespaces.php)                          | User-facing behavioral reference | Namespace isolation model                                                 |
| 2026-08-04 | [Namespace resolution rules](https://www.php.net/language.namespaces.rules.php)                       | Specification                    | Name resolution and import compatibility                                  |
| 2026-08-04 | [`proc_open`](https://www.php.net/proc-open)                                                          | User-facing behavioral reference | Subprocess boundary and descriptor behavior                               |
| 2026-08-04 | [Output control](https://www.php.net/outcontrol)                                                      | User-facing behavioral reference | Output-capture model                                                      |
| 2026-08-04 | [User-level output buffers](https://www.php.net/manual/en/outcontrol.user-level-output-buffers.php)   | User-facing behavioral reference | Buffer nesting and cleanup obligations                                    |
| 2026-08-04 | [`set_error_handler`](https://www.php.net/manual/en/function.set-error-handler.php)                   | User-facing behavioral reference | Error-handler limitations and restoration needs                           |
| 2026-08-04 | [`restore_error_handler`](https://www.php.net/manual/en/function.restore-error-handler.php)           | User-facing behavioral reference | Error-handler restoration                                                 |
| 2026-08-04 | [`token_get_all`](https://www.php.net/token-get-all)                                                  | User-facing behavioral reference | Evaluation of tokenizer-only parsing; not selected for AST transforms     |
| 2026-08-04 | [`tempnam`](https://www.php.net/tempnam)                                                              | User-facing behavioral reference | Temporary subprocess and PHPStan file lifecycle                           |
| 2026-08-04 | [`register_shutdown_function`](https://www.php.net/manual/en/function.register-shutdown-function.php) | User-facing behavioral reference | Limits of in-process cleanup after termination                            |
| 2026-08-04 | [Readonly classes](https://www.php.net/manual/en/language.oop5.basic.php)                             | User-facing behavioral reference | Immutable model implementation                                            |
| 2026-08-04 | [Enumerations](https://www.php.net/manual/en/language.enumerations.php)                               | User-facing behavioral reference | Closed result and mode sets                                               |
| 2026-08-04 | [Backed enumerations](https://www.php.net/manual/en/language.enumerations.backed.php)                 | User-facing behavioral reference | Stable scalar values at CLI and serialization boundaries                  |
| 2026-08-04 | [Type declarations](https://www.php.net/manual/en/language.types.declarations.php)                    | Specification                    | Public API and callback type constraints                                  |
| 2026-08-04 | [Covariance and contravariance](https://www.php.net/manual/en/language.oop5.variance.php)             | User-facing behavioral reference | Review of extension-interface variance; no MVP mechanism derived from it  |

### PHPUnit

| Date       | Document                                                                                         | Classification                   | Design use                                                       |
| ---------- | ------------------------------------------------------------------------------------------------ | -------------------------------- | ---------------------------------------------------------------- |
| 2026-08-04 | [Writing tests for PHPUnit 11.5](https://docs.phpunit.de/en/11.5/writing-tests-for-phpunit.html) | Integration guide                | Data providers, dependency attributes, and assertion integration |
| 2026-08-04 | [Risky tests](https://docs.phpunit.de/en/11.5/risky-tests.html)                                  | User-facing behavioral reference | Output, global state, and tests-without-assertions behavior      |
| 2026-08-04 | [Assertions](https://docs.phpunit.de/en/11.5/assertions.html)                                    | Integration guide                | Failure reporting and assertion adapter design                   |
| 2026-08-04 | [Attributes](https://docs.phpunit.de/en/11.5/attributes.html)                                    | Integration guide                | Supported provider and test metadata                             |
| 2026-08-04 | [Error handling](https://docs.phpunit.de/en/11.5/error-handling.html)                            | User-facing behavioral reference | Interaction with PHP error handling and process state            |

### PHPStan

| Date       | Document                                                                                        | Classification                   | Design use                                                                 |
| ---------- | ----------------------------------------------------------------------------------------------- | -------------------------------- | -------------------------------------------------------------------------- |
| 2026-08-04 | [Testing extensions](https://phpstan.org/developing-extensions/testing)                         | Integration guide                | `RuleTestCase` integration and exact diagnostic expectations               |
| 2026-08-04 | [Custom rules](https://phpstan.org/developing-extensions/rules)                                 | Integration guide                | Rule type and registration boundaries                                      |
| 2026-08-04 | [PHPDoc types](https://phpstan.org/writing-php-code/phpdoc-types)                               | Specification                    | Generic collection and callable PHPDoc types                               |
| 2026-08-04 | [Command-line usage](https://phpstan.org/user-guide/command-line-usage)                         | Integration guide                | Considered CLI execution; direct test integration remains preferred        |
| 2026-08-04 | [Output formats](https://phpstan.org/user-guide/output-format)                                  | Integration guide                | Considered machine-readable diagnostics; not needed for the direct adapter |
| 2026-08-04 | [`RuleTestCase` public API](https://apiref.phpstan.org/2.1.x/PHPStan.Testing.RuleTestCase.html) | User-facing behavioral reference | Public extension points and declared method contracts                      |
| 2026-08-05 | [Error identifiers](https://phpstan.org/error-identifiers)                                      | User-facing behavioral reference | Diagnostic identity for a possible post-MVP expectation syntax             |
| 2026-08-05 | [Ignoring errors](https://phpstan.org/user-guide/ignoring-errors)                               | User-facing behavioral reference | PHPStan's documented identifier-oriented inline-comment convention         |

### Composer

| Date       | Document                                                                   | Classification                   | Design use                                                         |
| ---------- | -------------------------------------------------------------------------- | -------------------------------- | ------------------------------------------------------------------ |
| 2026-08-04 | [Basic usage](https://getcomposer.org/doc/01-basic-usage.md)               | Integration guide                | Package installation and autoloading contract                      |
| 2026-08-04 | [`composer.json` schema](https://getcomposer.org/doc/04-schema.md)         | Specification                    | Runtime requirements, optional suggestions, and binary declaration |
| 2026-08-04 | [Vendor binaries](https://getcomposer.org/doc/articles/vendor-binaries.md) | Integration guide                | `vendor/bin/akashi` proxy behavior                                 |
| 2026-08-04 | [Composer runtime API](https://getcomposer.org/doc/07-runtime.md)          | Integration guide                | Discovering the project autoloader from the binary proxy           |
| 2026-08-04 | [Package versions](https://getcomposer.org/doc/articles/versions.md)       | User-facing behavioral reference | Dependency-constraint strategy                                     |

### CommonMark and selected general-purpose dependencies

| Date       | Document                                                                                             | Classification                   | Design use                                                                  |
| ---------- | ---------------------------------------------------------------------------------------------------- | -------------------------------- | --------------------------------------------------------------------------- |
| 2026-08-04 | [CommonMark 0.31.2 specification](https://spec.commonmark.org/0.31.2/)                               | Specification                    | Fenced-code parsing, indentation, containers, and info strings              |
| 2026-08-04 | [League CommonMark overview](https://commonmark.thephpleague.com/2.x/)                               | User-facing behavioral reference | Library scope and CommonMark conformance                                    |
| 2026-08-04 | [League CommonMark installation](https://commonmark.thephpleague.com/2.x/installation/)              | Integration guide                | PHP and extension requirements                                              |
| 2026-08-04 | [League CommonMark customization](https://commonmark.thephpleague.com/2.x/customization/overview/)   | Integration guide                | Parser/environment construction                                             |
| 2026-08-04 | [League CommonMark AST](https://commonmark.thephpleague.com/2.x/customization/abstract-syntax-tree/) | Integration guide                | Public traversal and source-position capabilities                           |
| 2026-08-04 | [League CommonMark configuration](https://commonmark.thephpleague.com/2.x/configuration/)            | Integration guide                | Minimal core parser configuration                                           |
| 2026-08-04 | [League CommonMark changelog](https://commonmark.thephpleague.com/2.x/changelog/)                    | User-facing behavioral reference | Current supported branch and relevant fixes                                 |
| 2026-08-04 | [League CommonMark package metadata](https://packagist.org/packages/league/commonmark)               | User-facing behavioral reference | Current release and dependency constraints                                  |
| 2026-08-04 | [PHP-Parser README](https://github.com/nikic/PHP-Parser#readme)                                      | Integration guide                | Public parsing, AST, location, name-resolution, and transformation features |
| 2026-08-04 | [PHP-Parser package metadata](https://packagist.org/packages/nikic/php-parser)                       | User-facing behavioral reference | Current release and PHP compatibility                                       |
| 2026-08-04 | [Symfony Process 7.4](https://symfony.com/doc/7.4/components/process.html)                           | Integration guide                | Array commands, output streams, exit status, and timeouts                   |
| 2026-08-04 | [Symfony 7.4 release](https://symfony.com/releases/7.4)                                              | User-facing behavioral reference | PHP compatibility and LTS status                                            |
| 2026-08-04 | [Symfony Process package metadata](https://packagist.org/packages/symfony/process)                   | User-facing behavioral reference | Current release and dependency constraints                                  |

### Cargo and rustdoc

| Date       | Document                                                                                                             | Classification                   | Design use                                                                                                                                         |
| ---------- | -------------------------------------------------------------------------------------------------------------------- | -------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| 2026-08-04 | [rustdoc documentation tests](https://doc.rust-lang.org/stable/rustdoc/write-documentation/documentation-tests.html) | User-facing behavioral reference | Observable concepts only: hidden support lines, ignored examples, non-running examples, expected runtime failure, and expected compilation failure |
| 2026-08-04 | [`cargo test`](https://doc.rust-lang.org/cargo/commands/cargo-test.html)                                             | User-facing behavioral reference | User-visible doctest invocation and working-directory behavior                                                                                     |
| 2026-08-04 | [Cargo targets](https://doc.rust-lang.org/cargo/reference/cargo-targets.html)                                        | User-facing behavioral reference | User-visible documentation-test target behavior and terminology                                                                                    |

## Rust and rustdoc influence

The Rust review influenced only the roadmap and vocabulary used to discuss deferred behavior:

- hidden support lines motivate preserving separate authored, semantic, and possible future display views;
- ignored examples motivate a future explicit skip directive;
- non-running examples motivate a future parse-or-analyze-only mode;
- expected runtime failure motivates a future typed failure expectation;
- expected compilation failure motivates a future exact PHPStan-diagnostic expectation; and
- Cargo's documented working-directory behavior reinforces that Akashi must make its execution directory explicit.

Akashi does not adopt the names `should_panic`, `no_run`, or `compile_fail` as APIs. No Rust-specific algorithm, API
shape, source transformation, or internal architecture was copied or adapted.

## Independently derived Akashi decisions

The following decisions come from Yumemi's observed requirements and established PHP ecosystem contracts, not a
competing doctest implementation:

- immutable documents, examples, locations, and result variants follow the project's strict PHPStan and readonly-model
  conventions;
- the discovery, transformation, execution, verification, and integration split follows the need to reuse one corpus for
  CLI extraction, runtime tests, and PHPStan tests;
- generated IDs, marker syntax, extraction behavior, in-process execution, assertion handling, unique namespace
  isolation, and exact PHPStan matching originate in the existing Yumemi harnesses and migration requirements;
- CommonMark AST extraction follows the CommonMark specification and League CommonMark's public integration API;
- PHP parsing and name resolution follow PHP language rules and PHP-Parser's public integration API;
- subprocess isolation follows PHP's process model and Symfony Process's public integration API; and
- PHPUnit, PHPStan, Composer binary, and autoloader adapters follow their respective official integration contracts; and
- PHPStan's diagnostic identifiers and identifier-oriented inline ignore comments are observed public behavior; the
  proposed post-MVP `@akashi-phpstan-error` prefix, grammar, statement association, and expectation semantics are Akashi
  decisions, not syntax from a competing doctest implementation.

## Accidental exposure

On 2026-08-04, a search for PHPStan's official `RuleTestCase` API returned and displayed a small excerpt from PHPStan's
generated source-reference page at `https://apiref.phpstan.org/2.1.x/source-src.Testing.RuleTestCase.html`. The result
exposed public method declarations and a short implementation excerpt concerning diagnostic collection and sorting.
PHPStan is an explicitly allowed integration platform, not a competing doctest framework, but implementation review was
unnecessary for this design task. The excerpt was disregarded; no implementation detail from it was incorporated. The
architecture relies only on PHPStan's official testing guide and public class API listed above.

No competing PHP doctest documentation or prohibited implementation material was accidentally exposed.

## Current dependency status

At the time of this record, Akashi has no runtime dependencies beyond PHP 8.2 or later. PHPUnit, PHPStan and its
extensions, PHP-CS-Fixer, and Infection are development-only dependencies. `ARCHITECTURE.md` proposes general-purpose
runtime dependencies, but those must be validated and introduced in a later, working implementation chunk.
