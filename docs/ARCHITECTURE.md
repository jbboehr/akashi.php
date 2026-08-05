# Akashi architecture and implementation plan

Status: proposed for review before the next implementation chunk.

This document defines the intended MVP architecture for Akashi. It is grounded in the
[Yumemi compatibility inventory](MIGRATING_YUMEMI.md), the [implementation handoff](IMPLEMENTATION_HANDOFF.md), and the
official public references recorded in the [clean-room record](CLEAN_ROOM.md). It is not derived from a competing PHP
doctest project or from Cargo, rustdoc, or compiler implementation material.

## Goals and decision order

Akashi is a reusable PHP library whose first compatibility targets are Yumemi and Yumemi Apocrypha. It is not a
Yumemi-specific test helper and it is not initially a standalone documentation generator or full test runner.

When design goals conflict, use this order:

1. preserve type and state invariants;
2. prevent avoidable corruption of the test process and its environment;
3. produce correct, deterministic results with useful source locations;
4. keep integration behavior explicit and statically analyzable;
5. minimize the amount of project-specific test code;
6. keep common authoring and debugging workflows pleasant;
7. leave narrow, documented seams for already-identified deferred work.

Type safety must not become ceremony for consumers. Rich value objects belong inside the model and at dangerous
boundaries; convenience factories should accept ordinary PHP strings where the conversion is unambiguous and validate
them immediately.

## Trust and safety boundary

Documentation examples are trusted project code. Neither execution backend is a security sandbox.

The default in-process backend protects the test runner from the hazards it can reasonably control:

- it parses and validates source before execution;
- it isolates declarations in an execution-specific namespace;
- it evaluates inside a closure so ordinary top-level variables do not enter `$GLOBALS`;
- it captures output and restores output-buffer depth;
- it restores guarded process state such as the working directory and `error_reporting()`;
- it catches `Throwable` and reports the original Markdown location; and
- it rejects detected constructs that can terminate or irreversibly alter the hosting test process.

It cannot protect against arbitrary global mutation, native-extension crashes, resource exhaustion, dynamically invoked
side effects, or fatal behavior that PHP cannot turn into a `Throwable`. Examples requiring process-level behavior must
opt into separate-process execution. That backend protects PHPUnit from `exit()`, fatal errors, and declaration or
variable leakage, but it still inherits the user's operating-system permissions, filesystem access, environment, and
network access.

> **Review note:** Confirm that this trusted-code boundary is acceptable. A true untrusted-code sandbox would be a
> separate project involving operating-system isolation, capability restrictions, and resource controls.

## System shape

The same immutable `Example` is reused without reparsing its Markdown document:

```text
Document manifest -> document loader -> CommonMark parser -> ExampleCorpus
                                                        |          |
                                                        |          +-> marked selector -> extract CLI
                                                        |
                                                        +-> Example -> transform pipeline -> PreparedExample
                                                                         |              |
                                                                         |              +-> subprocess executor
                                                                         +-> in-process executor
                                                                                         |
                                                                                         +-> PHPUnit assertion adapter

Example -> relevance predicate -> expectation parser -> PHPStan adapter -> diagnostic matcher -> PHPUnit assertion
```

The layers are:

1. **Sources** discover documents and extract examples.
2. **Models** preserve identity, authored bytes, semantic code, directives, and locations.
3. **Transforms** turn an example into backend-specific prepared source with a source map.
4. **Executors** execute only prepared source and return explicit result variants.
5. **Verifiers** compare an execution or analysis result with a contract.
6. **Integrations** translate verification results into PHPUnit failures or CLI behavior.

There is no service container, mutable global registry, or general plugin registry in the MVP.

### Public API boundary

The supported surface consists of the immutable source/model types, marked selector, runtime configuration, executor and
result contracts, verifier contracts, and documented integration facades. Third-party parser nodes, Symfony Process
objects, PHPUnit internals, and PHPStan diagnostics must not leak through core public signatures.

Low-level transforms, source-map machinery, state guards, and temporary-artifact helpers begin as `@internal`. Promote
one only when a concrete consumer use case needs direct composition. Public exceptions share a small Akashi domain base
but retain specific subclasses for configuration, discovery, parsing, selection, unsupported source, execution
infrastructure, and cleanup failures, so consumers may catch narrowly without parsing messages.

Before the first stable release, record the supported public classes and their invariants. Thereafter, apply semantic
versioning to those contracts. Debug metadata and generated source are inspectable through an explicit diagnostic API,
not public mutable properties and not routine failure output.

## Dependency plan

### Runtime dependencies

The proposed runtime dependencies are:

| Package                | Proposed constraint | Purpose                                                                        |
| ---------------------- | ------------------- | ------------------------------------------------------------------------------ |
| `league/commonmark`    | `^2.8.3`            | Standards-compliant CommonMark block parsing and fenced-code AST nodes         |
| `nikic/php-parser`     | `^5.8`              | PHP parsing, locations, AST validation, name resolution, and source transforms |
| `symfony/process`      | `^7.4`              | Portable argument escaping, output capture, exit status, and process timeout   |
| `composer-runtime-api` | `^2.2`              | Reliable Composer autoloader discovery from the installed CLI proxy            |

