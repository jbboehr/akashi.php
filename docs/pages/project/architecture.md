# Architecture

This document describes the architecture implemented in the current repository. Historical sequencing, migration
instructions, and clean-room records live outside the public mdBook and are not part of the runtime design.

Akashi is a reusable library, not a standalone test runner. It discovers examples into a framework-independent model,
then lets PHPUnit, PHPStan, or the extraction CLI consume that model through explicit adapters.

## Example Lifecycle

```text
Markdown files
      │
      ▼
MarkdownSource ── CommonMark extraction ──► ExampleCorpus
                                                │
                   ┌────────────────────────────┼─────────────────────────┐
                   ▼                            ▼                         ▼
          PHPUnit runtime                PHPStan verification      marked selection
          transform → execute            select → analyze          extract CLI
                   │                            │                         │
                   ▼                            ▼                         ▼
          PHPUnit assertions             PHPUnit assertions          stdout
```

The `Example` is the shared boundary. Consumers do not need to understand parser nodes, generated namespaces, temporary
files, Symfony Process, or PHPStan diagnostics to discover a corpus.

## Source Discovery

`MarkdownSource` is an immutable manifest of an absolute project root, file and directory includes, exclusions, and an
optional marker name. Loading resolves and validates paths, rejects symbolic-link directory traversal and duplicate
physical documents, sorts documents deterministically, and gives each document to `CommonMarkExampleExtractor`.

The CommonMark adapter selects PHP fenced code blocks and associates configured marker comments and Akashi runtime
directives using document structure rather than regular expressions over the whole file. It preserves original source
text and exact line and byte spans. A nonempty ordered `ExampleCorpus` is constructed only after cross-document marker
uniqueness and corpus ordering invariants hold.

Discovery is separate from selection. A configured marker adds an explicit identity but does not hide unmarked fences; a
PHPStan relevance predicate selects a subcorpus later; a runtime skip changes PHPUnit disposition without deleting the
example.

## Canonical Example Model

`Document` owns the project-relative path, maintained Markdown bytes, and line index. `Example` owns:

- generated identity and human-readable label;
- its originating `Document`;
- line, byte, fence, marker, and directive locations;
- normalized language and fence metadata;
- the unmodified extracted PHP source;
- document ordinal and optional author-assigned marker ID; and
- a typed set of runtime directives.

Small value objects validate paths, identifiers, source coordinates, languages, and directives at construction time.
`ExampleCorpus` enforces nonemptiness, unique generated and marker IDs, and deterministic document/ordinal order.

Original example code remains separate from transformed code. This is necessary for diagnostics and prevents execution
preparation from silently becoming the maintained representation.

## Source Locations and Prepared Code

Each execution backend produces a backend-specific `PreparedExample` containing the original `Example`, generated
`PreparedCode`, its `ExecutionMode`, and a `SourceMap`. Prepared code and its map must contain the same number of lines.

The current map translates generated lines to original Markdown lines. Transforms preserve or explicitly account for
synthetic lines, allowing assertion, parse, runtime, and PHPStan reporting to prefer a maintained source location.
Failures fall back to the example start when PHP cannot provide a reliable generated line.

The model deliberately retains original locations rather than exposing only temporary files. A fully composable,
multi-origin source-map system is deferred, but the current representation does not discard the information needed to
add canonical external sources and separate presentation locations later.

## In-Process Transformation

`InProcessTransformer` is the fixed composition root for the default backend:

1. `PhpExampleParser` supplies a valid PHP opening tag when absent and parses with `nikic/php-parser`.
2. `PhpNameResolver` resolves names before relocation.
3. `InProcessSafetyValidator` rejects constructs that cannot be isolated soundly.
4. `NativeAssertionRewriter` changes native `assert()` calls into fully qualified PHPUnit-backed assertions while
   preserving authored expressions and source coordinates.
5. `NamespaceIsolator` places declarations in a generated execution namespace and produces prepared code plus a line
   map.

These mechanics are internal. Akashi has no public transform registry; a fixed order makes the safety and mapping
contract reviewable. Unsupported constructs fail explicitly and recommend the authored separate-process directive
instead of changing backend semantics implicitly.

## Execution Backends

Both backends implement the typed `Executor` contract and return `ExecutionSucceeded` or `ExecutionFailed`. Results
retain the prepared example, captured streams, duration, failure phase, original cause, and cleanup failures. Execution
and cleanup failure are separate phases so a restoration problem cannot erase the first exception.

### In process

`InProcessExecutor` evaluates prepared source through an empty closure scope. It optionally establishes a configured
project root and `require_once` bootstrap, captures output, catches `Throwable`, and restores guarded process state in
`finally`. Generated namespaces isolate declarations; the evaluation closure prevents ordinary top-level variables from
entering caller scope.

This backend is the default because it avoids child startup, shares PHPUnit's autoloaded environment, and lets rewritten
assertions count as ordinary PHPUnit assertions. Its protections are deliberately best-effort: PHP cannot recover from
every fatal condition or external side effect.

### Separate process

`SeparateProcessTransformer` preserves normal-file PHP semantics and its line map. `SubprocessExecutor` creates a
private temporary file, invokes the current PHP binary with Symfony Process without a shell, applies the configured
project root and optional bootstrap, captures both streams, enforces the fixed emergency timeout, classifies child
outcomes, and removes the file in `finally`.

The child protects PHPUnit from ordinary fatal process behavior. It is not an operating-system sandbox.

## Verification and Integrations

`VerifiesPhpUnitExamples` is the ordinary consumer integration: its corpus and optional runtime-configuration hooks
compose a project-owned PHPUnit test class without an extension or mutable registry. It delegates named provider
arguments to `PhpUnitExampleDataSets`, which rejects duplicate labels before yielding. `PhpUnitRuntime` is the runtime
facade: it applies skip and mode precedence, prepares and executes through the selected backend, then gives the result
to `PhpUnitResultAsserter`. The adapter and facade remain public for projects that need a custom PHPUnit method.

PHPStan follows a separate verification path over the same `Example` model. `PhpStanExampleConfiguration` selects a
relevant ordered subcorpus. The `VerifiesPhpStanExamples` trait parses and validates every selected example, writes
private analysis files, preloads declarations, asks `RuleTestCase` for diagnostics, translates lines, and gives
framework-neutral diagnostics to `DiagnosticMatcher`. Matching requires exact counts and deterministic one-to-one
assignments before PHPUnit receives the result.

The extraction CLI is intentionally smaller. It loads one Markdown file with an explicit marker name, selects one
author-assigned ID, and writes the original code with its documented final-newline contract. It does not enter either
execution pipeline.

## Dependency Boundaries

`league/commonmark`, `nikic/php-parser`, and `symfony/process` support core implemented behavior. PHPUnit and PHPStan
are optional Composer suggestions. Their runtime types are confined to integration namespaces so core source discovery
and the CLI can autoload without them.

There is no service container, mutable global registry, plugin registry, or implicit project configuration. Projects
compose source, runtime, and verifier configuration through typed immutable values and ordinary PHPUnit test classes.

## Current and Deferred Architecture

Current architecture supports Markdown sources, markers, two runtime directives and backends, PHPUnit, PHPStan
`RuleTestCase`, and marked extraction. PHPDoc sources, external canonical examples, named regions, synchronization,
formatting, hidden support code, generalized verifier plugins, and a standalone runner do not exist yet.

Those directions are recorded in the [Roadmap](roadmap.md). No placeholder interfaces or registries are created solely
for them. The existing separation between original `Example`, prepared source, execution results, and verifier
diagnostics is the seam preserved for future work.
