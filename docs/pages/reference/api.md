# Public API

<figure class="logion" data-logion="OSD 6:14">
<div class="logion-text">
<blockquote>
<p>High above the world, the wandering lights crowded one another until the outermost loosened its circle and moved away.
Silence widened behind it, giving each light room to burn. Space began as permission granted to departure. Bless what
releases without cursing the one who leaves.</p>
</blockquote>
<p class="logion-citation">— <cite>Ordinances of the Synthetic Dawn 6:14</cite></p>
</div>
<img src="../images/logia/OSD-6_14.webp" alt="One wandering light departing a crowded field of luminous celestial orbits into open space" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

Akashi is pre-1.0, so these APIs are usable but may change between minor releases before 1.0. Architecture tests
classify every autoloadable Akashi declaration as an entry point, canonical model type, PHPStan diagnostic model type,
exception, or explicitly internal declaration. This reference groups the public types by consumer workflow;
autoloadability alone does not create an extension point.

## Source and Corpus

| Type                                          | Purpose                                                                                    |
| --------------------------------------------- | ------------------------------------------------------------------------------------------ |
| `jbboehr\Akashi\Source\DocumentationSource`   | Immutable mixed Markdown/PHPDoc discovery and extraction.                                  |
| `jbboehr\Akashi\Source\MarkdownSource`        | Immutable file/directory discovery and CommonMark PHP-fence extraction.                    |
| `jbboehr\Akashi\Source\MarkedExampleSelector` | Select exactly one example by an author-assigned marker ID.                                |
| `jbboehr\Akashi\Document`                     | One maintained Markdown or PHP source document and its line index.                         |
| `jbboehr\Akashi\Example`                      | Canonical example with a typed source variant, code, directives, and optional expectation. |
| `jbboehr\Akashi\ExampleCorpus`                | Ordered, nonempty, unique collection of examples.                                          |

`DocumentationSource` is the ordinary entry point for mixed corpora; `MarkdownSource` remains the explicit Markdown-only
entry point and exposes `loadDocuments()` for consumers that need the selected documents themselves.

`Document`, `Example`, and `ExampleCorpus` form the canonical public model. `Example::$source` is either
`Model\InlineExampleSource` for a Markdown/PHPDoc fence or `Model\ReferencedExampleSource` for a canonical external PHP
file or named region. `Example::codeOrigin()` returns the maintained code location without requiring callers to switch
on that presentation distinction. A referenced source also retains each `Model\ReferenceLocation` where PHPDoc presents
the canonical example. Typed integrations constructing inline examples can use `Example::fromInline()` to derive a
matching `CodeOrigin` from one fenced `SourceLocation`.

Path, identifier, language, fence, directive, and source-coordinate values under `jbboehr\Akashi\Model` are also public
because the canonical model and configuration objects expose them as typed state. That includes
`Model\ExpectedException`, which carries a normalized authored throwable class name without requiring the class to exist
before runtime setup. Their constructors enforce the same invariants used by source discovery; they are data contracts,
not subclassing or service-replacement seams.

The supporting value types are grouped by what they preserve:

| Concern          | Types                                                                                                                                                                                                                                       |
| ---------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Project paths    | `Model\ProjectRoot`, `Model\ProjectPath`, `Model\AbsoluteFilePath`, `Model\DocumentPath`                                                                                                                                                    |
| Example identity | `Model\ExampleId`, `Model\MarkerId`, `Model\MarkerName`, `Model\RegionName`, `Model\PhpDocTagName`                                                                                                                                          |
| Authored source  | `Model\ExampleCode`, `Model\Language`, `Model\LineIndex`, `Model\CodeOrigin`, `Model\SourceSpan`, `Model\SourceLocation`, `Model\MetadataLocation`, `Model\InlineExampleSource`, `Model\ReferencedExampleSource`, `Model\ReferenceLocation` |
| Fence metadata   | `Model\FenceCharacter`, `Model\FenceMetadata`                                                                                                                                                                                               |
| Runtime metadata | `Model\Directive`, `Model\DirectiveSet`, `Model\ExpectedException`                                                                                                                                                                          |

Most consumers receive and inspect these values through the canonical model rather than constructing them directly.
Direct construction remains supported for typed integrations that create documents or examples without weakening the
model to raw arrays or ambiguous strings.

## Synchronization

| Type                                      | Purpose                                                                                  |
| ----------------------------------------- | ---------------------------------------------------------------------------------------- |
| `Synchronization\SynchronizationChecker`  | Inspect presentations and render corrected immutable documents.                          |
| `Synchronization\SynchronizationWriter`   | Atomically persist one corrected document when its maintained bytes are still current.   |
| `Synchronization\SynchronizationRegion`   | Preserve one presentation, its canonical target, fence metadata, and exact source spans. |
| `Synchronization\SynchronizationMismatch` | Pair a stale presentation with its canonical origin and expected normalized PHP code.    |

`SynchronizationChecker::rewrite()` replaces only stale code spans in memory. It preserves directives, fences, prose,
Markdown or PHPDoc container prefixes, and the presentation's line-ending convention, then re-parses and verifies the
result against the already resolved canonical snapshot. The method performs no filesystem writes.

