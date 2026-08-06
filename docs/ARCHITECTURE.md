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

A true untrusted-code sandbox remains a separate project involving operating-system isolation, capability restrictions,
and resource controls. The owner accepted this trusted-code boundary for the MVP.

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
2. **Models** preserve identity, unmodified example source, directives, and original maintained-source locations.
3. **Transforms** turn an example into backend-specific prepared source with a source map back to that origin.
4. **Executors** execute only prepared source and return explicit result variants.
5. **Verifiers** compare an execution or analysis result with a contract.
6. **Integrations** translate verification results into PHPUnit failures or CLI behavior.

There is no service container, mutable global registry, or general plugin registry in the MVP.

### Public API boundary

The supported surface consists of the immutable source/model types, marked selector, executor and result contracts,
verifier contracts, and documented integration facades. Immutable runtime configuration joins that boundary with the
separate-process backend. Third-party parser nodes, Symfony Process objects, PHPUnit internals, and PHPStan diagnostics
must not leak through core public signatures.

Low-level transforms, source-map machinery, state guards, and temporary-artifact helpers begin as `@internal`. Promote
one only when a concrete consumer use case needs direct composition. Public exceptions share a small Akashi domain base
but retain specific subclasses for configuration, discovery, parsing, selection, unsupported source, execution
infrastructure, and cleanup failures, so consumers may catch narrowly without parsing messages.

Before the first stable release, record the supported public classes and their invariants. Thereafter, apply semantic
versioning to those contracts. Debug metadata and generated source are inspectable through an explicit diagnostic API,
not public mutable properties and not routine failure output.

## Dependency plan

### Runtime dependencies

The runtime dependencies are:

| Package                | Constraint | Purpose                                                                        |
| ---------------------- | ---------- | ------------------------------------------------------------------------------ |
| `league/commonmark`    | `^2.8.3`   | Standards-compliant CommonMark block parsing and fenced-code AST nodes         |
| `nikic/php-parser`     | `^5.8`     | PHP parsing, locations, AST validation, name resolution, and source transforms |
| `symfony/process`      | `^7.4`     | Portable argument escaping, output capture, exit status, and process timeout   |
| `composer-runtime-api` | `^2.2`     | Reliable Composer autoloader discovery from the installed CLI proxy            |

The constraints were validated through Composer before being added to `composer.json`. They all support PHP 8.2 at the
reviewed versions. Caret constraints keep compatible fixes installable.

`nikic/php-parser` and `symfony/process` were intentionally installed during the dependency slice before their first
imports. This keeps the accepted MVP dependency set under continuous Composer and lowest-version validation;
`nikic/php-parser` first becomes active in the transform slice and `symfony/process` in the separate-process slice.

`league/commonmark` is intentionally preferred over a new Markdown implementation. Akashi needs CommonMark container,
fence, info-string, and line-location behavior, not merely a regular expression that passes the current corpus. Akashi
will use its public AST API and will not render HTML.

The parser integration must begin with a focused spike proving that public fenced-code nodes provide sufficient start
and end line information. A small raw-line indexer may supplement the AST to recover exact source spans and original
line endings, but it must not become an independent competing Markdown parser.

`league/commonmark` brings `ext-mbstring` and several small transitive packages. The owner accepted that cost for the
MVP in favor of standards correctness; revisit it only if dependency constraints create concrete integration problems.

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
- `ProjectPath` applies the same containment rules to configured files and directories and permits `.` for the project
  root; discovery converts selected files to `DocumentPath` values.
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

### Original example and prepared code

`Document` owns the complete original Markdown bytes. `ExampleCode` contains the unmodified PHP content defined by
CommonMark: Markdown container prefixes and fence indentation are not PHP, while an authored `<?php` opening tag and the
code's original line endings are preserved. `SourceLocation` maps that code back to its exact document lines and raw
source span.

For top-level Yumemi fences, the document slice and `ExampleCode` are byte-identical. A block quote illustrates why they
can differ: the document contains `> ` prefixes, but the executable PHP does not. The original representation remains
available through `Document`; consumers do not choose between two public code strings.

Transforms receive `ExampleCode` and produce a distinct `PreparedCode`. Marked extraction also uses `ExampleCode`, then
applies its documented final-newline contract. Hidden supporting lines are deferred; a future display view may derive
from the same document span, but the MVP must not implement or silently recognize Rust's `# ` convention.

### Source locations

Avoid ambiguous `startLine` and `endLine` meanings. `SourceLocation` should expose:

- opening fence line;
- first code-content line;
- last code-content line, if any;
- closing fence line, if present; and
- marker or directive line locations when applicable.

User-facing failures point to the first relevant code line and add AST-relative offsets for transformed statements.
Compatibility accessors for the current integer properties may remain until the model refactor is complete.

### Future canonical origins and presentation locations

`Document` and `SourceLocation` are intentionally Markdown-specific for the MVP. Future PHPDoc and external-example
sources must be able to represent, without forcing all of them into a synthetic Markdown document:

- an inline Markdown fence;
- an inline PHPDoc fence;
- an external whole PHP file;
- a named region inside an external PHP file; and
- an inline presentation synchronized from an external source.

The future model must conceptually distinguish the canonical code origin from zero or more documentation reference or
presentation locations. A substantial external PHP file or named region remains canonical even when several PHPDoc or
Markdown locations present it. A synchronized inline copy is a presentation, not another source of truth.

That distinction continues through transformation and verification:

```text
canonical code origin -> extracted code -> transformed or temporary PHP -> verifier diagnostic
          |
          +-> documentation reference or synchronized presentation
```

Mappings between these stages must compose so parse errors, rewritten assertions, runtime exceptions, PHPStan
diagnostics, formatter errors and diffs, synchronized copies, hidden support code, and future linter or compiler results
can point to the source developers maintain. Reports must not expose only an opaque generated or temporary-file line
when a canonical location is available. A presentation location may be included as useful context, but it must not
replace the canonical failure location.

The MVP does not need a universal source-origin hierarchy or fully general mapping engine. Its current `Document`,
`ExampleCode`, `SourceLocation`, parser tokens, AST locations, and prepared-code mapping must simply retain rather than
discard the data needed for that later composition. Original authored code remains separate from every transformed,
generated, hidden, or synchronized view.

### Corpus invariants

`ExampleCorpus` is an immutable, iterable collection that validates on construction:

- generated example IDs are unique;
- explicit marker IDs are unique within the marker namespace used for selection;
- examples are in deterministic document-path and block-ordinal order; and
- the corpus is nonempty.

`MarkedExampleSelector` provides the typed selection currently required by extraction consumers without exposing mutable
arrays. General-purpose corpus filtering remains deferred until a concrete runtime or analyzer consumer establishes how
an empty selection should be represented without weakening the corpus's nonempty invariant. Source loaders report an
empty requested PHP corpus as a source error rather than constructing an invalid collection.

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
- explicit file paths without the case-sensitive `.md` extension are rejected during configuration, while recursive
  directory scans silently ignore non-Markdown files;
- exclusion paths match either one project-relative path or an entire directory subtree and must exist when loaded;
- only files ending in `.md`, compared case-sensitively, are selected in the MVP;
- symlinked directories are not followed;
- every resolved file must remain within the project root by default;
- duplicate physical files reached through multiple includes are rejected rather than silently run twice, subject to the
  platform limitation below;
- paths are normalized to `/` and sorted with bytewise lexical comparison; and
- read, missing-root, duplicate, and empty-corpus failures have separate exception types.

The owner accepted rejecting documentation outside the configured project root and not following directory symlinks for
the MVP. Revisit this policy if a concrete monorepo integration requires shared documentation trees.

Physical identity normally uses the device and inode reported by `stat()`. On platforms that report inode `0`, Akashi
falls back to the canonical real path: duplicate paths still fail, but two distinct hard-link aliases to the same file
may not be recognized as duplicates. A portable Windows file-index implementation remains deferred until Akashi has a
Windows discovery test environment.

The generated identity strategy remains compatible with Yumemi:

```text
example-{first 12 hex characters of sha1(project-relative path)}-{decimal ordinal padded to at least two digits}
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

The parser AST determines block boundaries and CommonMark container semantics. `Document`'s `LineIndex` reconstructs the
unmodified example bytes with original line endings and maps them to raw document offsets. Tests must include selected
examples adapted from the official CommonMark specification in addition to the handoff's synthetic cases.

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
- a `SourceMap` from generated lines and nodes to the maintained source origin, which is a Markdown line in the MVP;
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
5. report syntax failures against the maintained source line, which is a Markdown line in the MVP, never only the
   generated source.

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

Namespace rewriting remains the highest-risk transform. The owner accepted preserving global name resolution through AST
rewriting while rejecting explicit authored namespaces in the MVP. Supporting arbitrary authored namespace blocks
in-process can be reconsidered after this behavior is proven against the migration corpus.

### Native assertion transform

The PHPUnit integration supplies a transform that rewrites only calls resolved as PHP's native `assert()` construct. It
calls a small `Integration\PhpUnit\NativeAssertion` bridge that:

- evaluates the condition exactly once;
- applies PHP truthiness as native `assert()` does;
- calls `PHPUnit\Framework\Assert::assertTrue()` so PHPUnit records the assertion;
- preserves an authored string description;
- uses the exact original expression text plus its maintained source location, which is Markdown in the MVP, as the
  fallback description; and
- preserves an authored `Throwable` description by throwing it when the condition fails.

Named arguments and the valid one- and two-argument forms must be tested. Invalid forms fail during transformation.
Because the transformed source contains no native `assert()`, behavior does not depend on `zend.assertions`.

## In-process execution

`InProcessExecutor` accepts only a prepared in-process example. It invokes `eval()` inside a private static closure; the
evaluated source excludes opening and closing PHP tags as required by PHP. The evaluator is a no-capture closure built
around a safely quoted source literal, so the evaluated code receives an empty local variable scope and no internal
variable name can collide with an authored variable.

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
- `ExecutionFailed` contains the phase, primary throwable or structured cause, captured output, an optional validated
  generated failure line, and zero or more cleanup failures.

A cleanup failure can never produce success. If execution and cleanup both fail, preserve the execution failure as the
primary cause and report every cleanup failure as secondary context.

`return` at example top level terminates only the evaluated code and is supported. Tests must prove this explicitly.
Unsupported control-flow constructs fail during validation with a source location.

## Separate-process execution

The internal `SubprocessExecutor` writes the unmodified `ExampleCode` to a private temporary file, adding `<?php` only
when absent. Temporary files are created with PHP's atomic temporary-file primitive, must have `0600` permissions where
supported, and must be verified to reside in the requested absolute temporary directory rather than an undocumented
fallback location.

The process command is an argument list, never a shell string. It uses:

- the current `PHP_BINARY`;
- startup `-d` options that enable native assertions and assertion exceptions;
- the configured bootstrap through `auto_prepend_file` when present;
- the configured project root as working directory; and
- Symfony Process's captured stdout, stderr, and exit status.

The MVP retains Symfony Process's documented 60-second default as an internal emergency ceiling because the dependency
provides it directly. A timeout produces a structured infrastructure failure; it is not an authored expected-timeout
feature. User-configurable timeouts, in-process interruption, alternate PHP binaries, INI values, and environment
variables remain deferred.

PHP parse and runtime locations that name the temporary file are translated through the prepared source map to the
maintained Markdown source. The temporary path may remain in debug metadata but is not the only user-facing location.

Exit status zero is success, even when reached through an authored `exit(0)`; a nonzero status, signal, timeout, or
startup failure is an `ExecutionFailed` result. Stdout and stderr are always captured separately. Stderr alone does not
fail an otherwise successful example until an expected-output contract exists.

Every temporary artifact is removed in `finally`. Cleanup failures are reported as with in-process execution.

Treating `exit(0)` as success remains the provisional MVP policy. Detecting early successful exit would require a
child-control protocol and is not justified by a current migration requirement.

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

- normalized maintained-source path, which is the Markdown path in the MVP;
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
        yield from PhpUnitExampleDataSets::fromCorpus(DocumentationExamples::corpus());
    }

    #[DataProvider('examples')]
    public function testDocumentationExample(Example $example): void
    {
        PhpUnitRuntime::assertExample($example);
    }
}
```

`PhpUnitRuntime` is a stateless convenience facade over explicit pipeline, executor, and result-asserter objects. It
does not store global configuration. Advanced consumers can compose those objects directly. Until the separate-process
backend and immutable runtime configuration are added, the facade executes ordinary examples in-process and rejects a
separate-process directive explicitly instead of weakening its requested isolation.

`PhpUnitExampleDataSets` validates every human label for uniqueness before yielding one `Example` argument under that
label. `PhpUnitResultAsserter` translates `ExecutionFailed` into a PHPUnit assertion failure while retaining the
original cause through the previous-exception chain. When PHP's cause is an `Error` rather than an `Exception`, one
transparent `RuntimeException` link adapts it to PHPUnit's public exception contract without discarding the original
`Error`. After any successful execution the adapter performs one explicit “example completed” assertion, so examples
without authored assertions are not risky tests. Rewritten native assertions continue to count independently.

