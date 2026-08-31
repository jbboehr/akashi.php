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
                 ┌────────────────────┬─────┼────────────────────┬───────────────┐
                 ▼                    ▼                          ▼               ▼
          PHPUnit runtime     PHPStan verification       inline formatting  marked selection
          transform → execute select → analyze           PHP-CS-Fixer check extract CLI
                 │                    │                          │               │
                 ▼                    ▼                          ▼               ▼
          PHPUnit assertions  PHPUnit assertions           typed diffs        stdout
```

The `Example` is the shared boundary. Consumers do not need to understand parser nodes, generated namespaces, temporary
files, Symfony Process, or PHPStan diagnostics to discover a corpus.

## Source Discovery

`DocumentationSource` is an immutable manifest of an absolute project root, file and directory includes, exclusions, an
optional legacy marker name, and configured PHPDoc reference tags. Loading resolves and validates paths, rejects
symbolic-link directory traversal and duplicate physical documents, sorts documents deterministically, and dispatches
`.md` and `.php` documents by extension. `MarkdownSource` retains the same Markdown-only contract. Both accept bulk file
iterables, including `SplFileInfo` values from Symfony Finder, without depending on Finder.

The source manifests deliberately remain concrete configuration entry points rather than implementations of a public
source interface. Extension-based dispatch is sufficient for the current formats, while `ExampleCorpus` is the shared
boundary consumed by PHPUnit, PHPStan, and extraction.

The CommonMark adapter selects PHP fenced code blocks and associates canonical `akashi:` metadata plus an optional
legacy marker dialect using document structure rather than regular expressions over the whole file. One internal typed
grammar parser merges comma-separated flags and keyed properties from adjacent HTML comments and token-aware PHP line
comments. It recognizes stable `example` identity, `skip`, `compile-only`, `separate-process`, typed `expect-exception`,
optional message and code constraints, and exact expected stdout anywhere in fenced or referenced canonical code. It
rejects duplicate or conflicting declarations and prevents matching text in strings and heredocs. Original source text
and exact line and byte spans remain intact; the public model continues to expose separate typed identity, directives,
and runtime expectations rather than a generic metadata map.

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

Discovery is separate from selection. Canonical `example` metadata or a configured legacy marker adds an explicit
identity but does not hide unnamed fences; a PHPStan relevance predicate selects a subcorpus later; runtime skip and
compile-only change PHPUnit disposition without deleting the example.

## Canonical Example Model

`Document` owns the project-relative path, maintained Markdown or PHP source bytes, and line index. `Example` owns:

- corpus identity and human-readable label;
- an `InlineExampleSource` or `ReferencedExampleSource`;
- one canonical `CodeOrigin` containing maintained document, line, byte, and directive locations;
- fence metadata for inline examples or an optional region and all PHPDoc `ReferenceLocation` values for referenced
  examples;
- normalized language;
- the unmodified extracted PHP source;
- document ordinal and optional author-assigned named example ID; and
- a typed set of runtime directives and an optional typed expected-exception contract.

Small value objects validate paths, identifiers, source coordinates, languages, and directives at construction time.
`ExampleCorpus` enforces nonemptiness, unique corpus and named example IDs, and deterministic canonical path,
source-line, and corpus-ID order.

Original example code remains separate from transformed code. This is necessary for diagnostics and prevents execution
preparation from silently becoming the maintained representation.

## Source Locations and Prepared Code

Each execution backend produces a backend-specific `PreparedExample` containing the original `Example`, generated
`PreparedCode`, its `ExecutionMode`, and a `SourceMap`. Prepared code and its map must contain the same number of lines.

The current map translates generated lines to original Markdown, PHPDoc, or external PHP lines. A transform describes
each output line in terms of its input map, and Akashi composes that relation back to the maintained source. Synthetic
lines remain explicitly unmapped. This lets sequential transforms preserve useful locations without depending on which
source adapter produced the example, allowing assertion, parse, runtime, and PHPStan reporting to prefer a maintained
source location. Failures fall back to the example start when PHP cannot provide a reliable generated line.

The model deliberately retains original locations rather than exposing only temporary files. Canonical external code
origins and separate PHPDoc presentation locations are implemented. Mapping one generated artifact to several source
origins, as future synchronization write diagnostics, hidden-code, formatter, or renderer work may require, remains
deferred.

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
private authored file, invokes the current PHP binary with Symfony Process through an explicit argument vector without
constructing a shell command, applies the configured project root and optional bootstrap, captures both streams,
enforces the fixed emergency timeout, classifies child outcomes, and removes temporary files in `finally`.

For an expected exception, a private launcher catches `Throwable` around the unmodified authored file and writes
token-bound JSON evidence to a private side file. Base64 fields preserve arbitrary PHP strings, while the child records
type availability, subtype matching, and integer-or-string exception codes in the environment where the throwable
exists. The parent validates that evidence and maps its generated line without treating stdout or stderr as a protocol.
Nonzero exits take precedence; after a clean child exit, malformed changed evidence is an infrastructure failure.

The child protects PHPUnit from ordinary fatal process behavior. It is not an operating-system sandbox.

## Verification and Integrations

`VerifiesPhpUnitExamples` is the ordinary consumer integration: its corpus and optional runtime-configuration hooks
compose a project-owned PHPUnit test class without an extension or mutable registry. It delegates named provider
arguments to `PhpUnitExampleDataSets`, which rejects duplicate labels before yielding. For configured test classes,
`PhpUnitExampleSuite` immutably joins the corpus and runtime configuration, while `VerifiesPhpUnitExampleSuite` exposes
them through one hook. The suite is resolved once per provider invocation; each data set carries only its selected
example and the shared runtime configuration, so PHPUnit and ParaTest do not receive the complete corpus for every test.
The two traits are alternative public entry points and preserve the same runtime semantics without a hidden registry or
cache. `PhpUnitRuntime` is the runtime facade: it applies skip and compile-only disposition before mode precedence.
Compile-only parses against the host PHP version and records one assertion without transformation, bootstrap loading, or
execution. Ordinary examples are prepared and executed through the selected backend, then their result, optional
expected throwable contract, and optional exact stdout expectation go to `PhpUnitResultAsserter`. A compatible authored
exception is success only when execution has no cleanup failure, its message contains the optional case-sensitive
substring, and its integer code equals the optional code constraint. Normal completion and type, message, code, or
stdout mismatches fail with maintained source context. Output comparison occurs only after the execution or exception
contract succeeds. Child exits, signals, timeouts, and infrastructure failures remain failures. The adapter and facade
remain public for projects that need a custom PHPUnit method.

PHPStan follows a separate verification path over the same `Example` model. `PhpStanExampleConfiguration` selects a
relevant ordered subcorpus. The `VerifiesPhpStanExamples` trait parses identifier-oriented expectations associated with
the next PHP statement and legacy message-only expectations, writes private analysis files, preloads declarations, asks
`RuleTestCase` for diagnostics, translates lines, and gives framework-neutral diagnostics to `DiagnosticMatcher`.
Matching may constrain exact identifier, message-or-tip substring, and maintained statement span. It requires exact
counts and deterministic one-to-one assignments before PHPUnit receives the result.

External analysis has a narrower implemented seam. `PhpStanJsonDecoder` converts PHPStan 1.12 or 2.x JSON output into a
typed `PhpStanJsonResult` without loading PHPStan or PHPUnit. The result keeps analyzer-wide errors, per-file
association, counts, and available diagnostic evidence distinct instead of flattening them into the matcher model. The
decoder validates the documented structure and internal counts while ignoring unknown fields. `PhpStanResultVerifier`
then compares an explicit expectation map across the union of expected and reported paths through `DiagnosticMatcher`.
Its framework-neutral `PhpStanVerificationResult` separates successful file assignments, complete file mismatches, and
analyzer-wide errors, so expected verification failures remain data rather than exceptions. Command execution and exit
status interpretation remain separate from matching. `PhpStanCommandRunner` now executes an explicit,
boundary-preserving executable and argument vector from an explicit project root without constructing a command string.
Its typed result preserves normal exit status and streams without treating a nonzero status as an infrastructure
failure, while timeout, signal, path/setup failure, local instrumentation failure, and process failures surfaced as
exceptions remain distinguishable. `PhpStanCommandVerifier` validates expectations before launch and composes those
three stages into typed non-completion, output-rejection, or completed-verification outcomes. It does not treat a
nonzero analysis status as an automatic verification failure. Its exception-oriented convenience methods require a
successful diagnostic match while retaining the complete typed outcome on `PhpStanCommandVerificationFailedException`.
`PhpStanExternalFixturePlanner` selects only referenced canonical PHP examples, groups whole-file and named-region
expectations by physical identity where the filesystem reports one, validates that their loaded bytes are still current,
and returns direct project-relative analysis paths with platform-native canonical absolute expectation keys.

Symfony Console supplies declarative command definitions, generated help, command listing, shell completion, input and
output routing, and cross-platform terminal handling. Akashi wraps that replaceable router with exact command names,
single-occurrence options, stable statuses, and explicit stdout/stderr contracts. The extraction command loads one
Markdown or PHP file, selects one author-assigned `example` identity, and writes the original code with its documented
final-newline contract. The optional `--legacy-marker-name` option adds a legacy marker-comment dialect. The command
does not enter either execution pipeline. `--project-root` supplies the reference-resolution boundary when the selected
document is below the project root; reference targets themselves are not named example IDs.

The synchronization layer recognizes an `akashi-sync` comment, one closed PHP fence, and an `akashi-sync-end` comment as
consecutive Markdown blocks; blank separator lines are allowed so normal Markdown formatters preserve a valid structure.
`SynchronizationRegion` retains the presentation document, exact raw region and code spans, logical undecorated code,
fence metadata, and canonical target. `SynchronizationChecker` resolves that target through the same project-containment
and named-region rules as PHPDoc references, normalizes only line endings and a missing final newline, and returns typed
mismatches with both presentation and canonical origins. The `sync --check` CLI loads explicit Markdown or PHP files
through the shared project-containment loader and reports those mismatches on stderr with stable process statuses and
source-labelled unified diffs from presentation to canonical code. Write mode renders and validates the complete
selected set before mutation, rechecks maintained and canonical snapshots, and then reports each changed path
deterministically. It rejects a selected whole-file canonical dependency when the selected canonical PHP document would
also change; named-region dependencies remain valid because presentation edits cannot overlap their tokenized executable
regions.

The same checker can apply all nonoverlapping mismatch edits to the original byte spans and return a new immutable
`Document`. It derives the presentation container prefix and line ending from the authored fence, changes only code
content, and re-parses and verifies the result before returning it. Unsafe canonical content that would terminate a
fence or PHPDoc comment is rejected against its original presentation line and canonical target. Verification uses the
already resolved canonical snapshot rather than rereading source files. This pure library operation performs no
filesystem writes. `SynchronizationWriter` supplies the separate persistence boundary: it rejects stale bytes and
symbolic-link paths, writes and flushes a temporary sibling, preserves permission bits, and atomically replaces one
document. Batch validation belongs to the CLI; individual replacements remain atomic even if a later filesystem failure
interrupts a multi-file write.

The formatting layer addresses only code physically embedded in Markdown or PHPDoc. `FormattingChecker` receives the
same corpus, skips referenced external sources, and invokes one project-installed PHP-CS-Fixer process per inline
example. It separates an authored opening tag from the checked body, prefixes a harmless `declare` plus an
entropy-backed boundary, and writes the resulting valid PHP to a private temporary directory. PHP-CS-Fixer runs through
an explicit argument vector without constructing a shell command, cache, or parallel workers and under a fixed timeout.
The checker reads the formatter-modified temporary file, verifies the boundary, discards project-level material inserted
before it, and returns typed `FormattingMismatch` values without writing maintained documentation. Process evidence
replaces the temporary filename with the original document location; cleanup executes on every outcome.

`FormattingRewriter` is the separate pure update boundary. It accepts the exact loaded `Document` plus checked
mismatches for that document, rejects stale, cross-document, duplicate, referenced, or structurally unsafe inputs, and
applies only their nonoverlapping code spans. It restores the existing Markdown or PHPDoc container prefix around each
formatter-produced logical line, preserves the formatter's body line endings, then re-extracts the complete candidate
document to ensure every fence and runtime directive retains its meaning. The result is a new immutable `Document`; no
filesystem access occurs.

`format --check` supplies explicit safe document discovery and source-labelled unified diffs over that library seam.
`format --write` first renders every proposed document in memory, then reloads the complete source and repeats every
formatter invocation. Source bytes and formatter results must match the first pass before any maintained file changes.
Persistence reuses the stale-byte-protected, symbolic-link-rejecting atomic writer, so each file is replaced from a
flushed sibling while retaining its permission bits. External whole files and named regions remain directly
formatter-compatible PHP and are intentionally left to normal project formatter commands. There is no generic formatter
registry.

## Dependency Boundaries

`league/commonmark`, `nikic/php-parser`, `sebastian/diff`, `symfony/console`, `symfony/process`, and the PHP 8.2 Random
extension polyfill support core implemented behavior. Parser output is normalized with PHP's native `PhpToken`, keeping
source edits independent of the token class that differs between PHP-Parser 4 and 5. `sebastian/diff` supplies
unified-diff formatting without making PHPUnit a CLI dependency. Symfony Console supplies the replaceable CLI router and
presentation layer; Symfony Process supplies explicit child-process execution. The Random polyfill preserves the typed
`Randomizer` seam on PHP 8.1 and defers to PHP's native extension on later runtimes. PHPUnit, PHPStan, and PHP-CS-Fixer
are optional Composer suggestions. PHP-CS-Fixer is invoked as a project executable and its classes are never loaded by
Akashi. PHPUnit and PHPStan runtime types are confined to integration namespaces so core source discovery and the CLI
can autoload without them.

There is no service container, mutable global registry, plugin registry, or implicit project configuration. Projects
compose source, runtime, and verifier configuration through typed immutable values and ordinary PHPUnit test classes.

## Current and Deferred Architecture

Current architecture supports Markdown and inline PHPDoc fences, PHPDoc references to canonical external PHP files and
named regions, synchronized-presentation inspection and in-memory rewriting through the library, check/write sync CLI,
optional PHP-CS-Fixer checks and validated in-memory formatting rewrites for inline examples, markers, token-aware
runtime directives, both execution backends, typed exception expectations, PHPUnit, identifier- and text-oriented
PHPStan expectations through `RuleTestCase`, typed PHPStan command execution and composed verification, JSON decoding
and standalone result verification, and marked extraction. External canonical PHP examples can also be projected into
direct PHPStan command fixtures without generated source. Hidden support code, documentation-renderer inclusion,
generalized verifier plugins, and a standalone Akashi test runner do not exist yet.

Those directions are recorded in the [Roadmap](roadmap.md). No placeholder interfaces or registries are created solely
for them. The existing separation between original `Example`, prepared source, execution results, and verifier
diagnostics is the seam preserved for future work.