`SynchronizationWriter::write()` accepts the original loaded `Document` and its rendered replacement. It verifies the
maintained bytes, rejects symbolic-link paths, and uses a flushed same-directory temporary file plus atomic rename. The
writer preserves permission bits; callers that need validation across several documents should render the complete set
before invoking it, as the CLI does.

## Formatting

| Type                                 | Purpose                                                                |
| ------------------------------------ | ---------------------------------------------------------------------- |
| `Formatting\PhpCsFixerConfiguration` | Canonical project, PHP-CS-Fixer executable, and optional config paths. |
| `Formatting\FormattingChecker`       | Check inline examples without modifying maintained documents.          |
| `Formatting\FormattingMismatch`      | Pair an inline example with the formatter-proposed replacement code.   |

`FormattingChecker::check()` accepts an `ExampleCorpus`, skips every `ReferencedExampleSource`, and returns ordered
mismatches for inline Markdown and PHPDoc fences. The checker runs a project-installed PHP-CS-Fixer against private
temporary files through an argument vector. It ignores formatter-added file-level material outside the protected body,
preserves authored opening tags, and does not write maintained source.

This is a concrete PHP-CS-Fixer integration, not a generic formatter extension point. External canonical examples remain
ordinary PHP files and should use normal project formatter commands directly.

## PHPUnit Runtime

| Type                                          | Purpose                                                                  |
| --------------------------------------------- | ------------------------------------------------------------------------ |
| `Integration\PhpUnit\VerifiesPhpUnitExamples` | Provide a named PHPUnit test for every example in a consumer corpus.     |
| `Integration\PhpUnit\PhpUnitExampleDataSets`  | Convert a corpus to uniquely labeled PHPUnit data-provider arguments.    |
| `Integration\PhpUnit\PhpUnitRuntime`          | Transform, execute, and assert one example through the selected backend. |
| `Execution\RuntimeConfiguration`              | Canonical project root, optional bootstrap, and default execution mode.  |
| `Execution\ExecutionMode`                     | `InProcess` or `SeparateProcess`.                                        |

Prepared-source, transform, executor, result, and failure types describe Akashi's internal implementation boundary. They
are not public extension points. Most runtime consumers should use `VerifiesPhpUnitExamples`; projects that need a
custom PHPUnit method can use `PhpUnitExampleDataSets` and `PhpUnitRuntime::assertExample()` directly. Both paths keep
backend selection, preparation, execution, cleanup, and PHPUnit reporting within the supported facade.

## PHPStan

| Type                                              | Purpose                                               |
| ------------------------------------------------- | ----------------------------------------------------- |
| `Integration\PHPStan\PhpStanExampleConfiguration` | Canonical project root and relevance predicate.       |
| `Integration\PHPStan\VerifiesPhpStanExamples`     | `RuleTestCase` trait that verifies a selected corpus. |
| `Integration\PHPStan\ExpectationParser`           | Parse authored `//!` expectations.                    |
| `Integration\PHPStan\DiagnosticMatcher`           | Match framework-neutral diagnostics to expectations.  |

`AnalyzerDiagnostic`, `DiagnosticExpectation`, `DiagnosticAssignment`, `DiagnosticMatchResult`,
`DiagnosticMismatchKind`, `DiagnosticsMatched`, and `DiagnosticsMismatched` form the public analyzer-independent
matching model. Direct consumers may use that typed model with `ExpectationParser` and `DiagnosticMatcher`; the
`VerifiesPhpStanExamples` trait remains the supported integration path for PHPStan's runtime objects.

## Exceptions

Source-loading failures, including malformed marker, directive, reference, and named-region metadata, share
`Source\Exception\SourceException`; transformation failures share `Transform\Exception\TransformException`; execution
failures share `Execution\Exception\ExecutionException`; and PHPStan integration failures share
`Integration\PHPStan\Exception\PhpStanException`. Formatting configuration, execution, output, unsupported-input, and
cleanup failures share `Formatting\Exception\FormattingException`. Synchronization structure, resolution, and
persistence failures share `Synchronization\Exception\SynchronizationException`, which is also a source-exception
subtype. Specific subclasses preserve distinctions such as missing paths, unsupported examples, runtime configuration,
empty PHPStan selection, and verification infrastructure.

These exception families and their documented leaf classes are public machine-readable failure categories. Consumers
should catch the narrowest meaningful type or its family base instead of parsing exception messages.

`PhpUnitRuntime::assertExample()` can also raise PHPUnit's ordinary expectation-failure or skipped-test control flow.

## Optional Dependencies

Core discovery, the domain model, transformation, execution, extraction, and synchronization do not require PHPUnit,
PHPStan, or PHP-CS-Fixer to autoload. The `Integration\PhpUnit` namespace requires a compatible PHPUnit installation
when used. PHPStan verification requires both PHPUnit and PHPStan because it integrates with `RuleTestCase` and reports
through PHPUnit. Formatting checks require a compatible project-installed PHP-CS-Fixer executable only when invoked;
Akashi does not load its PHP classes.

See [Compatibility and Safety](compatibility.md) for the targeted versions.
