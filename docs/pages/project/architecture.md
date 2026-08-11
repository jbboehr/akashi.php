# Architecture

<figure class="logion" data-logion="OSD 53:1">
<div class="logion-text">
<blockquote>
<p>Set the cedar vessel beside the bronze and fill each from the same spring; for the feast requireth both fragrance and
endurance, and wisdom appointeth unlike offices without making either ashamed.</p>
</blockquote>
<p class="logion-citation">— <cite>Ordinances of the Synthetic Dawn 53:1</cite></p>
</div>
<img src="../images/logia/OSD-53_1.webp" alt="Cedar and bronze vessels receiving water from one luminous spring in a sunset pavilion" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

This document describes the architecture implemented in the current repository. Historical sequencing, migration
instructions, and clean-room records live outside the public mdBook and are not part of the runtime design.

The companion [Invariants](invariants.md) chapter separates durable behavioral contracts from the replaceable mechanics
described here.

Akashi is a reusable library, not a standalone test runner. It discovers examples into a framework-independent model,
then lets PHPUnit, PHPStan, or the extraction CLI consume that model through explicit adapters.

## Example Lifecycle

```text
Markdown / PHPDoc files
          │
          ▼
DocumentationSource
  ├── inline PHP fences ───────────────────┐
  └── PHPDoc references ─► PHP files/regions
                                            │
                                            ▼
                                      ExampleCorpus
                                            │
                 ┌──────────────────────────┼──────────────────────────┐
                 ▼                          ▼                          ▼
          PHPUnit runtime           PHPStan verification       marked selection
          transform → execute       select → analyze           extract CLI
                 │                          │                          │
                 ▼                          ▼                          ▼
          PHPUnit assertions        PHPUnit assertions             stdout
```

The `Example` is the shared boundary. Consumers do not need to understand parser nodes, generated namespaces, temporary
files, Symfony Process, or PHPStan diagnostics to discover a corpus.

## Source Discovery

`DocumentationSource` is an immutable manifest of an absolute project root, file and directory includes, exclusions, an
optional marker name, and configured PHPDoc reference tags. Loading resolves and validates paths, rejects symbolic-link
directory traversal and duplicate physical documents, sorts documents deterministically, and dispatches `.md` and `.php`
documents by extension. `MarkdownSource` retains the same Markdown-only contract. Both accept bulk file iterables,
including `SplFileInfo` values from Symfony Finder, without depending on Finder.

The source manifests deliberately remain concrete configuration entry points rather than implementations of a public
source interface. Extension-based dispatch is sufficient for the current formats, while `ExampleCorpus` is the shared
boundary consumed by PHPUnit, PHPStan, and extraction.

The CommonMark adapter selects PHP fenced code blocks and associates configured marker comments and external Akashi
directives using document structure rather than regular expressions over the whole file. A shared token-aware parser
also recognizes `skip`, `separate-process`, and typed `expect-exception` PHP line comments anywhere in fenced or
canonical code. It rejects competing declarations and prevents matching text in strings and heredocs. Original source
text and exact line and byte spans remain intact.

The PHPDoc adapter locates every `T_DOC_COMMENT` with PHP's tokenizer, projects each comment's interior lines into
CommonMark by removing conventional docblock decoration, and extracts each comment independently. It then restores the
original PHP `Document`, line coordinates, and raw source spans. Independent parsing prevents metadata from crossing a
docblock boundary; file-wide ordinals keep generated inline identities deterministic.

The PHPDoc reference adapter recognizes `@akashi-example` by default, with an explicit replacement set configurable on
`DocumentationSource`. References resolve from the canonical project root to ordinary `.php` files or token-validated
named regions. The resolver rejects project-root escapes and malformed, nested, overlapping, mismatched, duplicate, or
empty regions. Repeated references and in-project filesystem aliases resolve to one canonical example while retaining
every PHPDoc presentation location. Referenced files do not have to be duplicated in the discovery include manifest.

A nonempty ordered `ExampleCorpus` is constructed only after cross-document marker uniqueness, reference resolution,
physical-source deduplication, and corpus ordering invariants hold.