The exact constraints must be validated through Composer before changing `composer.json`. They all support PHP 8.2 at
the reviewed versions. Use caret constraints so compatible fixes remain installable.

`league/commonmark` is intentionally preferred over a new Markdown implementation. Akashi needs CommonMark container,
fence, info-string, and line-location behavior, not merely a regular expression that passes the current corpus. Akashi
will use its public AST API and will not render HTML.

The parser integration must begin with a focused spike proving that public fenced-code nodes provide sufficient start
and end line information. A small raw-line indexer may supplement the AST to recover authored bytes, but it must not
become an independent competing Markdown parser.

> **Review note:** `league/commonmark` brings `ext-mbstring` and several small transitive packages. The recommendation
> is to accept that cost for standards correctness. If minimizing dependencies is more important, this is the principal
> decision to revisit before parser implementation.

`nikic/php-parser` is justified by requirements that are unsafe to implement with token replacement: distinguishing
native `assert()` calls, validating namespace-sensitive constructs, preserving imports, mapping line locations, and
rewriting names before injecting an isolation namespace.

`symfony/process` is preferred over a local `proc_open()` wrapper because its documented array-command API avoids shell
escaping, captures stdout and stderr separately, exposes exit status, and applies a finite timeout portably.

### Optional integrations

PHPUnit and PHPStan remain development dependencies of Akashi and optional dependencies for consumers:

- core discovery, selection, transformation primitives, and the extraction CLI must load without PHPUnit or PHPStan;
- classes under `Integration\PhpUnit` are loaded only by projects using PHPUnit;
- classes under `Integration\PHPStan` are loaded only by projects using PHPStan;
- `composer.json` should `suggest` supported PHPUnit and PHPStan versions and explain the related features; and
- Akashi's development matrix validates the optional integrations against the supported versions.

Splitting integrations into separate Composer packages is deferred unless real dependency conflicts make it necessary.

### Configuration and bootstrap

Configuration is immutable PHP, not an array file or mini-language. Keep three focused configurations instead of one
object containing options for every layer:

- `MarkdownSource` owns document roots, includes, exclusions, language selection, and marker parsing;
- `RuntimeConfiguration` owns the canonical project root, optional bootstrap, and default execution mode; and
- `PhpStanExampleConfiguration` owns the root, relevance predicate, and analyzer-specific context.

Factories accept project-relative scalar paths for convenience and immediately resolve them against a validated
canonical root. Internally, every filesystem boundary receives a path value object. No API depends on the caller's
ambient working directory.

The PHPUnit process has normally loaded the consumer's Composer autoloader before Akashi runs. The in-process executor
validates the configured bootstrap and loads it with `require_once` only if the consumer explicitly supplies one. The
same absolute bootstrap path is passed to subprocess PHP through `auto_prepend_file`. Extraction does not load the
bootstrap. This keeps the common Composer/PHPUnit case effortless while making nonstandard bootstraps explicit.

## Domain model

### Existing model evolution

`Document` and `Example` already establish readonly, constructor-validated state. Before they become a stable public
API, refine rather than discard them.

The target model is:

```text
Document
  DocumentPath path
  string contents
  LineIndex lines

Example
  ExampleId id
  string label
  Document document
  positive-int ordinal
  SourceLocation location
  Language language
  ExampleCode code
  FenceMetadata fence
  DirectiveSet directives
  MarkerId|null explicitMarkerId
```

The value types have narrow purposes:

- `DocumentPath` stores a slash-normalized, project-relative display path and rejects NUL bytes and traversal outside
  the configured root.
- `ExampleId` validates a deterministic file-safe identity.
- `MarkerId` validates lowercase kebab-case author identities.
- `MarkerName` validates configurable names such as `yumemi-example`.
- `SourceLocation` stores the code span and fence span without ambiguous integer pairs.
- `Language` is an open validated value object, not an enum, because future sources can expose arbitrary languages.
- `DirectiveSet` is a typed immutable set of supported directives; callers do not receive string-keyed arrays.

Use PHP 8.2 readonly classes for immutable state and backed enums for closed sets such as `ExecutionMode`,
`FailurePhase`, and CLI `ExitCode`. Do not wrap display-only strings such as `label` unless a concrete invariant
appears.

Convenience factories accept scalars and construct the value objects internally. Consumers using normal Markdown sources
should almost never construct an `Example` themselves.

### Authored and semantic code

`ExampleCode` must distinguish:

- **authored code**: the exact bytes between the opening and closing fences, including original line endings; and
- **semantic code**: the code content defined by CommonMark after container prefixes and fence indentation are removed.

For the top-level Yumemi fences these views are normally identical. Keeping both prevents a future nested list or block
quote example from forcing a choice between byte-exact extraction and correct execution. Transforms always receive the
semantic view; marked extraction uses the authored view and applies its documented final-newline contract.

