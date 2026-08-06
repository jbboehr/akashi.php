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

The only implementation material used as domain prior art is the user-owned Yumemi material explicitly allowed by the
handoff. The narrow League CommonMark public-API source inspection and accidental PHPStan API-reference exposure
recorded below concern allowed integration dependencies, not competing doctest implementations; neither supplied an
Akashi domain algorithm or architecture.

## User-owned implementation materials

These local materials are allowed inputs rather than external clean-room references:

| Material                                                                                        | Purpose                                                                                |
| ----------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------- |
| `tmp/imm.php` (`jbboehr/yumemi`)                                                                | Generic project scaffold and the current documentation-test behavior to migrate        |
| `tmp/yumemi-apocrypha.php` (`jbboehr/yumemi-apocrypha`)                                         | Generic project scaffold and the second current documentation-test behavior to migrate |
| `tmp/imm.php/tests/Documentation/MarkdownExamples.php`                                          | Yumemi's document-manifest discovery behavior                                          |
| `tmp/imm.php/tests/Documentation/MarkdownExamplesTest.php`                                      | Manifest ordering, extension filtering, and exclusion fixtures                         |
| `tmp/yumemi-apocrypha.php/tests/Documentation/MarkedCodeBlockExtractor.php`                     | Legacy marked-extraction and final-newline behavior                                    |
| `tmp/yumemi-apocrypha.php/tests/Documentation/MarkedCodeBlockExtractorTest.php`                 | Legacy extraction examples and failure expectations                                    |
| `tmp/yumemi-apocrypha.php/tests/Documentation/extract-markdown-example.php`                     | Legacy CLI stream and exit-status behavior                                             |
| `tmp/yumemi-apocrypha.php/tests/Consumer/run`                                                   | The eight current marked-example consumer invocations                                  |
| `tmp/yumemi-apocrypha.php/{README.md,docs/pages/getting-started.md,docs/pages/integrations.md}` | Byte-for-byte extraction compatibility corpus                                          |
| `docs/IMPLEMENTATION_HANDOFF.md`                                                                | Project scope, requirements, compatibility targets, and clean-room policy              |

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
| 2026-08-05 | [`ob_start`](https://www.php.net/manual/en/function.ob-start.php)                                     | User-facing behavioral reference | Owned-buffer creation, nesting, and buffer-control flags                  |
| 2026-08-05 | [`ob_get_status`](https://www.php.net/manual/en/function.ob-get-status.php)                           | User-facing behavioral reference | Detecting whether nested output buffers are removable                     |
| 2026-08-05 | [`ob_get_clean`](https://www.php.net/manual/en/function.ob-get-clean.php)                             | User-facing behavioral reference | Capturing and removing Akashi's owned output buffer                       |
| 2026-08-05 | [`ob_end_flush`](https://www.php.net/manual/en/function.ob-end-flush.php)                             | User-facing behavioral reference | Folding nested handler output into Akashi's owned buffer                  |
| 2026-08-05 | [Output-control constants](https://www.php.net/manual/en/outcontrol.constants.php)                    | Specification                    | Interpreting output-buffer removability flags                             |
| 2026-08-05 | [`getcwd`](https://www.php.net/manual/en/function.getcwd.php)                                         | User-facing behavioral reference | Capturing and verifying the process working directory                     |
| 2026-08-05 | [`chdir`](https://www.php.net/manual/en/function.chdir.php)                                           | User-facing behavioral reference | Restoring the process working directory and detecting failure             |
| 2026-08-05 | [`error_reporting`](https://www.php.net/manual/en/function.error-reporting.php)                       | User-facing behavioral reference | Capturing and restoring the process error-reporting level                 |

### PHPUnit

| Date       | Document                                                                                         | Classification                   | Design use                                                       |
| ---------- | ------------------------------------------------------------------------------------------------ | -------------------------------- | ---------------------------------------------------------------- |
| 2026-08-04 | [Writing tests for PHPUnit 11.5](https://docs.phpunit.de/en/11.5/writing-tests-for-phpunit.html) | Integration guide                | Data providers, dependency attributes, and assertion integration |
| 2026-08-04 | [Risky tests](https://docs.phpunit.de/en/11.5/risky-tests.html)                                  | User-facing behavioral reference | Output, global state, and tests-without-assertions behavior      |
| 2026-08-04 | [Assertions](https://docs.phpunit.de/en/11.5/assertions.html)                                    | Integration guide                | Failure reporting and assertion adapter design                   |
| 2026-08-04 | [Attributes](https://docs.phpunit.de/en/11.5/attributes.html)                                    | Integration guide                | Supported provider and test metadata                             |
| 2026-08-04 | [Error handling](https://docs.phpunit.de/en/11.5/error-handling.html)                            | User-facing behavioral reference | Interaction with PHP error handling and process state            |

### PHPStan

| Date       | Document                                                                                                     | Classification                   | Design use                                                                         |
| ---------- | ------------------------------------------------------------------------------------------------------------ | -------------------------------- | ---------------------------------------------------------------------------------- |
| 2026-08-04 | [Testing extensions](https://phpstan.org/developing-extensions/testing)                                      | Integration guide                | `RuleTestCase` integration and exact diagnostic expectations                       |
| 2026-08-04 | [Custom rules](https://phpstan.org/developing-extensions/rules)                                              | Integration guide                | Rule type and registration boundaries                                              |
| 2026-08-04 | [PHPDoc types](https://phpstan.org/writing-php-code/phpdoc-types)                                            | Specification                    | Generic collection and callable PHPDoc types                                       |
| 2026-08-04 | [Command-line usage](https://phpstan.org/user-guide/command-line-usage)                                      | Integration guide                | Considered CLI execution; direct test integration remains preferred                |
| 2026-08-04 | [Output formats](https://phpstan.org/user-guide/output-format)                                               | Integration guide                | Considered machine-readable diagnostics; not needed for the direct adapter         |
| 2026-08-04 | [`RuleTestCase` public API](https://apiref.phpstan.org/2.1.x/PHPStan.Testing.RuleTestCase.html)              | User-facing behavioral reference | Public extension points and declared method contracts                              |
| 2026-08-05 | [Error identifiers](https://phpstan.org/error-identifiers)                                                   | User-facing behavioral reference | Diagnostic identity for a possible post-MVP expectation syntax                     |
| 2026-08-05 | [Ignoring errors](https://phpstan.org/user-guide/ignoring-errors)                                            | User-facing behavioral reference | PHPStan's documented identifier-oriented inline-comment convention                 |
| 2026-08-05 | [`smaller.alwaysFalse`](https://phpstan.org/error-identifiers/smaller.alwaysFalse)                           | User-facing behavioral reference | Preserving runtime guards behind a narrower public PHPDoc contract                 |
| 2026-08-05 | [`booleanAnd.alwaysFalse`](https://phpstan.org/error-identifiers/booleanAnd.alwaysFalse)                     | User-facing behavioral reference | Preserving nullable runtime guards behind a narrower public PHPDoc contract        |
| 2026-08-05 | [`argument.type`](https://phpstan.org/error-identifiers/argument.type)                                       | User-facing behavioral reference | Testing deliberate violations of narrowed PHPDoc contracts                         |
| 2026-08-05 | [`new.resultUnused`](https://phpstan.org/error-identifiers/new.resultUnused)                                 | User-facing behavioral reference | Retaining observable construction results in negative tests                        |
| 2026-08-05 | [`staticMethod.alreadyNarrowedType`](https://phpstan.org/error-identifiers/staticMethod.alreadyNarrowedType) | User-facing behavioral reference | Avoiding redundant assertion calls in contract-violation tests                     |
| 2026-08-05 | [`nullCoalesce.variable`](https://phpstan.org/error-identifiers/nullCoalesce.variable)                       | User-facing behavioral reference | Removing a redundant fallback after nonempty CLI routing                           |
| 2026-08-05 | [`return.type`](https://phpstan.org/error-identifiers/return.type)                                           | User-facing behavioral reference | Correcting transform helpers whose inferred return type was broader than declared  |
| 2026-08-05 | [`ternary.shortNotAllowed`](https://phpstan.org/error-identifiers/ternary.shortNotAllowed)                   | User-facing behavioral reference | Replacing truthiness-based edit ordering with an explicit comparison               |
| 2026-08-05 | [`arrayValues.list`](https://phpstan.org/error-identifiers/arrayValues.list)                                 | User-facing behavioral reference | Removing redundant normalization where transform inputs were already lists         |
| 2026-08-05 | [`identical.alwaysFalse`](https://phpstan.org/error-identifiers/identical.alwaysFalse)                       | User-facing behavioral reference | Aligning constructor input PHPDoc with runtime validation                          |
| 2026-08-05 | [`notIdentical.alwaysTrue`](https://phpstan.org/error-identifiers/notIdentical.alwaysTrue)                   | User-facing behavioral reference | Removing impossible empty-source branches from nonempty safety fixtures            |
| 2026-08-05 | [`match.unhandled`](https://phpstan.org/error-identifiers/match)                                             | User-facing behavioral reference | Keeping reflection-safety dispatch exhaustive and fail-closed                      |
| 2026-08-05 | [`catch.neverThrown`](https://phpstan.org/error-identifiers/catch.neverThrown)                               | User-facing behavioral reference | Expressing throwable user output-handler callbacks at the static-analysis boundary |
| 2026-08-05 | [`throws.unusedType`](https://phpstan.org/error-identifiers/throws.unusedType)                               | User-facing behavioral reference | Replacing an inaccurate broad `@throws` tag with a typed callback seam             |
| 2026-08-05 | [`nullCoalesce.offset`](https://phpstan.org/error-identifiers/nullCoalesce.offset)                           | User-facing behavioral reference | Removing a redundant fallback after a guaranteed named regex capture               |
| 2026-08-05 | [`offsetAccess.invalidOffset`](https://phpstan.org/error-identifiers/offsetAccess.invalidOffset)             | User-facing behavioral reference | Replacing a broadly typed array-key lookup with a validated argument value         |
| 2026-08-05 | [`catch.internalClass`](https://phpstan.org/error-identifiers/catch.internalClass)                           | User-facing behavioral reference | Testing PHPUnit failures without coupling to its internal exception class          |

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
- executable examples in documentation comments and externally included documentation provide user-identified behavioral
  precedent for deferred PHPDoc sources and separate code-origin and presentation locations;
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
- the preference for ordinary external PHP files as canonical substantial examples, the three planned authoring modes,
  named-region rules, synchronization safety, and formatter integration were requested by the owner and follow normal
  PHP IDE, runtime, formatter, and static-analysis workflows;
- the discovery, transformation, execution, verification, and integration split follows the need to reuse one corpus for
  CLI extraction, runtime tests, and PHPStan tests;
- generated IDs, marker syntax, extraction behavior, in-process execution, assertion handling, unique namespace
  isolation, and PHPStan's exact-count and substring contracts originate in the existing Yumemi harnesses and migration
  requirements;
- deterministic one-to-one assignment between PHPStan expectations and diagnostics is an independently designed
  strengthening approved by the owner and remains gated by the Yumemi corpus;
- CommonMark AST extraction follows the CommonMark specification and League CommonMark's public integration API;
- PHP parsing and name resolution follow PHP language rules and PHP-Parser's public integration API;
- subprocess isolation follows PHP's process model and Symfony Process's public integration API;
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

On 2026-08-05, a broad local symbol search intended to locate League CommonMark's installed public node types also
returned source filenames, public method declarations, and a few call-site lines mentioning `getStartLine()`,
`getEndLine()`, `getInfo()`, `getInfoWords()`, and `getLiteral()`. No parser implementation was opened or analyzed, and
no algorithm or implementation structure from those results was used. League CommonMark is an allowed general-purpose
dependency, but the exposed source lines were disregarded; extraction work continued from its official documentation,
the CommonMark specification, and runtime reflection of public APIs.

On 2026-08-05, a narrow local symbol search for PHPUnit's public assertion counter returned the declarations of
`Assert::getCount()` and `Assert::resetCount()` from the installed PHPUnit source file. No method body or surrounding
implementation was opened. The search result was used only to exercise the documented assertion integration in a test;
it did not influence Akashi's architecture or public API.

No competing PHP doctest documentation or prohibited implementation material was accidentally exposed.

## Allowed dependency public-API inspection

On 2026-08-05, PHP runtime reflection was used to inspect the public signatures and PHPDoc of `ParserFactory`, `Parser`,
`Token`, `NameResolver`, `NodeFinder`, and `Error` from the installed PHP-Parser 5.8 package. A small runtime parse also
observed documented public node attributes, source positions, and resolved names. No dependency source file, internal
test, implementation algorithm, or architecture material was opened or copied; the transform and source-edit design
remains independently derived from Akashi's recorded requirements.

On 2026-08-05, while implementing metadata-comment association, the installed League CommonMark 2.8.3 files
`src/Extension/CommonMark/Node/Block/HtmlBlock.php` and `src/Node/Node.php` were opened to confirm the public
`TYPE_2_COMMENT` constant and the public `previous()`, `next()`, and `parent()` node methods. This was a narrow
inspection of public API declarations in an allowed general-purpose dependency. No parser implementation, internal test,
source-level algorithm, or architecture was examined or copied. Akashi's immediate sibling-association rule remains the
independent design recorded in `ARCHITECTURE.md`; the inspection only confirmed that League CommonMark's public node API
could express it.

## Current dependency status

Akashi requires PHP 8.2 or later and the following runtime dependencies:

- `composer-runtime-api` 2.2 or later, for locating the project autoloader through Composer's generated binary proxy;
- `league/commonmark` 2.8.3 or later within the 2.x series, for standards-conforming Markdown parsing;
- `nikic/php-parser` 5.8 or later within the 5.x series, for PHP parsing and later source transformation; and
- `symfony/process` 7.4 or later within the 7.x series, for later isolated example execution.

These are general-purpose integration libraries rather than documentation-test frameworks. Their official public
documentation and package metadata are recorded above. Apart from the narrow League CommonMark public-API source
inspection recorded above, no dependency source code or internal tests were consulted. PHPUnit, PHPStan and its
extensions, PHP-CS-Fixer, and Infection remain development-only dependencies; PHPUnit and PHPStan are suggested optional
integrations for consumers.
