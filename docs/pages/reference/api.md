# Public API

Akashi is pre-1.0, so these APIs are usable but remain provisional. Types marked `@internal` in source are not public
extension points even when Composer can autoload them.

## Source and Corpus

| Type                                          | Purpose                                                                               |
| --------------------------------------------- | ------------------------------------------------------------------------------------- |
| `jbboehr\Akashi\Source\MarkdownSource`        | Immutable file/directory discovery and CommonMark PHP-fence extraction.               |
| `jbboehr\Akashi\Source\MarkedExampleSelector` | Select exactly one example by an author-assigned marker ID.                           |
| `jbboehr\Akashi\Document`                     | One maintained Markdown document and its line index.                                  |
| `jbboehr\Akashi\Example`                      | Canonical extracted example, source location, fence metadata, marker, and directives. |
| `jbboehr\Akashi\ExampleCorpus`                | Ordered, nonempty, unique collection of examples.                                     |

Path, identifier, language, fence, directive, and source-coordinate values live under `jbboehr\Akashi\Model`. Their
constructors enforce domain invariants and may be useful to integration code that needs typed values.

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

`AnalyzerDiagnostic`, expectation, assignment, and match-result types form the analyzer-independent matching model. The
`VerifiesPhpStanExamples` trait is the supported integration path for PHPStan's runtime objects.

## Exceptions

Source-loading failures, including malformed marker and directive metadata, share `Source\Exception\SourceException`;
transformation failures share `Transform\Exception\TransformException`; execution failures share
`Execution\Exception\ExecutionException`; and PHPStan integration failures share
`Integration\PHPStan\Exception\PhpStanException`. Specific subclasses preserve distinctions such as missing paths,
unsupported examples, runtime configuration, empty PHPStan selection, and verification infrastructure.

`PhpUnitRuntime::assertExample()` can also raise PHPUnit's ordinary expectation-failure or skipped-test control flow.

## Optional Dependencies

Core discovery, the domain model, transformation, execution, and the CLI do not require PHPUnit or PHPStan to autoload.
The `Integration\PhpUnit` namespace requires a compatible PHPUnit installation when used. PHPStan verification requires
both PHPUnit and PHPStan because it integrates with `RuleTestCase` and reports through PHPUnit.

See [Compatibility and Safety](compatibility.md) for the targeted versions.