Do not build a PHPUnit extension or event subscriber in the MVP. A normal data provider and assertion facade are easier
to understand, filter, debug, and statically analyze.

## PHPStan integration

The core model has no PHPStan types. `Integration\PHPStan` owns:

- `DiagnosticExpectation` with expected text and maintained source line, which is Markdown in the MVP;
- `AnalyzerDiagnostic` with identifier, message, optional tip, analyzer line, and mapped maintained-source location;
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
guarded lifecycle, it creates a private temporary directory and one securely named file per relevant example from its
unmodified `ExampleCode`. It then changes to the project root, requires every file exactly once so example-local
declarations are visible to reflection, and analyzes each file independently through `gatherAnalyserErrors([$file])`.
Each analyzer result is still matched against its own immutable `Example`; corpus-level setup does not couple diagnostic
contracts between examples. The trait always restores the working directory and removes every temporary artifact.
Analyzer line numbers are mapped from each generated temporary file back to the maintained Markdown line before
reporting or matching line-sensitive expectations; the temporary path remains diagnostic metadata rather than the
primary source location.

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
- the compatibility graph between expectations and diagnostics must admit a one-to-one assignment.

The matcher uses a deterministic perfect-matching algorithm, not a greedy “first compatible diagnostic” scan. A greedy
scan can reject a valid assignment when a broad substring consumes the only diagnostic matching a later narrow
substring. One-to-one matching is a deliberate, owner-approved strengthening of the legacy behavior: two expectations
cannot reuse one diagnostic while another diagnostic remains unexplained. The current Yumemi corpus remains a required
compatibility gate before this behavior becomes a fixed public contract.

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
indentation, containers, info strings, unclosed fences, and literal nested fence text. Test the unmodified
`ExampleCode`, raw document span, line endings, and mapping between them.

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
matching, count mismatches, overlapping substrings requiring a non-greedy one-to-one assignment, declaration visibility,
cwd restoration, and cleanup.

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

- executable documentation-comment examples map to a future PHPDoc source rather than a requirement to keep substantial
  code physically inside comments;
- externally included documentation reinforces the seam between canonical code origins and presentation locations;
- hidden supporting lines map to a future display-versus-execution source transform whose syntax remains undecided;
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
- future source adapters may attach a canonical code origin and separate presentation locations without changing
  executor contracts;
- new verifiers consume `Example` or `PreparedExample` and produce `VerificationResult`;
- new executors consume backend-specific prepared code and produce `ExecutionResult`;
- a future standalone runner composes the existing corpus and verifier objects; and
- reporters format existing results without changing execution.

No registries for those future components are built during the MVP.

PHPDoc extraction, external references and named regions, synchronization, formatter integration, hidden support code,
and renderer integrations follow the deferred sequence in the implementation handoff. Referenced canonical examples are
preferred for substantial code. Synchronization and automatic docblock rewriting are compatibility features, and
check-only modes should precede write modes. None of these features changes the MVP package layout or dependency plan,
and no placeholder implementation is introduced for them.

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
   - introduce typed IDs, paths, locations, directives, unmodified example code, and corpus;
   - migrate existing model tests without adding parser behavior.
2. **Manifest and discovery**
   - immutable fluent configuration;
   - canonical root/path policy;
   - deterministic loading, exclusions, duplicates, and empty-corpus failures.
3. **CommonMark extraction**
   - prove public source locations;
   - extract PHP fences, unmodified PHP code, raw document spans, info strings, and ordinals;
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

## Recorded owner direction

The owner reviewed the original decision list on 2026-08-05. Proceed with these directions:

1. accept `league/commonmark` and its `ext-mbstring` cost for the MVP;
2. expose one unmodified `ExampleCode`, retain original Markdown in `Document`, and keep transformed code separate;
3. reject paths outside the project root and directory symlink traversal for now;
4. proceed with the documented trusted-code boundary and the evidence-driven initial unsafe-construct list;
5. reject explicit authored namespaces for in-process execution during the MVP;
6. require deterministic one-to-one PHPStan diagnostic matching, subject to the Yumemi compatibility gate;
7. retain Symfony Process's built-in 60-second emergency ceiling without adding timeout configuration; and
8. refine `Document`, `Example`, and supporting value objects incrementally as concrete invariants require.

Treat `exit(0)` as separate-process success unless migration evidence justifies a child-control protocol. Revisit any
accepted choice when implementation evidence contradicts its assumptions rather than preserving the document at the
expense of correctness.
