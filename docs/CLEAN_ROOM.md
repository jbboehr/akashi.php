# Clean-room record

This record tracks the materials used to design and implement Akashi. It should be updated in the same change that
introduces a new external design reference.

## Boundary and confirmations

Akashi's MVP was designed and implemented independently from competing PHP documentation-test frameworks. During the
initial MVP:

- no source code, tests, package archives, installed files, documentation, READMEs, examples, CLI help, configuration
  references, issue discussions, or third-party summaries were examined for `testflowlabs/doctest`, `texthtml/doctest`,
  `monadial/phpunit-docrunner`, `hoaproject/Kitab`, or any other competing PHP documentation-test framework;
- no rustdoc, Cargo, compiler, or related doctest implementation source, internal tests, contributor-oriented design
  material, source-level architecture description, or summary of such implementation material was examined;
- no prohibited implementation code was examined; and
- the Rust and Cargo review was limited to official public user-facing behavior.

The only implementation material used as domain prior art is the user-owned Yumemi project material explicitly allowed
by the handoff. The narrow League CommonMark public-API source inspection and accidental PHPStan API-reference exposure
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
| `tmp/yumemi.php` at `368badd0669faec7b46052e867bd9f40be49cd29`                                  | Verification of the completed Yumemi consumer migration                                |
| `tmp/yumemi.php/tests/Documentation/DocumentationExamplesTest.php`                              | Thin consumer composition for runtime acceptance                                       |
| `tmp/yumemi.php/tests/Documentation/DocumentationPhpStanExamplesTest.php`                       | Thin consumer composition for PHPStan acceptance                                       |
| `tmp/yumemi-apocrypha.php/tests/Documentation/MarkedCodeBlockExtractor.php`                     | Legacy marked-extraction and final-newline behavior                                    |
| `tmp/yumemi-apocrypha.php/tests/Documentation/MarkedCodeBlockExtractorTest.php`                 | Legacy extraction examples and failure expectations                                    |
| `tmp/yumemi-apocrypha.php/tests/Documentation/extract-markdown-example.php`                     | Legacy CLI stream and exit-status behavior                                             |
| `tmp/yumemi-apocrypha.php/tests/Consumer/run`                                                   | The eight current marked-example consumer invocations                                  |
| `tmp/yumemi-apocrypha.php/{README.md,docs/pages/getting-started.md,docs/pages/integrations.md}` | Byte-for-byte extraction compatibility corpus                                          |
| `jbboehr/yumemi-apocrypha.php` at `f617093eeca3cf6be21907f596f15673c545927c`                    | Verification of the completed Apocrypha migration and legacy-extractor removal         |
| GitHub Actions check records for Apocrypha commit `f617093eeca3cf6be21907f596f15673c545927c`    | Confirmation that all 164 recorded migration checks completed successfully             |
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
| 2026-08-11 | [`random_bytes`](https://www.php.net/manual/en/function.random-bytes.php)                             | User-facing behavioral reference | Handling the CSPRNG failure exception contract across PHP 8.1 and 8.2     |

### PHPUnit

| Date       | Document                                                                                         | Classification                   | Design use                                                                         |
| ---------- | ------------------------------------------------------------------------------------------------ | -------------------------------- | ---------------------------------------------------------------------------------- |
| 2026-08-04 | [Writing tests for PHPUnit 11.5](https://docs.phpunit.de/en/11.5/writing-tests-for-phpunit.html) | Integration guide                | Data providers, dependency attributes, and exception type/message/code integration |
| 2026-08-04 | [Risky tests](https://docs.phpunit.de/en/11.5/risky-tests.html)                                  | User-facing behavioral reference | Output, global state, and tests-without-assertions behavior                        |
| 2026-08-04 | [Assertions](https://docs.phpunit.de/en/11.5/assertions.html)                                    | Integration guide                | Failure reporting and assertion adapter design                                     |
| 2026-08-04 | [Attributes](https://docs.phpunit.de/en/11.5/attributes.html)                                    | Integration guide                | Supported provider and test metadata                                               |
| 2026-08-04 | [Error handling](https://docs.phpunit.de/en/11.5/error-handling.html)                            | User-facing behavioral reference | Interaction with PHP error handling and process state                              |
| 2026-08-08 | [Annotations in PHPUnit 10.5](https://docs.phpunit.de/en/10.5/annotations.html)                  | Integration guide                | Attribute precedence during the PHPUnit 10 compatibility review                    |
| 2026-08-08 | [Supported PHPUnit versions](https://phpunit.de/supported-versions.html)                         | User-facing behavioral reference | Upstream lifecycle context for Akashi's tested version boundary                    |
| 2026-08-14 | [Writing tests for PHPUnit 12.5](https://docs.phpunit.de/en/12.5/writing-tests-for-phpunit.html) | User-facing behavioral reference | Forward-compatible confirmation of exception-message substring semantics           |

### PHPStan

| Date       | Document                                                                                                     | Classification                   | Design use                                                                         |
| ---------- | ------------------------------------------------------------------------------------------------------------ | -------------------------------- | ---------------------------------------------------------------------------------- |
| 2026-08-04 | [Testing extensions](https://phpstan.org/developing-extensions/testing)                                      | Integration guide                | `RuleTestCase` integration and exact diagnostic expectations                       |
| 2026-08-04 | [Custom rules](https://phpstan.org/developing-extensions/rules)                                              | Integration guide                | Rule type and registration boundaries                                              |
| 2026-08-04 | [PHPDoc types](https://phpstan.org/writing-php-code/phpdoc-types)                                            | Specification                    | Generic collection and callable PHPDoc types                                       |
| 2026-08-04 | [Command-line usage](https://phpstan.org/user-guide/command-line-usage)                                      | Integration guide                | JSON-format invocation and documented command-status boundary                      |
| 2026-08-04 | [Output formats](https://phpstan.org/user-guide/output-format)                                               | Integration guide                | Typed decoding of the documented JSON diagnostic shape                             |
| 2026-08-04 | [`RuleTestCase` public API](https://apiref.phpstan.org/2.1.x/PHPStan.Testing.RuleTestCase.html)              | User-facing behavioral reference | Public extension points and declared method contracts                              |
| 2026-08-06 | [`RuleTestCase` 2.2 public API](https://apiref.phpstan.org/2.2.x/PHPStan.Testing.RuleTestCase.html)          | User-facing behavioral reference | `gatherAnalyserErrors()` input and diagnostic-list contract                        |
| 2026-08-11 | [`RuleTestCase` 1.12 public API](https://apiref.phpstan.org/1.12.x/PHPStan.Testing.RuleTestCase.html)        | User-facing behavioral reference | Confirming the public analyzer adapter contract on the PHPStan 1 line              |
| 2026-08-11 | [`Error` 1.12 public API](https://apiref.phpstan.org/1.12.x/PHPStan.Analyser.Error.html)                     | User-facing behavioral reference | Confirming diagnostic accessors and decoding on the PHPStan 1 line                 |
| 2026-08-11 | [PHPStan 1.12 release roadmap](https://phpstan.org/blog/phpstan-1-12-road-to-phpstan-2-0)                    | User-facing behavioral reference | Establishing PHPStan 1.12 as the final PHPStan 1 minor compatibility line          |
| 2026-08-06 | [PHPStan strict rules](https://github.com/phpstan/phpstan-strict-rules#readme)                               | Integration guide                | Rule-specific configuration for permitting the short ternary operator              |
| 2026-08-09 | [PHPStan deprecation rules](https://github.com/phpstan/phpstan-deprecation-rules#readme)                     | Integration guide                | Enabling analysis of calls to deprecated symbols                                   |
| 2026-08-09 | [PHPStan disallowed calls](https://github.com/spaze/phpstan-disallowed-calls#readme)                         | Integration guide                | Selecting a narrow committed-debug-output policy                                   |
| 2026-08-09 | [Disallowed-calls custom rules][disallowed-calls-custom-rules]                                               | Integration guide                | Configuring grouped function-call restrictions                                     |
| 2026-08-09 | [Disallowed-call parameter conditions][disallowed-calls-parameters]                                          | Integration guide                | Permitting explicit non-output formatting modes                                    |
| 2026-08-09 | [PHPat getting started](https://www.phpat.dev/getting-started/)                                              | Integration guide                | Evaluating namespace architecture rules; adoption deferred                         |
| 2026-08-11 | [PHPDocs basics](https://phpstan.org/writing-php-code/phpdocs-basics#immutable-classes)                      | Specification                    | Preserving class-wide readonly analysis while using PHP 8.1 readonly properties    |
| 2026-08-05 | [Error identifiers](https://phpstan.org/error-identifiers)                                                   | User-facing behavioral reference | Retaining optional diagnostic identity in decoded command output                   |
| 2026-08-05 | [Ignoring errors](https://phpstan.org/user-guide/ignoring-errors)                                            | User-facing behavioral reference | PHPStan's documented identifier-oriented inline-comment convention                 |
| 2026-08-05 | [`smaller.alwaysFalse`](https://phpstan.org/error-identifiers/smaller.alwaysFalse)                           | User-facing behavioral reference | Preserving runtime guards behind a narrower public PHPDoc contract                 |
| 2026-08-05 | [`booleanAnd.alwaysFalse`](https://phpstan.org/error-identifiers/booleanAnd.alwaysFalse)                     | User-facing behavioral reference | Preserving nullable runtime guards behind a narrower public PHPDoc contract        |
| 2026-08-05 | [`argument.type`](https://phpstan.org/error-identifiers/argument.type)                                       | User-facing behavioral reference | Testing deliberate violations of narrowed PHPDoc contracts                         |
| 2026-08-05 | [`new.resultUnused`](https://phpstan.org/error-identifiers/new.resultUnused)                                 | User-facing behavioral reference | Retaining observable construction results in negative tests                        |
| 2026-08-05 | [`staticMethod.alreadyNarrowedType`](https://phpstan.org/error-identifiers/staticMethod.alreadyNarrowedType) | User-facing behavioral reference | Avoiding redundant assertion calls in contract-violation tests                     |
| 2026-08-05 | [`greater.alwaysTrue`](https://phpstan.org/error-identifiers/greater.alwaysTrue)                             | User-facing behavioral reference | Removing a redundant empty-source branch from a nonempty runtime fixture           |
| 2026-08-05 | [`property.notFound`](https://phpstan.org/error-identifiers/property.notFound)                               | User-facing behavioral reference | Narrowing a transform fixture to its backend-specific prepared-example type        |
| 2026-08-05 | [`property.nonObject`](https://phpstan.org/error-identifiers/property.nonObject)                             | User-facing behavioral reference | Correcting the same stale broad fixture return type                                |
| 2026-08-05 | [`function.impossibleType`](https://phpstan.org/error-identifiers/function.impossibleType)                   | User-facing behavioral reference | Testing backend structure through reflection instead of a statically settled check |
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
| 2026-08-06 | [`method.childParameterType`](https://phpstan.org/error-identifiers/method.childParameterType)               | User-facing behavioral reference | Matching the adapter test seam to `RuleTestCase` parameter contravariance          |
| 2026-08-06 | [`phpstanApi.constructor`](https://phpstan.org/error-identifiers/phpstanApi.constructor)                     | User-facing behavioral reference | Avoiding direct construction of PHPStan diagnostics outside its compatibility API  |
| 2026-08-05 | [`catch.internalClass`](https://phpstan.org/error-identifiers/catch.internalClass)                           | User-facing behavioral reference | Testing PHPUnit failures without coupling to its internal exception class          |
| 2026-08-05 | [`classConstant.internalClass`](https://phpstan.org/error-identifiers/classConstant.internalClass)           | User-facing behavioral reference | Avoiding PHPUnit internal-class constants in executor integration tests            |
| 2026-08-05 | [`nullsafe.neverNull`](https://phpstan.org/error-identifiers/nullsafe.neverNull)                             | User-facing behavioral reference | Replacing a redundant null-safe configuration access with explicit mode selection  |
| 2026-08-06 | [`property.nonObject`](https://phpstan.org/error-identifiers/property.nonObject)                             | User-facing behavioral reference | Carrying validated diagnostic value types into matcher property access             |
| 2026-08-06 | [`method.nonObject`](https://phpstan.org/error-identifiers/method.nonObject)                                 | User-facing behavioral reference | Carrying validated diagnostic value types into matcher method calls                |
| 2026-08-06 | [`argument.type`](https://phpstan.org/error-identifiers/argument.type)                                       | User-facing behavioral reference | Returning explicitly typed validated matcher inputs                                |
| 2026-08-06 | [`parameterByRef.type`](https://phpstan.org/error-identifiers/parameterByRef.type)                           | User-facing behavioral reference | Replacing mutable by-reference matching state with typed returned state            |
| 2026-08-06 | [`return.type`](https://phpstan.org/error-identifiers/return.type)                                           | User-facing behavioral reference | Keeping recursive matcher state consistent with its declared return shape          |
| 2026-08-06 | [`missingType.iterableValue`](https://phpstan.org/error-identifiers/missingType.iterableValue)               | User-facing behavioral reference | Specifying precise element types on data-driven matcher tests                      |
| 2026-08-09 | [`trait.unused`](https://phpstan.org/error-identifiers/trait.unused)                                         | User-facing behavioral reference | Exercising a host-declaration collision without leaving an unused test fixture     |
| 2026-08-09 | [`method.internal`](https://phpstan.org/error-identifiers/method.internal)                                   | User-facing behavioral reference | Avoiding an internal PHPUnit assertion-counter API in an integration test          |

[disallowed-calls-custom-rules]: https://github.com/spaze/phpstan-disallowed-calls/blob/main/docs/custom-rules.md
[disallowed-calls-parameters]: https://github.com/spaze/phpstan-disallowed-calls/blob/main/docs/allow-with-parameters.md

### Composer

| Date       | Document                                                                    | Classification                   | Design use                                                          |
| ---------- | --------------------------------------------------------------------------- | -------------------------------- | ------------------------------------------------------------------- |
| 2026-08-04 | [Basic usage](https://getcomposer.org/doc/01-basic-usage.md)                | Integration guide                | Package installation and autoloading contract                       |
| 2026-08-04 | [`composer.json` schema](https://getcomposer.org/doc/04-schema.md)          | Specification                    | Runtime requirements, optional suggestions, and binary declaration  |
| 2026-08-04 | [Vendor binaries](https://getcomposer.org/doc/articles/vendor-binaries.md)  | Integration guide                | `vendor/bin/akashi` proxy behavior                                  |
| 2026-08-04 | [Composer runtime API](https://getcomposer.org/doc/07-runtime.md)           | Integration guide                | Discovering the project autoloader from the binary proxy            |
| 2026-08-04 | [Package versions](https://getcomposer.org/doc/articles/versions.md)        | User-facing behavioral reference | Dependency-constraint strategy                                      |
| 2026-08-08 | [Command-line interface](https://getcomposer.org/doc/03-cli.md)             | Integration guide                | Backward-compatible repository configuration for consumer gates     |
| 2026-08-08 | [Repositories](https://getcomposer.org/doc/05-repositories.md)              | Integration guide                | Path-repository mirroring and explicit development versions         |
| 2026-08-08 | [Composer 2.9.0-RC1 changelog](https://getcomposer.org/changelog/2.9.0-RC1) | User-facing behavioral reference | Identifying the newer `repository` command's compatibility boundary |

### Doctrine of the Second Sun

These materials govern literary marginalia, technical writing, software-knowledge preservation, and their maintenance
workflows only. They did not supply Akashi runtime, doctest, extraction, or verification behavior.

| Date       | Document                                                                                                                                                                | Classification                   | Design use                                                                        |
| ---------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------- | --------------------------------------------------------------------------------- |
| 2026-08-08 | [Integration guide](https://github.com/jbboehr/doctrine-of-the-second-sun/blob/f3b65781760948594ce72d34e1bc1507c18d2065/INTEGRATION-GUIDE.md)                           | Integration guide                | Composer adoption, local-policy boundary, adapter refresh, and upgrade checks     |
| 2026-08-08 | [Style guide](https://github.com/jbboehr/doctrine-of-the-second-sun/blob/f3b65781760948594ce72d34e1bc1507c18d2065/DOCTRINE-STYLE-GUIDE.md)                              | Specification                    | Canonical literary authority and updated stochastic composition guidance          |
| 2026-08-08 | [Coding guide](https://github.com/jbboehr/doctrine-of-the-second-sun/blob/f3b65781760948594ce72d34e1bc1507c18d2065/DOCTRINE-CODING-GUIDE.md)                            | Specification                    | Safe source placement and preservation rules                                      |
| 2026-08-08 | [Image guide](https://github.com/jbboehr/doctrine-of-the-second-sun/blob/f3b65781760948594ce72d34e1bc1507c18d2065/DOCTRINE-IMAGE-GUIDE.md)                              | Specification                    | Visual translation authority and updated sampling guidance                        |
| 2026-08-08 | [Generation guide](https://github.com/jbboehr/doctrine-of-the-second-sun/blob/f3b65781760948594ce72d34e1bc1507c18d2065/DOCTRINE-GENERATION-GUIDE.md)                    | Integration guide                | Tool-neutral writer, reviewer, leakage, insertion, and verification separation    |
| 2026-08-08 | [Gold exemplars](https://github.com/jbboehr/doctrine-of-the-second-sun/blob/f3b65781760948594ce72d34e1bc1507c18d2065/DOCTRINE-GOLD-EXEMPLARS.md)                        | User-facing behavioral reference | Nonnormative quality calibration for generation and review                        |
| 2026-08-08 | [Ruinenwert](https://github.com/jbboehr/doctrine-of-the-second-sun/blob/f3b65781760948594ce72d34e1bc1507c18d2065/RUINENWERT.md)                                         | Specification                    | Long-term knowledge preservation, conformance, and replacement-boundary guidance  |
| 2026-08-08 | [Codex integration and adapters](https://github.com/jbboehr/doctrine-of-the-second-sun/tree/f3b65781760948594ce72d34e1bc1507c18d2065/integrations/codex)                | Integration guide                | Refreshing the committed read-only writer and code-blind reviewer configurations  |
| 2026-08-08 | [Upstream agent guidelines](https://github.com/jbboehr/doctrine-of-the-second-sun/blob/f3b65781760948594ce72d34e1bc1507c18d2065/AGENTS.md)                              | Integration guide                | Confirming the boundary between portable guidance and consuming-project policy    |
| 2026-08-10 | [The Measure of Words](https://github.com/jbboehr/doctrine-of-the-second-sun/blob/5c2c843c4d0f898eb5792e94187a74b2ce585ad5/MEASURE-OF-WORDS.md)                         | Specification                    | Concise, result-first technical writing without loss of necessary substance       |
| 2026-08-10 | [Updated integration guide](https://github.com/jbboehr/doctrine-of-the-second-sun/blob/5c2c843c4d0f898eb5792e94187a74b2ce585ad5/INTEGRATION-GUIDE.md)                   | Integration guide                | Explicit technical-writing adoption and local-policy boundary                     |
| 2026-08-10 | [Updated Ruinenwert](https://github.com/jbboehr/doctrine-of-the-second-sun/blob/5c2c843c4d0f898eb5792e94187a74b2ce585ad5/RUINENWERT.md)                                 | Specification                    | Confirming Akashi's deliberate local exception for formal governance guidance     |
| 2026-08-10 | [Heliogenesis integration](https://github.com/jbboehr/doctrine-of-the-second-sun/blob/5c2c843c4d0f898eb5792e94187a74b2ce585ad5/integrations/web/heliogenesis/README.md) | Integration guide                | Reviewing document-tomography behavior while preserving Akashi's unmarked article |
| 2026-08-10 | [Updated upstream agent guidelines](https://github.com/jbboehr/doctrine-of-the-second-sun/blob/5c2c843c4d0f898eb5792e94187a74b2ce585ad5/AGENTS.md)                      | Integration guide                | Confirming the Measure of Words scope and consuming-project authority             |

### CommonMark and selected general-purpose dependencies

| Date       | Document                                                                                                                                                             | Classification                   | Design use                                                                                               |
| ---------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------- | -------------------------------------------------------------------------------------------------------- |
| 2026-08-04 | [CommonMark 0.31.2 specification](https://spec.commonmark.org/0.31.2/)                                                                                               | Specification                    | Fenced-code parsing, indentation, containers, and info strings                                           |
| 2026-08-04 | [League CommonMark overview](https://commonmark.thephpleague.com/2.x/)                                                                                               | User-facing behavioral reference | Library scope and CommonMark conformance                                                                 |
| 2026-08-04 | [League CommonMark installation](https://commonmark.thephpleague.com/2.x/installation/)                                                                              | Integration guide                | PHP and extension requirements                                                                           |
| 2026-08-04 | [League CommonMark customization](https://commonmark.thephpleague.com/2.x/customization/overview/)                                                                   | Integration guide                | Parser/environment construction                                                                          |
| 2026-08-04 | [League CommonMark AST](https://commonmark.thephpleague.com/2.x/customization/abstract-syntax-tree/)                                                                 | Integration guide                | Public traversal and source-position capabilities                                                        |
| 2026-08-04 | [League CommonMark configuration](https://commonmark.thephpleague.com/2.x/configuration/)                                                                            | Integration guide                | Minimal core parser configuration                                                                        |
| 2026-08-04 | [League CommonMark changelog](https://commonmark.thephpleague.com/2.x/changelog/)                                                                                    | User-facing behavioral reference | Current supported branch and relevant fixes                                                              |
| 2026-08-04 | [League CommonMark package metadata](https://packagist.org/packages/league/commonmark)                                                                               | User-facing behavioral reference | Current release and dependency constraints                                                               |
| 2026-08-04 | [PHP-Parser README](https://github.com/nikic/PHP-Parser#readme)                                                                                                      | Integration guide                | Public parsing, AST, location, name-resolution, and transformation features                              |
| 2026-08-04 | [PHP-Parser package metadata](https://packagist.org/packages/nikic/php-parser)                                                                                       | User-facing behavioral reference | Current release and PHP compatibility                                                                    |
| 2026-08-04 | [Symfony Process 7.4](https://symfony.com/doc/7.4/components/process.html)                                                                                           | Integration guide                | Array commands, output streams, exit status, and timeouts                                                |
| 2026-08-04 | [Symfony 7.4 release](https://symfony.com/releases/7.4)                                                                                                              | User-facing behavioral reference | PHP compatibility and LTS status                                                                         |
| 2026-08-04 | [Symfony Process package metadata](https://packagist.org/packages/symfony/process)                                                                                   | User-facing behavioral reference | Current release and dependency constraints                                                               |
| 2026-08-14 | [Symfony Console component](https://symfony.com/doc/6.4/components/console.html)                                                                                     | Integration guide                | Application and command composition, help, output, and exit behavior                                     |
| 2026-08-14 | [Symfony Console input](https://symfony.com/doc/6.4/console/input.html)                                                                                              | Integration guide                | Arguments, valued options, raw tokens, option terminators, and duplicate-evidence review                 |
| 2026-08-14 | [Symfony Console command testing](https://symfony.com/doc/6.4/console.html#testing-commands)                                                                         | Integration guide                | Evaluation of framework-provided test helpers and separated output                                       |
| 2026-08-14 | [Symfony Finder component](https://symfony.com/doc/6.4/components/finder.html)                                                                                       | Integration guide                | Evaluation of discovery reuse through Akashi's existing iterable file boundary; no dependency selected   |
| 2026-08-14 | [Symfony Filesystem component](https://symfony.com/doc/6.4/components/filesystem.html)                                                                               | Integration guide                | Evaluation of filesystem convenience APIs against Akashi's stronger write boundary; no dependency added  |
| 2026-08-07 | [ParaTest README](https://github.com/paratestphp/paratest#readme)                                                                                                    | Integration guide                | TestCase- and test-level parallel modes and worker-process behavior                                      |
| 2026-08-07 | [ParaTest package metadata](https://packagist.org/packages/brianium/paratest)                                                                                        | User-facing behavioral reference | Selecting the PHP 8.2 and PHPUnit 11.5 compatible development release                                    |
| 2026-08-08 | [PHPStan `staticMethod.alreadyNarrowedType`](https://phpstan.org/error-identifiers/staticMethod.alreadyNarrowedType)                                                 | Integration guide                | Replacing a deliberately constant PHPUnit assertion with a measured completion assertion                 |
| 2026-08-08 | [PHPStan `argument.type`](https://phpstan.org/error-identifiers/argument.type)                                                                                       | Integration guide                | Proving an associated directive line non-null before formatted diagnostics                               |
| 2026-08-08 | [PHPStan `assign.propertyType`](https://phpstan.org/error-identifiers/assign.propertyType)                                                                           | Integration guide                | Preserving the nonempty normalized expected-exception class-name invariant                               |
| 2026-08-08 | [PHPStan `return.type`](https://phpstan.org/error-identifiers/return.type)                                                                                           | Integration guide                | Proving the inline directive's computed source line remains positive                                     |
| 2026-08-08 | [PHPStan `smaller.alwaysFalse`](https://phpstan.org/error-identifiers/smaller.alwaysFalse)                                                                           | Integration guide                | Removing a redundant check after PHPStan proved the computed line positive                               |
| 2026-08-15 | [PHPStan `identical.alwaysFalse`](https://phpstan.org/error-identifiers/identical.alwaysFalse)                                                                       | Integration guide                | Removing an unreachable defensive null check after metadata constraint narrowing                         |
| 2026-08-15 | [PHPStan `match.unhandled`](https://phpstan.org/error-identifiers/match.unhandled)                                                                                   | Integration guide                | Making a regular-expression capture's defensive fallback explicit                                        |
| 2026-08-15 | [PHPStan `nullsafe.neverNull`](https://phpstan.org/error-identifiers/nullsafe.neverNull)                                                                             | Integration guide                | Keeping tests consistent with assertion-driven non-null narrowing                                        |
| 2026-08-09 | [GitHub Actions billing](https://docs.github.com/en/billing/managing-billing-for-your-products/managing-billing-for-github-actions/about-billing-for-github-actions) | User-facing behavioral reference | Selecting a restrained advisory macOS and Windows matrix for a public repository                         |
| 2026-08-11 | [PHP supported versions](https://www.php.net/supported-versions.php)                                                                                                 | User-facing behavioral reference | Recording PHP 8.1's upstream end-of-life status without conflating compatibility with security support   |
| 2026-08-11 | [Symfony PHP 8.2 polyfill README](https://github.com/symfony/polyfill-php82#readme)                                                                                  | Integration guide                | Identifying the secure Random engine polyfill and the documented full-extension provider                 |
| 2026-08-11 | [Random extension polyfill compatibility](https://php-random-polyfill.readthedocs.io/en/latest/compatibility.html)                                                   | User-facing behavioral reference | Preserving `Randomizer`, seeded test engines, secure production engines, and native no-overhead behavior |
| 2026-08-11 | [Random extension polyfill package metadata](https://packagist.org/packages/arokettu/random-polyfill)                                                                | User-facing behavioral reference | Selecting a PHP 8.1-compatible 1.x release constraint                                                    |
| 2026-08-11 | [nix-phps README](https://github.com/fossar/nix-phps#readme)                                                                                                         | Integration guide                | Pinning PHP 8.1 for development-only syntax and compatibility validation                                 |
| 2026-08-13 | [nixpkgs PHP packaging manual](https://github.com/NixOS/nixpkgs/blob/531670d871c0e29724a02f3cbcac170adc65b58c/doc/languages-frameworks/php.section.md)               | Integration guide                | Building one fixed-output Composer repository and reusable offline vendor closure per dependency set     |
| 2026-08-13 | [nix-github-actions README](https://github.com/nix-community/nix-github-actions/blob/f4158fa080ef4503c8f4c820967d946c2af31ec9/README.md)                             | Integration guide                | Generating independently named GitHub Actions matrix entries from ordinary flake checks and packages     |
| 2026-08-11 | [PHPUnit package metadata](https://packagist.org/packages/phpunit/phpunit)                                                                                           | User-facing behavioral reference | Resolving PHPUnit 10.5 on PHP 8.1 and PHPUnit 11.5 on later runtimes                                     |
| 2026-08-11 | [ParaTest package metadata](https://packagist.org/packages/brianium/paratest)                                                                                        | User-facing behavioral reference | Resolving ParaTest 7.3 on PHP 8.1 without weakening the current PHP 8.2 toolchain                        |
| 2026-08-11 | [Infection package metadata](https://packagist.org/packages/infection/infection)                                                                                     | User-facing behavioral reference | Resolving Infection 0.28 on PHP 8.1 while retaining Infection 0.32 on PHP 8.2                            |
| 2026-08-11 | [`sebastian/diff` package metadata](https://packagist.org/packages/sebastian/diff)                                                                                   | User-facing behavioral reference | Selecting compatible unified-diff releases for PHP 8.1 and later                                         |
| 2026-08-12 | [PHP-CS-Fixer `fix` command help](https://cs.symfony.com/doc/usage.html)                                                                                             | Integration guide                | Public CLI options, configuration discovery, process status, dry-run, cache, and sequential behavior     |

### Cargo and rustdoc

| Date       | Document                                                                                                             | Classification                   | Design use                                                                                                                                         |
| ---------- | -------------------------------------------------------------------------------------------------------------------- | -------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| 2026-08-04 | [rustdoc documentation tests](https://doc.rust-lang.org/stable/rustdoc/write-documentation/documentation-tests.html) | User-facing behavioral reference | Observable concepts only: hidden support lines, ignored examples, non-running examples, expected runtime failure, and expected compilation failure |
| 2026-08-04 | [`cargo test`](https://doc.rust-lang.org/cargo/commands/cargo-test.html)                                             | User-facing behavioral reference | User-visible doctest invocation and working-directory behavior                                                                                     |
| 2026-08-04 | [Cargo targets](https://doc.rust-lang.org/cargo/reference/cargo-targets.html)                                        | User-facing behavioral reference | User-visible documentation-test target behavior and terminology                                                                                    |

## Rust and rustdoc influence

The Rust review influenced only the roadmap and vocabulary used to discuss deferred behavior:

- hidden support lines motivate preserving separate authored, semantic, and possible future display views;
- executable examples in documentation comments provided user-facing behavioral precedent for Akashi's independently
  designed inline PHPDoc extraction; externally included documentation informed the independently designed separation
  now represented by canonical code origins and PHPDoc presentation locations;
- ignored examples informed the authored runtime-skip directive while broader ignore policies remain deferred;
- non-running examples motivate a future parse-or-analyze-only mode;
- expected runtime failure motivated the roadmap entry now implemented as a narrow throwable-type, message-substring,
  and integer-code expectation in both execution backends; its PHP-oriented syntax and PHPUnit-compatible subtype
  matching were independently designed;
- expected compilation failure motivates a future exact PHPStan-diagnostic expectation; and
- Cargo's documented working-directory behavior reinforces that Akashi must make its execution directory explicit.

Akashi does not adopt the names `should_panic`, `no_run`, or `compile_fail` as APIs. No Rust-specific algorithm, API
shape, source transformation, or internal architecture was copied or adapted.

Akashi's PHPDoc implementation was independently derived from its existing CommonMark example model and PHP ecosystem
conventions. It uses PHP's public tokenizer contract to locate documentation comments, parses only conventional
multiline comment interiors, and preserves the original PHP file and line locations. No prohibited implementation
material or competing PHP doctest documentation was consulted for this work.

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
- CLI routing, generated help, and completion follow Symfony Console's public integration API while Akashi independently
  preserves its exact-command, option-cardinality, output-stream, diagnostic-visibility, and process-status contracts;
- subprocess isolation follows PHP's process model and Symfony Process's public integration API;
- PHPUnit, PHPStan, Composer binary, and autoloader adapters follow their respective official integration contracts; and
- PHPStan's diagnostic identifiers and identifier-oriented inline ignore comments are observed public behavior; the
  implemented post-MVP `@akashi-phpstan-error` prefix, grammar, statement association, and expectation semantics are
  Akashi decisions, not syntax from a competing doctest implementation.

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

On 2026-08-07, while confirming the public `Assert::markTestSkipped()` integration point, the installed PHPUnit source
was opened around that method and its referenced skipped-test exception. This exposed the method's trivial throw and the
internal exception class and marker interface. Those internal types were disregarded and are not used by Akashi's
implementation or public contracts; runtime skip delegates only through PHPUnit's public `Assert::markTestSkipped()`
method, consistent with the official PHPUnit writing-tests guide already recorded above.

No competing PHP doctest documentation or prohibited implementation material was accidentally exposed.

## Allowed dependency public-API inspection

On 2026-08-05, PHP runtime reflection was used to inspect the public signatures and PHPDoc of `ParserFactory`, `Parser`,
`Token`, `NameResolver`, `NodeFinder`, and `Error` from the installed PHP-Parser 5.8 package. A small runtime parse also
observed documented public node attributes, source positions, and resolved names. No dependency source file, internal
test, implementation algorithm, or architecture material was opened or copied; the transform and source-edit design
remains independently derived from Akashi's recorded requirements.

On 2026-08-11, the public declarations and narrow constructor/token contracts of PHP-Parser 4.19.5's `ParserFactory`,
`NodeTraverser`, `NameResolver`, and `Lexer` were inspected to identify the supported intersection with PHP-Parser 5. No
internal tests, parsing algorithm, or source architecture was copied. The resulting compatibility seam uses PHP's native
`PhpToken`, the documented visitor-registration API, and the already-recorded AST attributes rather than either major
version's private implementation.

On 2026-08-05, while implementing metadata-comment association, the installed League CommonMark 2.8.3 files
`src/Extension/CommonMark/Node/Block/HtmlBlock.php` and `src/Node/Node.php` were opened to confirm the public
`TYPE_2_COMMENT` constant and the public `previous()`, `next()`, and `parent()` node methods. This was a narrow
inspection of public API declarations in an allowed general-purpose dependency. No parser implementation, internal test,
source-level algorithm, or architecture was examined or copied. Akashi's immediate sibling-association rule remains the
independent design recorded in `ARCHITECTURE.md`; the inspection only confirmed that League CommonMark's public node API
could express it.

Repository note (2026-08-09): the historical plan named `ARCHITECTURE.md` in the contemporaneous record above is now
preserved in the source repository as `docs/development/initial-architecture-plan.md`. Composer distributions exclude
that internal development directory; the implemented public design is documented in
`docs/pages/project/architecture.md`.

On 2026-08-05, while implementing the separate-process adapter, narrow sections of the installed Symfony Process 7.4
`Process.php`, `ProcessTimedOutException.php`, `ProcessSignaledException.php`, and `ProcessStartFailedException.php` and
`RuntimeException.php` files were opened to confirm documented public method contracts for process execution, separated
output, exit status, signals, timeouts, and startup failures. The inspection exposed the relevant public declarations,
their API comments, small accessor and forwarding method bodies, the beginning of `Process::start()`, and the exception
constructors. No process-management algorithm, internal test, or source architecture was copied. Akashi's
temporary-file, result, failure, cleanup, and source-mapping designs remain independently derived from its recorded
requirements and use only the public behavior already represented by Symfony's official integration guide.

On 2026-08-14, while evaluating selected general-purpose dependencies, the installed Symfony Console 7.4 `Application`,
`Command`, `ArgvInput`, input-definition, output, and text-descriptor source was inspected to confirm public extension
seams and the exact behavior of repeated options, raw tokens, help generation, verbosity, command abbreviations, and
exception routing. The installed Symfony Filesystem `dumpFile()` implementation was also inspected to compare its
symlink and replacement behavior with Akashi's documented stale-byte, flush, permission, and atomic-write guarantees.
These are explicitly allowed general-purpose dependencies. Akashi uses Console's public component contract, retains its
independently designed CLI invariants, and rejected Filesystem as a replacement for its stronger writer.

On 2026-08-06, while implementing Akashi's analyzer-independent diagnostic matcher, the official PHPStan
error-identifier pages for `property.nonObject`, `method.nonObject`, `argument.type`, `parameterByRef.type`,
`return.type`, and `missingType.iterableValue` were consulted as user-facing behavioral references after PHPStan
reported those identifiers against an intermediate implementation. They were used only to understand the public
diagnostics and correct the underlying native and PHPDoc types. No PHPStan implementation code, internal tests, or
source architecture was examined.

On 2026-08-06, while implementing the `RuleTestCase` adapter, the installed PHPStan 2.2.5 PHAR's
`src/Testing/RuleTestCase.php` was opened after runtime reflection identified the public class location. The inspection
showed the public `analyse()` and `gatherAnalyserErrors()` declarations and their method bodies, including analyzer
construction, error sorting, delayed-error handling, and result finalization. Akashi uses only the documented public
`gatherAnalyserErrors()` contract; none of PHPStan's analyzer construction, collection, sorting, or finalization
implementation was copied or adapted. The adapter's selection, temporary-file lifecycle, declaration preflight, source
mapping, matching, reporting, and cleanup remain independently derived from the recorded Yumemi requirements. Runtime
reflection also inspected the public signatures of `PHPStan\Analyser\Error` construction and decoding methods while
looking for a compatibility-promised way to produce a malformed diagnostic in a regression test. No such test fixture
was retained, and no `Error` implementation body was opened.

On 2026-08-06, while configuring `phpstan-strict-rules`, its installed `rules.neon` and public README were reviewed to
identify the supported `strictRules.disallowedShortTernary` switch. A symbol search also displayed the source filename
and diagnostic identifier for `DisallowedShortTernaryRule`; its implementation body was not opened. This changed only
Akashi's own development-style policy and did not influence its runtime architecture or public API.

On 2026-08-09, Infection's installed `infection/include-interceptor` 0.4.2 `src/IncludeInterceptor.php` was inspected to
diagnose why mutations applied in the PHPUnit host process do not propagate into subprocess fixtures. This was a
deliberate inspection of general-purpose mutation-testing infrastructure, not a documentation-test implementation. It
influenced only the classification of two surviving mutants as instrumentation artifacts; no code, algorithm, or public
API was copied or adapted into Akashi.

On 2026-08-09, Composer 2.10.2's installed `src/Composer/Package/Archiver/PharArchiver.php` was inspected narrowly to
confirm that `composer archive` builds tar files from working-tree filesystem metadata through `PharData`. This informed
only the decision not to assert a POSIX executable permission bit on Windows; Akashi continues to enforce that archive
invariant on platforms that represent it. No Composer algorithm or architecture was copied or adapted.

On 2026-08-11, the installed `sebastian/diff` 6.0.2 public `Differ` and `UnifiedDiffOutputBuilder` declarations were
opened to confirm their constructor and diff-output contracts. This was a narrow public-API inspection of an allowed
general-purpose dependency. Akashi invokes that library directly and does not copy or adapt its diff algorithm, output
builder implementation, internal tests, or architecture. Package metadata for 5.1.1 established the PHP 8.1-compatible
constraint; the isolated PHP 8.1 consumer gate verifies the shared public API in practice.

On 2026-08-12, the header of Composer's generated `vendor/bin/php-cs-fixer` proxy was inspected to confirm that the
project-installed proxy is PHP source and receives Composer's documented binary-directory and autoloader globals. No
PHP-CS-Fixer implementation, internal test, or architecture material was opened. The inspection confirmed only the
already-selected `PHP_BINARY` process boundary; formatter behavior and options remain based on the public command help
recorded above.

On 2026-08-13, the Composer repository/install builders and hooks in the pinned nixpkgs revision were inspected narrowly
after the public PHP packaging manual identified them as the supported lower-level API. The inspection confirmed the
`mkComposerRepository` inputs, the local-repository plugin required by `composerInstallHook`, offline lock remapping,
and the fixed-output mismatch format. The pinned nix-github-actions README, public matrix function, and example workflow
were inspected to confirm the current `mkGithubMatrix` input and matrix-entry contract. These are general-purpose build
and CI integrations; Akashi adapted their documented public contracts without copying a documentation-test design. No
prohibited PHP doctest material, rustdoc implementation, compiler implementation, or another agent's analysis of those
systems was examined.

## Current dependency status

Akashi requires PHP 8.1 or later and the following runtime dependencies:

- `arokettu/random-polyfill` 1.0.6 or a compatible source-bearing 1.x release below the empty 1.99 compatibility
  package, to preserve the PHP 8.2 Random extension contract on PHP 8.1 while deferring to native implementations on
  later runtimes;
- `composer-runtime-api` 2.2 or later, for locating the project autoloader through Composer's generated binary proxy;
- `league/commonmark` 2.8.3 or later within the 2.x series, for standards-conforming Markdown parsing;
- `nikic/php-parser` 4.19.5 or later within the 4.x series, or 5.8 or later within the 5.x series, for PHP parsing and
  later source transformation;
- `sebastian/diff` 5.1.1 or later within the 5.x series, or 6.0.2 or later within the 6.x series, for deterministic
  source-labelled unified diffs; and
- `symfony/process` 6.4 or 7.4, for isolated example execution.

These are general-purpose integration libraries rather than documentation-test frameworks. Their official public
documentation and package metadata are recorded above. Apart from the narrow PHP-Parser, League CommonMark, Symfony
Process, `sebastian/diff`, PHP 8.2 polyfill, PHPStan `RuleTestCase`, Infection include-interceptor, and Composer
archiver inspections recorded above, no dependency source code or internal tests were consulted. PHPUnit, PHPStan and
its extensions, PHP-CS-Fixer, and Infection remain development-only dependencies of Akashi itself. PHPUnit and PHPStan
are suggested optional integration dependencies for consumers; PHP-CS-Fixer is suggested as an optional executable for
the formatting check. The normal development stack uses PHPStan 2.x and PHP-Parser 5; the isolated compatibility gate
uses PHPStan 1.12 with an explicit PHP-Parser 4.19.5 pin.