Discovery is separate from selection. A configured marker adds an explicit identity but does not hide unmarked fences; a
PHPStan relevance predicate selects a subcorpus later; a runtime skip changes PHPUnit disposition without deleting the
example.

## Canonical Example Model

`Document` owns the project-relative path, maintained Markdown or PHP source bytes, and line index. `Example` owns:

- generated identity and human-readable label;
- an `InlineExampleSource` or `ReferencedExampleSource`;
- one canonical `CodeOrigin` containing maintained document, line, byte, and directive locations;
- fence metadata for inline examples or an optional region and all PHPDoc `ReferenceLocation` values for referenced
  examples;
- normalized language;
- the unmodified extracted PHP source;
- document ordinal and optional author-assigned marker ID; and
- a typed set of runtime directives and an optional typed expected-exception contract.

Small value objects validate paths, identifiers, source coordinates, languages, and directives at construction time.
`ExampleCorpus` enforces nonemptiness, unique generated and marker IDs, and deterministic canonical path, source-line,
and stable-ID order.

Original example code remains separate from transformed code. This is necessary for diagnostics and prevents execution
preparation from silently becoming the maintained representation.

## Source Locations and Prepared Code

Each execution backend produces a backend-specific `PreparedExample` containing the original `Example`, generated
`PreparedCode`, its `ExecutionMode`, and a `SourceMap`. Prepared code and its map must contain the same number of lines.

The current map translates generated lines to original Markdown, PHPDoc, or external PHP lines. Transforms preserve or
explicitly account for synthetic lines, allowing assertion, parse, runtime, and PHPStan reporting to prefer a maintained
source location. Failures fall back to the example start when PHP cannot provide a reliable generated line.

The model deliberately retains original locations rather than exposing only temporary files. Canonical external code
origins and separate PHPDoc presentation locations are implemented. A fully composable mapping across future sync,
hidden-code, formatter, or renderer transformations remains deferred.

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
and optional expected throwable type to `PhpUnitResultAsserter`. A compatible execution exception is success only when
in-process execution has no cleanup failure; normal completion and incompatible types fail at the maintained directive
or exception location. The subprocess backend rejects this contract because its result does not preserve throwable
identity. The adapter and facade remain public for projects that need a custom PHPUnit method.

PHPStan follows a separate verification path over the same `Example` model. `PhpStanExampleConfiguration` selects a
relevant ordered subcorpus. The `VerifiesPhpStanExamples` trait parses and validates every selected example, writes
private analysis files, preloads declarations, asks `RuleTestCase` for diagnostics, translates lines, and gives
framework-neutral diagnostics to `DiagnosticMatcher`. Matching requires exact counts and deterministic one-to-one
assignments before PHPUnit receives the result.

The extraction CLI is intentionally smaller. It loads one Markdown or PHP file with an explicit marker name, selects one
author-assigned ID, and writes the original code with its documented final-newline contract. It does not enter either
execution pipeline. `--project-root` supplies the reference-resolution boundary when the selected document is below the
project root; reference targets themselves are not marker IDs.

## Dependency Boundaries

`league/commonmark`, `nikic/php-parser`, and `symfony/process` support core implemented behavior. PHPUnit and PHPStan
are optional Composer suggestions. Their runtime types are confined to integration namespaces so core source discovery
and the CLI can autoload without them.

There is no service container, mutable global registry, plugin registry, or implicit project configuration. Projects
compose source, runtime, and verifier configuration through typed immutable values and ordinary PHPUnit test classes.

## Current and Deferred Architecture

Current architecture supports Markdown and inline PHPDoc fences, PHPDoc references to canonical external PHP files and
named regions, markers, token-aware runtime directives, both execution backends, typed in-process exception
expectations, PHPUnit, PHPStan `RuleTestCase`, and marked extraction. Synchronization, formatting, hidden support code,
documentation-renderer inclusion, generalized verifier plugins, and a standalone runner do not exist yet.

Those directions are recorded in the [Roadmap](roadmap.md). No placeholder interfaces or registries are created solely
for them. The existing separation between original `Example`, prepared source, execution results, and verifier
diagnostics is the seam preserved for future work.