Hidden supporting lines are deferred. `ExampleCode` may later gain a display view, but the MVP must not implement or
silently recognize Rust's `# ` convention.

> **Review note:** Confirm the authored-versus-semantic distinction, especially for fenced blocks inside lists or block
> quotes. This is a small model cost that avoids a significant future compatibility trap.

### Source locations

Avoid ambiguous `startLine` and `endLine` meanings. `SourceLocation` should expose:

- opening fence line;
- first code-content line;
- last code-content line, if any;
- closing fence line, if present; and
- marker or directive line locations when applicable.

User-facing failures point to the first relevant code line and add AST-relative offsets for transformed statements.
Compatibility accessors for the current integer properties may remain until the model refactor is complete.

### Corpus invariants

`ExampleCorpus` is an immutable, iterable collection that validates on construction:

- generated example IDs are unique;
- explicit marker IDs are unique within the marker namespace used for selection;
- examples are in deterministic document-path and block-ordinal order; and
- the corpus is nonempty.

It provides typed selection and filtering methods without exposing mutable arrays. Predicates use
`Closure(Example): bool` PHPDoc signatures checked at PHPStan's maximum project level. Source loaders report an empty
requested PHP corpus as a source error rather than constructing an invalid collection.

## Source and discovery design

`MarkdownSource` is the main programmatic entry point. A convenience API should resemble:

```php
$source = MarkdownSource::forProject(__DIR__ . '/..')
    ->includeFile('README.md')
    ->includeDirectory('docs/pages')
    ->exclude('docs/pages/SUMMARY.md')
    ->withMarkerName('yumemi-example');

$corpus = $source->load();
```

Each fluent method returns a new instance. Scalar paths are validated immediately; existence and readability are
validated when `load()` performs I/O. The immutable final configuration consists of a canonical project root and a
nonempty ordered list of typed include and exclude rules.

Discovery rules are:

- explicit files and recursive directories are supported;
- only files ending in `.md`, compared case-sensitively, are selected in the MVP;
- symlinked directories are not followed;
- every resolved file must remain within the project root by default;
- duplicate physical files reached through multiple includes are rejected rather than silently run twice;
- paths are normalized to `/` and sorted with bytewise lexical comparison; and
- read, missing-root, duplicate, and empty-corpus failures have separate exception types.

> **Review note:** The recommended default rejects documentation outside the configured project root and does not follow
> directory symlinks. This strengthens stable identity and traversal safety but should be reviewed if monorepo users
> need shared documentation trees in the MVP.

The generated identity strategy remains compatible with Yumemi:

```text
example-{first 12 hex characters of sha1(project-relative path)}-{two-digit ordinal}
```

The ordinal is per Markdown document and counts selected PHP fences as the existing harness does. An explicit marker ID
is separate and never replaces the generated ID. Moving a document or inserting an earlier block changes an implicit
identity; authors who need a durable external identity should use a marker.

## CommonMark extraction and associated metadata

Configure League CommonMark with the core extension only. Walk the parsed AST in source order and select fenced-code
nodes whose first info-string token is exactly `php`, case-insensitively. Preserve the complete info string in
`FenceMetadata` even though the MVP does not assign semantics to additional tokens.

The implementation must cover the CommonMark fenced-block rules relevant to correctness:

- backtick and tilde fences;
- opening fences of three or more matching characters;
- closing fences at least as long as the opener;
- literal fence-like content inside a longer fence;
- up to three spaces of fence indentation;
- blocks within CommonMark containers;
- info-string restrictions; and
- an unclosed fence extending to the end of its containing block or document.

Line endings and raw byte offsets come from `Document`'s `LineIndex`; semantic block content comes from the parser node.
Tests must include selected examples adapted from the official CommonMark specification in addition to the handoff's
synthetic cases.

### Marker association

The configurable marker syntax remains:

```html
<!-- yumemi-example: selected-example -->
```

A marker associates only with the next PHP fenced block when the intervening source contains whitespace and other
recognized Akashi metadata comments, but no prose or unrelated block. Marker IDs are validated before document I/O.

Selection is a separate `MarkedExampleSelector`; parsing does not need to know which marker a caller will request.
Duplicate, missing, invalid, orphaned, and non-PHP marker failures are distinct domain exceptions with document lines.

### Execution directive

The only MVP execution directive is:

```html
<!-- akashi: separate-process -->
```

It follows the same immediate-association rule as a marker. Multiple adjacent recognized metadata comments may occur in
either order. Duplicate directives and unknown `akashi:` directive names are errors, which catches authoring typos.

The directive maps to `ExecutionMode::SeparateProcess`; absence maps to `ExecutionMode::InProcess`. Programmatic
configuration may override the mode for a selected example or corpus without modifying the immutable original example.
The public name follows PHPUnit's familiar “run in separate process” terminology; “subprocess” is reserved for internal
implementation mechanics.

Consumers that do not want Markdown directives may select the mode for the whole corpus:

```php
$runtime = RuntimeConfiguration::forProject(__DIR__ . '/..')
    ->withDefaultExecutionMode(ExecutionMode::SeparateProcess);
```

Do not use fence info-string tokens for Akashi directives in the MVP. HTML comments preserve readable language tags and
avoid inventing an info-string grammar before it is needed.

## Transformation model

Executors never accept raw `Example` source. A `TransformPipeline` produces a `PreparedExample` containing:

- the original `Example`;
- a backend-specific `PreparedCode` value;
- a `SourceMap` from generated lines and nodes to Markdown lines;
- the selected `ExecutionMode`;
- an execution-scope identifier; and
- debug metadata that is retained but hidden from normal output.

This type boundary prevents raw and transformed strings from being interchanged accidentally.

Transforms are explicit objects with a narrow `transform()` interface. The MVP has a fixed composition root rather than
a public transform registry. Custom-transform registration remains deferred.

### PHP parsing

For either backend:

1. add a synthetic `<?php` tag only when the example omitted one;
2. parse for the actual host PHP version;
3. require a complete AST with no recovered parse errors;
4. retain tokens and node locations for source mapping; and
5. report syntax failures against the Markdown line, never only the generated source.

Do not use `eval()` as the syntax checker. The transformed source is parsed again when needed before execution, so a bug
in the transformer fails as a transform error rather than becoming an opaque runtime failure.

The in-process backend initially supports one PHP segment, with an optional opening tag and no closing tag or inline
HTML. A closing/reopening tag or inline-HTML node receives a precise unsupported-example error with a separate-process
hint. The separate-process backend may execute those forms because it runs a normal PHP file.

### In-process safety validation

Before namespace injection, `InProcessSafetyValidator` rejects constructs that cannot be made acceptably safe in the
hosting PHPUnit process. The initial hard failures include:

- `exit` and `die`;
- `__halt_compiler()`;
- unsupported explicit namespace declarations;
- writes through `$GLOBALS` or superglobals;
- process-persistent handlers or registrations detected as resolved global function calls;
- global constant creation through `define()`; and
- output-buffer operations capable of removing a buffer owned by Akashi.

The exact resolved-global function list should be small and evidence-driven. Initial candidates include error and
exception handler mutation, shutdown registration, autoloader mutation, environment mutation, locale/timezone mutation,
and INI mutation. Each rejection names the construct and recommends `<!-- akashi: separate-process -->`.

Do not automatically switch a failing example to separate-process mode. An explicit directive keeps behavior reviewable
and prevents an environment-dependent source change from silently changing the execution model.

Dynamic calls and native extensions make exhaustive detection impossible, which is why the trusted-code boundary still
applies.

### Name resolution and namespace isolation

For examples without an authored namespace, the transform injects an Akashi execution namespace while preserving how
names would have resolved from the original global context:

- authored imports are retained;
- fully qualified names remain fully qualified;
- imported names are resolved before namespace injection;
- external class names that were global are rewritten to remain global;
- calls to functions declared by the example remain local to its generated namespace;
- ordinary global functions and constants preserve PHP's normal global behavior; and
- `namespace\` references and `__NAMESPACE__` receive explicit compatibility handling.

String-based reflection such as `class_exists('ExampleClass')` cannot always be repaired soundly. The validator should
reject ambiguous cases involving declarations and string class/function names, with a separate-process recommendation.

An execution namespace consists of a deterministic example-derived prefix plus a collision-resistant executor scope
suffix. The public example ID stays deterministic; the suffix makes executing the same example twice in one PHP process
safe. It is retained in debug metadata for reproducibility but omitted from routine failures.

An internal `ExecutionScopeFactory` receives PHP 8.2's native `Random\Randomizer`. The production composition root uses
`Random\Engine\Secure`; tests inject a seeded engine such as `Random\Engine\Xoshiro256StarStar` so transformed-source
fixtures are repeatable. Randomness is an implementation dependency, not consumer configuration. Do not add a Symfony
polyfill for APIs already guaranteed by Akashi's PHP 8.2 minimum.

> **Review note:** Namespace rewriting is the highest-risk transform. Review the choice to preserve global name
> resolution through AST rewriting and to reject explicit authored namespaces in the MVP. Supporting arbitrary authored
> namespace blocks in-process can be added after this behavior is proven against the migration corpus.

### Native assertion transform

The PHPUnit integration supplies a transform that rewrites only calls resolved as PHP's native `assert()` construct. It
calls a small `Integration\PhpUnit\NativeAssertion` bridge that:

- evaluates the condition exactly once;
- applies PHP truthiness as native `assert()` does;
- calls `PHPUnit\Framework\Assert::assertTrue()` so PHPUnit records the assertion;
- preserves an authored string description;
- uses the exact original expression text plus Markdown location as the fallback description; and
- preserves an authored `Throwable` description by throwing it when the condition fails.

Named arguments and the valid one- and two-argument forms must be tested. Invalid forms fail during transformation.
Because the transformed source contains no native `assert()`, behavior does not depend on `zend.assertions`.

## In-process execution

`InProcessExecutor` accepts only a prepared in-process example. It invokes `eval()` inside a private static closure; the
evaluated source excludes opening and closing PHP tags as required by PHP. The closure contains no consumer variables,
and any unavoidable internal variable name is selected after checking the example AST for collisions.

Using `eval()` is constrained to trusted, parser-validated documentation source. It is not exposed as a general string
execution API.

Execution is wrapped by guards that record and restore:

- output-buffer depth;
- current working directory;
- `error_reporting()`; and
- other state only where PHP offers a reliable, reversible API and tests prove restoration.

The output guard owns one buffer. If an example leaves nested removable buffers open, the guard folds their contents
into the owned buffer in the same logical order and removes them. If the example removed a pre-existing or Akashi-owned
buffer, or created an unremovable buffer, cleanup fails explicitly. Cleanup runs in `finally` after success or failure.

The executor catches `Throwable`. It returns a result variant rather than throwing source failures directly:

- `ExecutionSucceeded` contains captured stdout, duration, and debug metadata;
- `ExecutionFailed` contains the phase, primary throwable or structured cause, captured output, and zero or more cleanup
  failures.

A cleanup failure can never produce success. If execution and cleanup both fail, preserve the execution failure as the
primary cause and report every cleanup failure as secondary context.

`return` at example top level terminates only the evaluated code and is supported. Tests must prove this explicitly.
Unsupported control-flow constructs fail during validation with a source location.

## Separate-process execution

The internal `SubprocessExecutor` writes the semantic source to a private temporary file, adding `<?php` only when
absent. Temporary files are created with PHP's atomic temporary-file primitive, must have `0600` permissions where
supported, and must be verified to reside in the requested absolute temporary directory rather than an undocumented
fallback location.

The process command is an argument list, never a shell string. It uses:

- the current `PHP_BINARY`;
- startup `-d` options that enable native assertions and assertion exceptions;
- the configured bootstrap through `auto_prepend_file` when present;
- the configured project root as working directory; and
- Symfony Process's captured stdout, stderr, and exit status.

Timeout semantics and user configuration remain deferred as required by the handoff. During the executor spike, decide
explicitly whether to disable Symfony Process's documented default timeout or retain it solely as an internal emergency
ceiling. If retained, it is a fixed implementation safety limit, produces a structured infrastructure failure, and is
not an authored expected-timeout feature. Alternate PHP binaries, INI values, environment variables, and custom timeouts
remain deferred.

Exit status zero is success, even when reached through an authored `exit(0)`; a nonzero status, signal, timeout, or
startup failure is an `ExecutionFailed` result. Stdout and stderr are always captured separately. Stderr alone does not
fail an otherwise successful example until an expected-output contract exists.

Every temporary artifact is removed in `finally`. Cleanup failures are reported as with in-process execution.

> **Review note:** Review two separate-process policies: whether the MVP retains Symfony Process's 60-second emergency
> ceiling and whether `exit(0)` is success. Detecting early successful exit would require a child-control protocol and
> may not be worth the extra machinery yet.

## Result and failure model

Avoid a result object with nullable fields whose validity depends on a status string. Use a small discriminated object
family:

```text
ExecutionResult
  ExecutionSucceeded
  ExecutionFailed

VerificationResult
  VerificationPassed
  VerificationFailed
```

Closed enums describe `ExecutionMode`, `FailurePhase`, and `ExitCode`. Failure details are immutable value objects, not
associative arrays. Reports format these types at the integration edge.

Configuration and extraction errors are exceptions because no execution result exists. Authored-code failures become
results because verifiers and reporters need to inspect them. Programmer invariant violations use
`LogicException`-derived types and are never converted into an authored-code failure.

All messages include, where known:

- normalized Markdown path;
- generated example ID and explicit marker ID;
- code line and block ordinal;
- backend and failure phase;
- original throwable class/message or separate-process exit data; and
- an actionable hint for unsupported in-process constructs.

## PHPUnit integration

PHPUnit 11.5 data providers are public static methods and execute before test setup. Akashi therefore must not require
instance state or services in a provider. Providers should yield named data sets containing only immutable `Example`
objects.

A consumer test should remain explicit and small:

```php
final class DocumentationExamplesTest extends TestCase
{
    public static function examples(): iterable
    {
        yield from DocumentationExamples::runtimeDataSets();
    }

    #[DataProvider('examples')]
    public function testDocumentationExample(Example $example): void
    {
        PhpUnitRuntime::assertExample($example, DocumentationExamples::runtimeConfiguration());
    }
}
```

`PhpUnitRuntime` is a stateless convenience facade over explicit pipeline, executor, and result-asserter objects. It
does not store global configuration. Advanced consumers can compose those objects directly.

Named data-set keys use the human label and are checked for uniqueness. The adapter translates `ExecutionFailed` into a
PHPUnit assertion failure while retaining the original cause. After any successful execution it performs one explicit
“example completed” assertion, so examples without authored assertions are not risky tests. Rewritten native assertions
continue to count independently.

Do not build a PHPUnit extension or event subscriber in the MVP. A normal data provider and assertion facade are easier
to understand, filter, debug, and statically analyze.

## PHPStan integration

The core model has no PHPStan types. `Integration\PHPStan` owns:

- `DiagnosticExpectation` with expected text and Markdown line;
- `AnalyzerDiagnostic` with identifier, message, optional tip, and analyzer line;
- `ExpectationParser` for ordered nonempty `//!` comments;
- `DiagnosticMatcher` for comparison and reporting;
- `PhpStanExampleConfiguration` for root, bootstrap/config context, and relevance predicate; and
- a `VerifiesPhpStanExamples` trait that can call the public `RuleTestCase` analyzer API from a consumer subclass.

The consumer remains responsible for `getRule()` and `getAdditionalConfigFiles()`. PHPStan verification uses one
corpus-level test because Yumemi's reflection contract requires all relevant example files to be declared exactly once
before any of them are analyzed. Its test remains explicit:

```php
final class DocumentationPhpStanExamplesTest extends RuleTestCase
{
    use VerifiesPhpStanExamples;

    public function testDocumentationExamples(): void
    {
        $this->assertPhpStanExamples(
            DocumentationExamples::corpus(),
            DocumentationExamples::phpStanConfiguration(),
        );
    }

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(CallToFunctionParametersRule::class);
    }
}
```

The trait filters the corpus with the configured relevance predicate and rejects an empty relevant corpus. In one
guarded lifecycle, it creates a private temporary directory and one securely named file per relevant example from the
untransformed semantic source. It then changes to the project root, requires every file exactly once so example-local
declarations are visible to reflection, and analyzes each file independently through `gatherAnalyserErrors([$file])`.
Each analyzer result is still matched against its own immutable `Example`; corpus-level setup does not couple diagnostic
contracts between examples. The trait always restores the working directory and removes every temporary artifact.

For the MVP, declaration loading reproduces the existing Yumemi behavior. Before requiring any file, Akashi validates
the whole relevant corpus for process-terminating constructs and declaration collisions so failure occurs before partial
loading. The require phase reuses the applicable output and working-directory guards from in-process execution and
surfaces any execution or cleanup failure against the responsible Markdown example. A future declaration-only reflection
prelude may improve isolation, but it is not required before migration evidence demonstrates the need.

Comparison rules are:

- actual diagnostic count must exactly equal expectation count;
- a relevant example with no expectations must have zero diagnostics;
- matching text is searched in the message plus optional tip;
- expectations remain in authored order; and
- each expectation matches the first still-unmatched compatible diagnostic.

The last rule is a deliberate strengthening of the legacy “each substring appears somewhere” check: it prevents two
expectations from being satisfied by the same diagnostic while another diagnostic remains unexplained. The current
Yumemi corpus must be tested before this becomes fixed public behavior.

> **Review note:** Confirm the proposed one-to-one PHPStan diagnostic matching. It is more sound and deterministic than
> the legacy matcher, but it intentionally strengthens the migration contract.

The relevance predicate is consumer-supplied. A convenience token predicate supports Yumemi's current tokens without
placing those tokens in generic core behavior.

### Preferred PHPStan expectation syntax after the MVP

The MVP retains Yumemi's compact syntax unchanged:

```php
//! expected diagnostic substring
offendingCall();
```

It is a compatibility syntax, not the preferred long-term authoring model. It is cryptic, matches only mutable prose,
and does not state which source construct should produce the diagnostic.

After the MVP API and migrations are recorded, consider a separately reviewed canonical syntax based on PHPStan's public
diagnostic identifiers:

```php
// @akashi-phpstan-error argument.type
offendingCall();
```

An optional text constraint can disambiguate diagnostics sharing an identifier:

```php
// @akashi-phpstan-error argument.type: expected message substring
offendingCall();
```

The grammar is `// @akashi-phpstan-error IDENTIFIER` followed by an optional colon and nonempty message substring.
Repeated immediately preceding directives express multiple expected diagnostics. Each directive associates with the next
PHP statement, and its diagnostic line must fall within that statement's source span. Matching remains exact-count,
ordered, and one-to-one; the identifier must match exactly and the optional text is searched in the message plus tip.
Malformed directives fail rather than becoming ordinary comments.

This syntax is valid inert PHP, searchable, and more stable than message-only matching. It belongs entirely to the
PHPStan integration: the core `Example` model remains analyzer-agnostic. `//!` continues to be accepted for Yumemi
compatibility even if the identifier form is later implemented. Do not implement the new grammar during the MVP merely
because its model seam is documented here.

## CLI design

The CLI has a small explicit router and `Command` interface, not an application framework. The MVP command is:

```console
vendor/bin/akashi extract --marker-name=yumemi-example FILE MARKER-ID
```

The root application also handles `--help` and `--version`. Composer's binary proxy autoloader variable is used with a
root-package fallback.

Use a backed `ExitCode` enum:

- `0`: success;
- `1`: extraction or document-domain failure;
- `2`: usage error; and
- `70`: unexpected internal software failure.

On successful extraction, stdout contains only PHP source. Help and version may use stdout when no extraction payload is
being produced. All failures use stderr. Messages are stable in meaning, while tests should assert structured exception
types and essential text rather than freezing every punctuation mark.

The extraction newline policy is a dedicated function tested against every Apocrypha marker. Migration requires
byte-equivalent fixtures; any proposed normalization beyond the legacy appended LF must be proven against those files
before it is adopted.

## Static quality rules

Production code follows these constraints:

- `declare(strict_types=1)` everywhere;
- PHP 8.2 syntax only;
- final classes by default and readonly classes for values;
- enums for closed state sets instead of boolean flags or status strings;
- native parameter and return types wherever PHP can express them;
- precise PHPStan generics, lists, nonempty strings, and array shapes only where native PHP lacks the type;
- no implicit `mixed`, and explicit `mixed` only at real PHP or third-party boundaries;
- no public APIs centered on unvalidated associative arrays;
- no nullable property whose meaning changes based on another property;
- exceptions or result variants must make failure states explicit; and
- every new declaration follows the repository's doctrine requirements.

PHPDoc generics are useful for `iterable<string, Example>` providers and typed collections, but they must not replace a
concrete `ExampleCorpus`, result variant, or configuration object.

## Test strategy

### Unit and contract tests

Keep focused unit tests for every value invariant, discovery rule, metadata association rule, transform, result variant,
guard, matcher, and CLI exit code. Table-driven fixtures cover LF and CRLF input.

### CommonMark behavior

Adapt the relevant official CommonMark fenced-block examples into fixtures covering fence character, length,
indentation, containers, info strings, unclosed fences, and literal nested fence text. Test both authored bytes and
semantic source.

### Transform safety

Use small PHP fixtures for imports, global and qualified names, declaration collisions, `__NAMESPACE__`, string-based
reflection, explicit namespaces, returns, malformed code, named assertion arguments, throwable descriptions, and every
hard safety rejection. Every failure asserts the Markdown location.

### State restoration

Snapshot test-process state before and after successful and failing examples. Cover nested output buffers, thrown
exceptions, changed cwd, failed cleanup, and examples that attempt to remove owned buffers. Run restoration tests in a
separate PHPUnit process where a deliberately destructive fixture could compromise the suite.

### Separate-process behavior

Use the real current PHP binary. Prove a distinct PID, bootstrap loading, stdout/stderr separation, assertion failure,
parse failure, exit statuses, timeout, and cleanup. Avoid mocks for the acceptance path.

### PHPStan behavior

Exercise the consumer's real `RuleTestCase`, rule, and additional configuration. Test clean examples, message and tip
matching, count mismatches, ordered one-to-one matching, declaration visibility, cwd restoration, and cleanup.

### Compatibility fixtures

Before consumer edits:

1. lock the current Yumemi 10-document/37-fence manifest;
2. execute all current runtime examples through Akashi;
3. verify all relevant PHPStan examples and eight expectations;
4. compare all eight Apocrypha extracted files byte-for-byte; and
5. retain reduced real examples inside Akashi as integration fixtures.

Consumer migrations occur only after these gates pass in Akashi.

### Quality gates

Each implementation chunk runs the narrow PHPUnit tests plus, when affected, PHPStan, coding style, branch coverage,
mutation tests, documentation checks, and the relevant consumer suites. CI covers PHP 8.2 through the newest supported
version. Never claim an unexecuted matrix entry passed.

## Deferred seams informed by public behavior

Public rustdoc behavior helps identify useful capabilities, but does not dictate Akashi names or architecture:

- hidden supporting lines map to a future display-versus-execution source transform;
- ignored examples map to an explicit selection outcome with a reason, never silent omission;
- compile-only examples map to a future execution policy separate from backend selection;
- expected runtime failure maps to a typed expected-outcome contract using PHP exception idioms;
- expected compilation failure maps separately to PHP parse or analyzer expectations; and
- platform/version conditions map to typed predicates and explicit skip reports.

Do not expose Rust names such as `no_run`, `should_panic`, or `compile_fail`. The future model should separate three
orthogonal questions:

1. which backend prepares or executes the code;
2. which phases should run; and
3. what outcome is expected.

### Expected exceptions after the MVP

The roadmap should explicitly include a PHPUnit-familiar equivalent of `expectException()`, without coupling the core
outcome model to PHPUnit. A future typed outcome family should distinguish normal completion from an expected exception:

```text
ExpectedOutcome
  CompletionExpected
  ExceptionExpected
    class-string<Throwable> type
    MessageExpectation|null message
    int|null code
```

`ExceptionExpected` matches the declared exception class or a subclass, as PHP developers expect. `MessageExpectation`
uses explicit modes such as exact, contains, or regular expression rather than interpreting an untyped string
differently in different contexts. Programmatic configuration should read naturally, for example:

```php
RuntimeExpectation::exception(DomainException::class)
    ->withMessageContaining('invalid quantity');
```

A separately reviewed Markdown syntax could use inert PHP comments and PHPUnit terminology without requiring a test-case
instance inside the example:

```php
// @akashi-expect-exception \DomainException
// @akashi-expect-exception-message contains: invalid quantity
operationThatFails();
```

Akashi must still catch the `Throwable` itself so output-buffer cleanup, process-state restoration, and Markdown source
mapping always occur. An outcome verifier decides whether the captured exception satisfies `ExceptionExpected`; the
PHPUnit adapter then records that verification with normal PHPUnit assertions and reporting. Matching the expected
exception never excuses a cleanup failure. This feature remains deferred and must not be implemented during the MVP.

Other roadmap seams are deliberately narrow:

- new sources implement `ExampleSource` and produce the same immutable `Example`;
- new verifiers consume `Example` or `PreparedExample` and produce `VerificationResult`;
- new executors consume backend-specific prepared code and produce `ExecutionResult`;
- a future standalone runner composes the existing corpus and verifier objects; and
- reporters format existing results without changing execution.

No registries for those future components are built during the MVP.

## Proposed package layout

The exact namespaces may be refined while implementing, but ownership should remain recognizable:

```text
src/
  Document.php
  Example.php
  ExampleCorpus.php
  Model/                 IDs, paths, locations, code, fence metadata, directives
  Source/                manifest, discovery, loading, Markdown source
  Markdown/              CommonMark adapter and associated metadata parsing
  Selection/             marked-example selector
  Transform/             pipeline, prepared code, source maps, PHP transforms
  Execution/             executor contracts, result variants, modes, failures
  Execution/InProcess/   safety validation, namespace isolation, state guards
  Execution/Process/     Symfony Process adapter and temporary artifacts
  Verification/          generic verification result contracts
  Integration/PhpUnit/   assertion transform, runtime facade, result assertion
  Integration/PHPStan/   expectations, diagnostics, matcher, RuleTestCase trait
  Cli/                   router, commands, input parsing, exit codes, diagnostics
```

Do not create one class per bullet mechanically. Combine types where a class would add no invariant or behavior, but do
not collapse typed boundaries back into string-keyed arrays.

## Small working implementation chunks

Each item should end in a working commit and a pause for review.

1. **Dependencies and model refinement**
   - validate Composer constraints;
   - add runtime dependencies;
   - introduce typed IDs, paths, locations, directives, code views, and corpus;
   - migrate existing model tests without adding parser behavior.
2. **Manifest and discovery**
   - immutable fluent configuration;
   - canonical root/path policy;
   - deterministic loading, exclusions, duplicates, and empty-corpus failures.
3. **CommonMark extraction**
   - prove public source locations;
   - extract PHP fences, authored bytes, semantic code, info strings, and ordinals;
   - add CommonMark-derived and reduced Yumemi fixtures.
4. **Markers and directives**
   - associate configurable markers and the separate-process directive;
   - implement selection errors and tests.
5. **Extraction CLI**
   - command router, help/version, stable exits, clean streams;
   - prove byte equality for all Apocrypha fixtures.
6. **PHP transform foundation**
   - parsing, source maps, safety validation, name resolution, and namespace isolation;
   - no execution until transformed fixtures and failures are reviewable.
7. **In-process execution and PHPUnit runtime integration**
   - assertion bridge, state guards, execution result variants, and PHPUnit facade;
   - run the reduced and then full Yumemi runtime corpus.
8. **Separate-process execution**
   - secure temporary source, Process adapter, directive selection, timeout, and cleanup;
   - prove process isolation and common failures.
9. **PHPStan integration**
   - relevance predicate, ordered expectations, secure temp files, RuleTestCase trait, and matcher;
   - prove the complete Yumemi diagnostic corpus.
10. **Consumer migrations**
    - migrate Yumemi runtime and PHPStan tests;
    - migrate both extractors and Apocrypha consumer calls;
    - remove duplicates only after consumer suites pass.
11. **Public documentation and roadmap**
    - finalize API docs, limitations, directives, roadmap, and migration notes;
    - add examples to the mdBook and concise README.

## Decisions requiring owner review

The design recommends defaults, but these choices deserve particular attention before their implementation chunks:

1. accepting `league/commonmark` and its `ext-mbstring` cost instead of maintaining a fence scanner;
2. representing authored and CommonMark-semantic code separately;
3. rejecting source paths outside the project root and directory symlink traversal by default;
4. the trusted-code definition and the initial in-process unsafe-construct list;
5. rejecting explicit authored namespaces in-process during the MVP;
6. strengthening PHPStan expectations to one-to-one diagnostic matching;
7. the separate-process emergency-timeout and `exit(0)` policies; and
8. refactoring the existing scalar-heavy `Document` and `Example` constructors before declaring them stable.

The in-process safety boundary, namespace transform, and PHPStan matching rule are the highest-impact reviews. The other
choices can be revised locally without changing the overall architecture.
