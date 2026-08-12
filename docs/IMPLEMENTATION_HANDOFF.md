# Build Akashi: Executable Documentation Testing for PHP

You are implementing the initial usable version of a standalone PHP framework for testing code examples embedded in documentation.

The project is provisionally named:

> **Probatio Verborum Viventium『証』〜ＡＫＡＳＨＩ〜**

Use the following package identity:

* Repository: `akashi.php`
* Composer package: `jbboehr/akashi`
* PHP namespace: `jbboehr\Akashi`
* CLI executable: `vendor/bin/akashi`
* Short project name: **Akashi**

The immediate objective is to extract and generalize the documentation-testing machinery currently duplicated or embedded in:

* `jbboehr/yumemi.php`
* `jbboehr/yumemi-apocrypha.php`

The resulting package must replace all documentation-example functionality currently required by Yumemi and Yumemi Apocrypha while remaining generic enough for unrelated PHP projects.

Do not stop after writing a design document. Implement the first working version, test it thoroughly, and integrate it with the local Yumemi repositories when they are available.

## Clean-room policy

This policy is non-negotiable. Akashi must remain clean-room with respect to competing PHP doctest implementations, but
the coding agent may consult official, public, user-facing documentation for the platforms and standards Akashi
integrates with. The permissions below do not permit implementation review where it is otherwise prohibited.

The allowed-reference list below is exhaustive for the MVP. Except for user-owned projects explicitly allowed below,
implementation code, internal tests, and implementation-oriented materials from any documentation-test framework
remain prohibited.

### Allowed reference materials

Official, public, user-facing documentation and specifications may be reviewed for:

* PHP;
* PHPUnit;
* PHPStan;
* Composer;
* CommonMark;
* selected general-purpose dependencies used by Akashi;
* Cargo; and
* rustdoc.

Normal third-party parser or process libraries may be used, provided they are not competing documentation-test
frameworks and their implementation is not copied. Record any selected dependency and the official documentation
consulted for it in the clean-room record.

Official Cargo and rustdoc documentation may be used to understand:

* observable behavior;
* terminology;
* documented user-facing features;
* compatibility considerations; and
* deferred capabilities.

Examples of permitted behavioral concepts include:

* hidden supporting lines;
* ignored examples;
* compile-only or non-running examples;
* expected runtime failure;
* expected compilation failure; and
* how documentation examples behave from the user's perspective.

Copying or adapting high-level Rust doctest idioms is acceptable where they map cleanly to PHP. However:

* do not mechanically copy Rust-specific APIs or terminology;
* prefer PHP, PHPUnit, Composer, and PHPStan idioms where appropriate;
* prefer designs that are explicit, sound, statically analyzable, and type-safe; and
* treat Rust-style names such as `should_panic`, `no_run`, and `compile_fail` as descriptive placeholders rather than
  required Akashi API names.

The coding agent may inspect and adapt code from projects owned by the user, particularly:

* `jbboehr/yumemi.php`; and
* `jbboehr/yumemi-apocrypha.php`.

Akashi's implementation and public API should be independently derived from these actual compatibility requirements,
the requirements in this handoff, and established PHP ecosystem conventions.

### Prohibited Rust materials

Do not inspect:

* rustdoc source code;
* Cargo source code related to doctest implementation;
* compiler internals;
* internal rustdoc or compiler tests;
* implementation-oriented design documents;
* source-level architecture descriptions intended for rustc contributors;
* generated summaries of those implementation details; or
* another agent's analysis of those implementations.

Rust documentation review must remain limited to public behavior and user-facing contracts. Do not derive Akashi class
structures, algorithms, or source-level architecture from Rust implementation material.

### Competing PHP doctest projects

During the initial Akashi implementation, both source-code review and documentation review remain prohibited for:

* `testflowlabs/doctest`;
* `texthtml/doctest`;
* `monadial/phpunit-docrunner`;
* `hoaproject/Kitab`; and
* any other competing PHP documentation-test framework discovered during the work.

Do not inspect their:

* source code;
* tests;
* package archives;
* installed Composer files;
* READMEs;
* public documentation;
* examples;
* CLI help;
* configuration references;
* issue discussions describing architecture; or
* summaries produced by another agent or model.

Do not clone or install these projects for inspection, browse their repositories, use code search against them, ask
another agent to inspect them, or indirectly obtain their implementation or documentation through mirrors or generated
summaries. Their high-level capabilities are already represented in this handoff as deferred requirements. Akashi's
initial architecture and public API must emerge independently from Yumemi's actual requirements and established PHP
ecosystem conventions.

### Later comparative review

After the MVP architecture and public API have been implemented and recorded, a separate documentation-only comparative
review of competing PHP doctest projects may be performed. That later review is not part of the current implementation
task and must:

* be explicitly requested as a separate task;
* inspect public user-facing documentation only;
* avoid implementation code and internal tests;
* record every external document consulted;
* distinguish observed behavior from independently designed Akashi decisions; and
* avoid silently changing foundational APIs merely to match another project.

### Clean-room record

Create and maintain `docs/CLEAN_ROOM.md` so it records:

* every external document consulted;
* whether each document was a specification, integration guide, or user-facing behavioral reference;
* the allowed user-owned implementation materials consulted;
* every dependency introduced and why it does not violate this policy;
* confirmation that no prohibited implementation code was examined;
* confirmation that no competing PHP doctest documentation was examined during the MVP;
* any accidental exposure, including exactly what was seen and what mitigation was taken;
* which Rust or rustdoc behaviors influenced the roadmap or terminology; and
* which Akashi implementation decisions were independently derived from Yumemi and PHP ecosystem requirements.

If prohibited material is accidentally encountered, stop, record exactly what was seen and the mitigation taken, and
report the exposure before proceeding.

## Repository and compatibility constraints

Read every applicable `AGENTS.md` before modifying a repository.

Respect existing package metadata, code style, licensing, and contributor instructions. Do not make a new licensing decision.

Target:

* PHP 8.2 and later;
* PHPUnit 11.5 for the initial integration;
* current PHPStan 2.x versions used by Yumemi;
* strict types;
* static analysis at the repository’s normal maximum level;
* the coding standards already established in the repository.

Configure CI for PHP 8.2 through the newest supported version where practical.

Run all versions available in the local environment and configure the remaining matrix rather than pretending they were run.

Do not require PHP 8.4-only syntax or dependencies.

## Design philosophy

Official Cargo and rustdoc user-facing documentation may inform observable behavior, terminology, compatibility
considerations, and deferred features where they map cleanly to PHP. Rust implementation code, internal tests, and
implementation-oriented architecture material remain prohibited by the clean-room policy.

Do not mechanically reproduce Rust-specific idioms, architecture, syntax, or naming.

Prefer conventional PHP, Composer, PHPUnit, and PHPStan designs when they are:

* clearer;
* more ergonomic;
* easier to integrate into existing PHP projects;
* compatible with PHP’s runtime model;
* statically analyzable;
* sound and type-safe.

Where multiple designs are plausible, favor the design that is explicit, sound, statically analyzable, and type-safe rather than the one that most closely resembles Rust.

Rust-style names such as `should_panic`, `no_run`, and `compile_fail` are provisional descriptions of behavior, not mandated public API names. Choose PHP-idiomatic terminology when those features are eventually implemented.

Avoid stringly typed internal APIs where enums, immutable value objects, discriminated object types, or explicit interfaces would be safer.

Do not use PHPDoc generics as a substitute for a sound object model when a concrete type can reasonably represent the concept.

## Core design principle

Separate these concerns:

1. **Sources** discover documentation examples.
2. **Examples** represent extracted source code and metadata.
3. **Transforms** prepare examples without deciding how they are executed.
4. **Executors** run examples.
5. **Verifiers** enforce runtime, static-analysis, or other contracts.
6. **Integrations** expose results through PHPUnit, CLI tools, or future runners.

Do not build a monolithic Markdown runner.

A suitable central value object should contain at least:

* stable generated ID;
* human-readable label;
* original file path;
* start line;
* end line;
* language;
* unmodified source code;
* parsed directives or metadata;
* optional explicit marker ID;
* document-relative block ordinal.

The exact class names are your decision.

For the Markdown-driven MVP, these fields may describe one Markdown document directly. The longer-term source model
must not assume that the rendered documentation location is always the canonical code location, that code is always
physically embedded beside its prose, or that one physical line range remains sufficient after transformation. Preserve
a seam between the canonical code origin, any documentation reference or presentation location, transformed execution
source, and verifier diagnostics. Do not add speculative public types for those distinctions before an MVP use case
requires them.

Prefer:

* small immutable value objects;
* explicit interfaces;
* constructor validation;
* readonly state where appropriate;
* composition over inheritance;
* public APIs that prevent invalid states where reasonably possible.

Avoid:

* a service container;
* global mutable registries;
* loosely structured associative arrays across public boundaries;
* elaborate configuration frameworks;
* speculative abstractions unsupported by a current use case.

The same extracted example must be capable of being passed to multiple independent verifiers without reparsing the source document.

Every source and transformation must retain enough location information to map generated or executed PHP back to the
original maintained source. The MVP does not require a fully general source-map engine unless Yumemi needs one, but it
must not discard the information needed to add composed mappings later. Keep original example source separate from
transformed or generated source.

## Required MVP functionality

### 1. Markdown document discovery

Provide a programmatic Markdown source capable of reproducing Yumemi’s current document manifest:

* include the project-root `README.md`;
* recursively include Markdown files under `docs/pages/`;
* exclude `docs/pages/SUMMARY.md`;
* ignore non-Markdown files;
* return documents and examples in deterministic lexical order;
* fail clearly when configured roots do not exist;
* fail clearly when no PHP examples are found.

Do not hardcode that layout into the core abstraction. It must be expressible through configured files, directories, and exclusions.

Support the fenced PHP blocks actually present in Yumemi and Yumemi Apocrypha.

Use a sufficiently correct Markdown parser or purpose-built fence scanner so that fence-like text inside another fenced block is not interpreted as a separate example.

Preserve:

* code exactly as documented;
* original file location;
* starting line;
* ending line;
* block ordinal within the document;
* fence metadata needed by Akashi directives.

Support ordinary `php` fenced blocks with or without an explicit `<?php` opening tag.

Preserve Yumemi’s current deterministic implicit identity behavior where compatibility requires it, but retain explicit marker IDs separately.

Document the distinction between:

* an implicit generated example identity;
* an explicit author-assigned marker identity.

Generated identities should remain deterministic across runs when the relevant document structure has not changed.

### 2. Marked-example extraction

Replace the duplicated `MarkedCodeBlockExtractor` implementations and their CLI wrappers.

The existing syntax must continue to work without modifying current documentation:

```html
<!-- yumemi-example: selected-example -->
```

followed by a PHP fence.

Requirements:

* marker names must be configurable rather than permanently hardcoding `yumemi-example` into the generic core;
* IDs must follow the existing lowercase kebab-case rule;
* extraction must require exactly one matching PHP block;
* missing IDs must fail;
* duplicate IDs must fail;
* invalid IDs must fail before reading or searching the document;
* a marker not followed by a suitable PHP block must fail clearly;
* returned code must preserve its PHP opening tag;
* returned code must end with exactly one newline;
* expose both a programmatic selector and a CLI command;
* stdout must contain only extracted code on success;
* stderr must contain a useful error on failure;
* CLI usage errors and extraction failures must have stable, distinct nonzero exit statuses.

A target command may resemble:

```console
vendor/bin/akashi extract \
    --marker-name=yumemi-example \
    docs/pages/integrations.md \
    illuminate-cache-invalid
```

The exact CLI spelling may be refined, but update the Yumemi consumer scripts consistently.

Do not make the extractor depend on PHPUnit.

### 3. Runtime verification

Runtime examples must execute **in-process by default**.

Provide a PHPUnit integration that allows each extracted example to appear as an independently named PHPUnit test or data set with useful source information.

The in-process executor must reproduce Yumemi’s current guarantees:

* parse the example before execution;
* rewrite native `assert(EXPR)` calls into unconditional PHPUnit assertions so behavior does not depend on `zend.assertions`;
* register rewritten assertions with PHPUnit;
* preserve custom assertion descriptions where currently supported or reasonably possible;
* isolate declarations from other examples using a deterministic unique namespace or an equivalently safe mechanism;
* isolate top-level variables from `$GLOBALS`, such as by evaluating inside a closure;
* capture output without leaking it into PHPUnit;
* restore all output buffers correctly after success or failure;
* catch `Throwable`;
* report the original Markdown path, block identity, and source line on failure;
* map failures in generated or rewritten PHP back to the corresponding maintained Markdown line rather than reporting
  only an opaque temporary-file or generated-code location;
* ensure examples containing no assertions are not reported as risky PHPUnit tests;
* avoid collisions when two examples declare functions or classes with the same name;
* use the project’s normal Composer bootstrap.

Preserve user-authored imports and ordinary PHP name resolution as closely as practical.

Add explicit tests for:

* namespace-sensitive behavior;
* qualified and unqualified names;
* imported classes and functions;
* examples containing declarations;
* examples containing return statements or unsupported top-level control flow.

Do not silently skip an example that cannot be transformed safely. Produce a precise unsupported-example error containing its source location.

Keep transformed source available for debugging, but do not normally expose generated implementation details in user-facing output.

### 4. Opt-in separate-process execution

Implement a minimal separate-process executor as an alternative backend, but do **not** make it the default.

An individual example or configured group must be able to opt into separate-process execution through:

* programmatic configuration; and
* one documented Markdown directive.

Prefer an unobtrusive HTML comment immediately associated with the fence, for example:

```html
<!-- akashi: separate-process -->
```

The exact directive grammar may be refined, but keep it small, deterministic, and documented.

Do not use a Rust-specific directive name merely for familiarity.

The initial separate-process backend only needs to:

* use the current `PHP_BINARY`;
* load the configured Composer or project bootstrap file;
* execute the selected example;
* capture stdout;
* capture stderr;
* capture exit status;
* detect assertion failures;
* set startup options so native assertions execute reliably;
* report failures through the same high-level result abstraction as the in-process executor;
* prevent `exit()` or `die()` in the example from killing PHPUnit;
* map child-process parse and runtime locations back to the maintained Markdown source when PHP reports the temporary
  file;
* clean temporary files after success and failure.

Add a test proving separate-process execution actually uses another process, such as by comparing process IDs.

Advanced process configuration is deferred.

### 5. PHPStan documentation verification

Provide a reusable PHPStan integration that reproduces Yumemi’s current documentation contract.

Yumemi currently treats selected PHP fences as PHPStan fixtures.

Within those blocks:

```php
//! expected diagnostic substring
offendingCall();
```

means that PHPStan must report an error whose message or tip contains the supplied substring.

Required behavior:

* allow the consuming project to select PHPStan-relevant examples with a predicate or configured token list;
* support Yumemi’s current relevance tokens:

  * `unit_int<`
  * `unit_float<`
  * `Quantity<'`
  * `@yumemi-`
  * `//!`
* extract ordered `//!` expectations;
* analyze each example independently using the consumer’s real PHPStan container and configuration;
* permit a consuming `RuleTestCase` to provide the rule being tested;
* load Yumemi’s actual additional PHPStan configuration files;
* preserve the current need to make example-local declarations visible to PHP reflection before analysis;
* require the actual diagnostic count to equal the number of markers;
* match every expected substring against the combined PHPStan message and tip;
* require relevant examples without `//!` markers to analyze cleanly;
* preserve expectation order where the current harness depends on it;
* produce a useful report showing expected and actual diagnostics;
* map diagnostics from generated temporary files back to maintained Markdown lines; and
* clean temporary files even when analysis fails.

Keep PHPStan-specific code in an integration namespace or optional module.

Do not make the core example model depend on PHPStan classes.

The first integration may target `RuleTestCase` rather than every possible PHPStan entry point, but the design must leave a clear seam for a future general analyzer adapter.

Do not weaken exact error-count or substring assertions merely to simplify implementation.

### 6. PHPUnit integration

The consuming repository should need only a thin project-specific test class, factory, trait, or configuration object.

The framework should own generic behavior such as:

* Markdown discovery;
* data-provider construction;
* example labeling;
* marker parsing;
* directive parsing;
* runtime transformation;
* execution selection;
* output capture;
* expectation parsing;
* common failure reporting;
* temporary-file cleanup.

Yumemi should retain only genuinely project-specific choices such as:

* document roots;
* excluded documents;
* bootstrap path;
* PHPStan relevance predicate;
* PHPStan configuration files;
* PHPStan rule selection.

Do not generate permanent PHP test files unless there is a compelling technical reason.

In-process execution is the preferred path.

Avoid requiring consumers to subclass a large Akashi base class when a small composable fixture or trait is sufficient.

### 7. Akashi CLI

The MVP CLI only needs to provide functionality currently required by the migrations.

Required command:

* marked-example extraction.

The CLI architecture should permit later commands without requiring a rewrite, but do not implement a full standalone doctest runner in the MVP.

Requirements:

* stable exit codes;
* no decorative output on stdout when stdout is used as generated source;
* actionable stderr diagnostics;
* `--help`;
* `--version`;
* deterministic behavior;
* no dependency on an application framework.

### 8. Migration of Yumemi

When a local checkout is available, migrate `jbboehr/yumemi.php` to Akashi using a Composer path repository during development.

Replace or substantially reduce the responsibilities currently held by:

* `tests/Documentation/MarkdownExamples.php`
* `tests/Documentation/DocumentationExamplesTest.php`
* `tests/Documentation/DocumentationPhpStanExamplesTest.php`
* `tests/Documentation/MarkdownExamplesTest.php`
* `tests/Documentation/MarkedCodeBlockExtractor.php`
* `tests/Documentation/extract-markdown-example.php`

A thin Yumemi-specific PHPStan test subclass or configuration file is acceptable and expected.

Update `tests/Consumer/run` so documentation examples are extracted through `vendor/bin/akashi` rather than the local duplicated script.

Acceptance requirements:

* every existing public PHP fence still executes;
* native assertions remain unconditional;
* duplicate declarations remain isolated;
* every current `//!` diagnostic expectation remains enforced;
* clean PHPStan examples remain clean;
* marked consumer examples still produce byte-equivalent PHP fixture files;
* existing PHPUnit suites remain green;
* relevant consumer suites remain green;
* support for PHP 8.2 is not weakened.

Do not rewrite public documentation merely to accommodate Akashi unless an existing example exposes a genuine bug or ambiguity.

Before deleting or replacing code, inventory its behavior in `docs/MIGRATING_YUMEMI.md`.

### 9. Migration of Yumemi Apocrypha

When a local checkout is available, migrate `jbboehr/yumemi-apocrypha.php`.

Replace:

* its duplicated `MarkedCodeBlockExtractor`;
* its extractor test;
* its `extract-markdown-example.php` wrapper.

Update `tests/Consumer/run` to use the Akashi CLI for the marked examples currently extracted from:

* `README.md`;
* `docs/pages/getting-started.md`;
* `docs/pages/integrations.md`.

Preserve:

* current marker IDs;
* exact generated PHP contents;
* current diagnostic substring checks;
* both source-package and archive-package consumer workflows;
* Laravel or Illuminate version-matrix behavior.

Do not make Akashi responsible for:

* Composer archive validation;
* package installation validation;
* the overall external consumer harness;
* Yumemi-specific unit semantics.

Before deleting or replacing code, inventory its behavior in the migration documentation.

## Explicit non-goal

The duplicated `GeneratedDocumentationLinkChecker` is not part of the initial Akashi extraction.

Leave generated HTML link checking where it is for now.

Mention a possible future generic documentation-validation module in the roadmap, but do not broaden this task into an mdBook, HTML, or link-validation framework.

## Deferred features and roadmap

Create `docs/ROADMAP.md`.

The initial Yumemi-driven implementation treated the capabilities below as deferred unless current consumer behavior
required them. Subsequent work must preserve the explicit implementation status and remaining boundaries recorded in
this section rather than assuming that the entire original list is still unimplemented.

Do not inspect prohibited prior-art material to determine how these features are implemented. Official Cargo and rustdoc
user-facing documentation may clarify observable behavior and terminology, but competing PHP doctest documentation and
all prohibited implementation material remain outside the MVP clean-room boundary.

### Additional sources and maintainable authoring

* inline PHPDoc-comment examples — implemented post-MVP;
* external canonical PHP example files, including stable named regions — implemented post-MVP;
* documentation references to canonical examples — implemented post-MVP;
* optional synchronized inline presentations of external canonical examples — read-only parsing and comparison
  implemented post-MVP; CLI reporting and rewriting deferred;
* declaration-aware attachment metadata for examples on classes, methods, functions, and interfaces;
* attribute-based examples;
* arbitrary source adapters;
* non-Markdown documentation formats.

Future source support must address maintainability as well as extraction. It must not imply that PHPDoc-hosted examples
always have to be maintained literally inside documentation comments.

### PHPDoc example maintainability

Implementation status after the initial Yumemi-driven MVP: inline PHPDoc fenced examples and references to external
canonical PHP files or named regions are implemented through the mixed `DocumentationSource`; read-only parsing and
comparison of synchronized presentations are also implemented. Synchronization CLI/reporting and rewriting,
formatting, and hidden support code remain deferred. The requirements below preserve the design boundary and remaining
sequence.

> Use inline examples for short, local demonstrations. Use ordinary external PHP files as the canonical source for
> substantial examples.

The goal is to keep examples easy to edit in an IDE, format with normal PHP formatters, analyze with PHPStan or other
tools, execute directly, reuse in several documentation locations, synchronize without manual copy-and-paste, and
trace to their maintained source lines when verification fails.

The post-MVP design has three authoring modes; the first two are implemented.

#### 1. Inline examples

Short examples may remain directly inside a PHPDoc fenced block:

````php
/**
 * ```php
 * $result = convert(1, 'meter', 'centimeter');
 * assert($result === 100);
 * ```
 */
````

This mode suits examples that are short, tightly coupled to one symbol, understandable without significant setup, and
unlikely to be reused. Akashi now recognizes CommonMark PHP fences on the interior lines of selected PHPDoc comments;
content beside the opening and closing comment delimiters is not interpreted as Markdown.

#### 2. Referenced canonical examples

Substantial examples should normally live in ordinary valid PHP files, optionally divided into stable named regions.
The default reference syntax is:

```php
/**
 * @akashi-example examples/conversion.php#basic-conversion
 */
```

with a canonical file such as:

```php
<?php

// akashi-region: basic-conversion
$result = convert(1, 'meter', 'centimeter');
assert($result === 100);
// akashi-region-end: basic-conversion
```

Reference targets are project-root-relative case-sensitive `.php` paths with an optional lowercase kebab-case region.
`DocumentationSource::withPhpDocReferenceTags()` can replace the default tag with `example` or accept both during a
migration. The implemented design principles are:

* the external PHP file is the source of truth;
* it remains ordinary valid PHP usable by IDEs, formatters, the PHP runtime, and static analyzers;
* stable named regions are preferable to fragile line-number ranges, which shift after unrelated edits;
* the same whole file or named region may be referenced from more than one documentation location;
* missing, malformed, nested, overlapping, and duplicate named regions fail with clear diagnostics; and
* a documentation reference has a presentation location distinct from the canonical code origin, and both remain
  traceable.

PHPDocumentor's `@example` spelling can be configured as one frontend, but Akashi's source and example model remains
independent of any documentation generator and does not adopt line-number ranges or trailing descriptions.

#### 3. Synchronized inline examples

An optional compatibility mode supports renderers that require code to be physically embedded in PHPDoc or Markdown.
The implemented read-only synchronization syntax is:

````php
/**
 * <!-- akashi-sync: examples/conversion.php#basic-conversion -->
 *
 * ```php
 * $result = convert(1, 'meter', 'centimeter');
 * assert($result === 100);
 * ```
 *
 * <!-- akashi-sync-end -->
 */
````

Possible commands may resemble:

```console
vendor/bin/akashi sync
vendor/bin/akashi sync --check
```

The comments and explicitly closed PHP fence must remain consecutive Markdown blocks; optional blank separator lines are
accepted for formatter compatibility, but other intervening content is not. The target uses the same project-relative
`.php` path and optional named-region grammar as PHPDoc references. Line endings compare as LF and a missing final
newline is normalized to one LF; additional blank lines inside the fence and logical code indentation remain significant.
Markdown indentation and conventional PHPDoc leading `*` decoration are containers rather than code. PHP opening tags
remain canonical code and are neither inserted nor removed. The typed region keeps logical embedded code separate from
raw source spans into the maintained presentation.

The read-only parser and typed mismatch model are implemented. The command names remain provisional: no synchronization
CLI or write mode exists yet. The external file or named region remains canonical. A future check-only mode should fail
CI when the embedded copy is stale. A write mode may update only the embedded copy; it must not silently alter unrelated
prose or comment formatting. Malformed synchronization regions fail instead of being guessed at.

Referenced examples are generally preferable. Synchronization is a compatibility mechanism for renderers that cannot
include external content directly, not the primary authoring model.

### Source-location mapping

Every example source and each transformation must preserve enough information to map:

```text
generated or executed PHP line
            ↓
original Markdown, PHPDoc, or external example-file line
```

This mapping is required for parse errors, rewritten assertion failures, runtime exceptions, PHPStan diagnostics,
formatter errors and diffs, synchronized examples, hidden support code, and future compiler or linter adapters. When an
original maintained source location is available, Akashi must not report only an opaque temporary-file location.

Mappings may need to compose across extraction, support-code handling, PHP transformation, and temporary-file
generation. The model now represents inline Markdown and PHPDoc fences, external whole PHP files, named regions, and
read-only synchronized presentations. It conceptually distinguishes:

* canonical code origin;
* documentation reference or presentation location;
* transformed execution source; and
* verifier diagnostics.

One referenced example may have several PHPDoc presentation locations that reuse one canonical source. A single source
line range is not always sufficient after transformations. The implemented typed source variants preserve that seam
without introducing a general plugin or source-map framework.

### Hidden support code

Support code that participates in execution without being shown to readers remains deferred. Rustdoc's documented
hidden-line behavior is acceptable as high-level behavioral inspiration, but Akashi must not assume Rust's `# ` syntax:

* `#` is already a PHP comment marker;
* preprocessing hidden lines could confuse PHP formatters and IDEs;
* PHPDoc and Markdown renderers may treat comments differently;
* transformed hidden lines complicate source mapping; and
* explicit setup references may be more PHP-idiomatic.

Potential approaches include referenced setup files or named regions, an explicit Akashi directive, separate visible
and support-code sections, source-level annotations understood by Akashi, or renderer-specific integrations. Do not
select a syntax during the MVP.

> Prefer an explicit, PHP-idiomatic design that remains compatible with PHP parsers, formatters, IDEs, documentation
> renderers, and static analyzers.

### Formatter integration

Formatter support also remains deferred. Possible commands may resemble:

```console
vendor/bin/akashi format --check
vendor/bin/akashi format --write
```

The final names are not mandated. Check-only support should precede automatic rewriting. External examples should
normally be formatted directly with existing PHP tooling. Inline examples may need extraction into temporary valid PHP
before checking. Automatic docblock rewriting is riskier because indentation, leading `*` characters, Markdown fences,
opening tags, and prose boundaries must be preserved.

Akashi should integrate with a configured formatter rather than become a PHP formatter. Formatter output and diffs must
map back to the source developers actually maintain.

### Post-MVP authoring sequence

Place this work after the current Markdown/Yumemi MVP and before broad plugin or runner expansion. The sequence is:

1. PHPDoc fenced examples — implemented post-MVP.
2. External canonical PHP examples and named regions — implemented post-MVP.
3. Source-location mapping improvements.
4. Check-only synchronization — read-only library parsing and mismatch model implemented; CLI reporting deferred.
5. Check-only formatter integration.
6. Optional write-mode synchronization and formatting.
7. Hidden support-code semantics.
8. Documentation-renderer integrations.

This ordering is guidance, not a commitment to release numbers. Rustdoc's public documentation may provide behavioral
precedent for hidden setup lines, executable examples in documentation comments, inclusion of documentation from
external files, and examples that are checked without ordinary execution. It does not prescribe Akashi's API or syntax,
and the clean-room prohibition on rustdoc implementation material and competing PHP doctest documentation remains
unchanged.

All of these capabilities were outside the initial Yumemi-driven MVP. Inline PHPDoc extraction, external-example
references, and named-region parsing are now implemented as post-MVP work. Synchronization commands, formatter commands,
hidden support code, documentation-renderer plugins, and automatic docblock rewriting remain deferred. Do not add
dependencies, placeholder classes, or speculative interfaces for them unless an already-required abstraction naturally
supports the future behavior.

### Additional example semantics

* hidden setup or support lines, with syntax explicitly undecided;
* hidden assertion expressions;
* expected stdout;
* expected stderr;
* inline expected values;
* richer expected-exception contracts beyond the implemented in-process throwable type, including message, code, and
  separate-process support;
* panic-style or expected-failure runtime tests;
* expected parse errors;
* expected static-analysis failures as a generalized feature;
* compile-failure examples;
* ignored examples;
* parse-only or do-not-run examples;
* platform-conditional examples;
* PHP-version-conditional examples.

### Execution features

* setup blocks;
* teardown blocks;
* shared-state groups or sessions;
* suite-level bootstraps beyond MVP bootstrap support;
* configurable PHP binaries;
* PHP-version matrices;
* custom INI settings;
* environment variables;
* working-directory overrides;
* timeouts;
* memory limits;
* signals;
* crash handling;
* parallel execution;
* process pools;
* sandboxing.

### Verification and analyzer features

* general verifier or plugin registration;
* declaratively applying multiple verifiers to the same example;
* PHPStan outside `RuleTestCase`;
* Psalm;
* syntax-only verification;
* arbitrary compiler or linter adapters;
* analyzer-specific expected-diagnostic formats;
* cross-version diagnostic normalization.

The internal model must not prevent an example from being reused by multiple verifiers, but do not build a speculative plugin framework beyond what the MVP needs.

### Runner and reporting features

* standalone full-suite CLI runner;
* Pest integration;
* PHPUnit 12 support;
* JUnit output;
* machine-readable JSON output;
* generated or cached PHPUnit test classes;
* watch mode;
* filtering by path, marker, tag, executor, or verifier;
* snapshots;
* update mode;
* rich diffs;
* CI annotations.

### Extensibility

* custom fenced-language handlers;
* custom directives;
* custom transforms;
* custom execution backends;
* custom result reporters;
* generic generated-document link validation.

For each deferred area, describe the architectural seam that should support it later.

Do not implement speculative abstractions with no present use. Document the intended seam instead.

## Testing requirements

Build the package test-first around focused fixtures.

At minimum, test:

### Markdown discovery and parsing

* deterministic recursive Markdown discovery;
* excluded files;
* missing configured files;
* missing configured directories;
* CRLF input;
* LF input;
* multiple PHP fences;
* non-PHP fences;
* nested or longer Markdown fences;
* fence-like text inside another fence;
* optional `<?php` opening tags;
* correct source line tracking;
* correct ending line tracking;
* deterministic block ordinals;
* deterministic generated IDs;
* empty corpus failure.

### Marked extraction

* valid explicit marker IDs;
* invalid explicit marker IDs;
* duplicate explicit marker IDs;
* missing marker IDs;
* a marker not followed by a PHP block;
* exact marked-example extraction;
* preservation of `<?php`;
* stable trailing-newline behavior;
* configurable marker names;
* CLI success output;
* CLI usage failure;
* CLI extraction failure;
* stable CLI exit statuses.

### In-process execution

* two examples declaring the same function name;
* two examples declaring the same class name;
* top-level variable isolation;
* unconditional rewritten `assert()`;
* an assertion with a custom description;
* a failing rewritten assertion;
* examples without assertions;
* output capture;
* nested output buffers;
* thrown runtime exceptions;
* malformed PHP;
* imports and namespace-sensitive names;
* unsupported namespace constructs;
* useful source-location reporting;
* cleanup and output-buffer restoration after failure.

### Separate-process execution

* opt-in separate-process directive parsing;
* programmatic process selection;
* proof that another process was used;
* bootstrap loading;
* stdout capture;
* stderr capture;
* nonzero exit status;
* reliable native assertions;
* `exit()` in separate-process mode;
* temporary-file cleanup.

### PHPStan verification

* relevance predicate selection;
* current Yumemi relevance tokens;
* clean PHPStan examples;
* expected diagnostic substrings;
* diagnostic tips as well as messages;
* exact diagnostic counts;
* too many diagnostics;
* too few diagnostics;
* missing expected substring;
* multiple ordered expectations;
* relevant examples without markers;
* temporary-file cleanup after verifier failure;
* visibility of example-local declarations where required.

### Integration fixtures

Add integration fixtures representing both Yumemi and Yumemi Apocrypha rather than relying only on synthetic unit tests.

Where practical, use copies or minimal reductions of real current examples from those user-owned repositories.

## Validation commands

Run, as applicable:

* Akashi PHPUnit suite;
* Akashi PHPStan analysis;
* Akashi coding-style checks;
* mutation tests when configured and practical;
* Yumemi’s normal test suite;
* Yumemi’s documentation tests;
* Yumemi’s relevant consumer suites;
* Yumemi Apocrypha’s normal test suite;
* Yumemi Apocrypha’s relevant consumer suites.

Do not claim a suite passed unless it was actually executed.

Report commands that could not be run and the concrete reason.

Do not weaken or delete an existing consumer-level test merely because a new Akashi unit test covers similar code.

## Documentation deliverables

Produce:

* `README.md`
* `docs/ARCHITECTURE.md`
* `docs/CLEAN_ROOM.md`
* `docs/ROADMAP.md`
* `docs/MIGRATING_YUMEMI.md`
* API documentation for the source, example, executor, verifier, and PHPUnit integration
* documentation for explicit marker directives
* documentation for the separate-process directive
* documentation of known in-process limitations

The README should include:

* the full project title:

  * `Probatio Verborum Viventium『証』〜ＡＫＡＳＨＩ〜`
* installation;
* a minimal Markdown example;
* a minimal PHPUnit integration example;
* marked-example extraction;
* opt-in separate-process execution;
* PHP version support.

The README should emphasize:

* in-process execution is the default;
* separate-process execution is opt-in;
* the same extracted example can be reused by runtime and static-analysis verification;
* PHP 8.2 remains supported;
* Akashi prefers PHP-idiomatic, sound, and type-safe designs rather than mechanically reproducing Rust doctests.

Keep the README practical. Put deeper architecture and roadmap material under `docs/`.

## Implementation discipline

* Begin by inventorying the relevant code in Yumemi and Yumemi Apocrypha.
* Write the inventory into the migration document before deleting anything.
* Read all applicable repository instructions.
* Keep the Akashi core generic.
* No dimensional-analysis or Yumemi-specific concepts belong in the core.
* Prefer programmatic PHP configuration for the MVP.
* Preserve original source information throughout the pipeline.
* Preserve original unmodified example source separately from transformed source.
* Avoid global mutable registries.
* Avoid public APIs built primarily from unvalidated arrays.
* Avoid silently swallowing unsupported examples.
* Do not implement deferred features opportunistically.
* Keep commits small and coherent where repository workflow permits.
* Do not replace working project-specific consumer tests with weaker unit tests.
* Do not weaken exact PHPStan diagnostic assertions.
* Do not change the default execution mode to separate-process isolation.
* Do not inspect prohibited prior-art implementations or competing PHP doctest documentation during the MVP.
* Consult Cargo and rustdoc only through allowed official user-facing documentation, never implementation material.
* Do not claim clean-room independence without recording every external document and allowed implementation material
  actually consulted.
* Prefer PHP idioms over Rust idioms where PHP offers a clearer, sounder, or more type-safe design.
* Do not overengineer a generalized plugin system during the MVP.
* Do not broaden Akashi into a documentation generator.

## Suggested implementation order

Use this as guidance rather than an inflexible command sequence:

1. Inventory Yumemi and Yumemi Apocrypha behavior.
2. Establish package skeleton, CI, coding standards, and clean-room record.
3. Implement immutable document and example models.
4. Implement deterministic Markdown discovery and fenced-block extraction.
5. Implement configurable explicit marker extraction.
6. Implement the extraction CLI.
7. Implement source transformation for in-process execution.
8. Implement the default in-process executor.
9. Implement PHPUnit integration.
10. Implement minimal opt-in separate-process execution.
11. Implement the PHPStan `RuleTestCase` integration.
12. Add real-world compatibility fixtures.
13. Migrate Yumemi.
14. Migrate Yumemi Apocrypha.
15. Complete architecture, migration, and roadmap documentation.
16. Run all available validation suites.
17. Report any remaining gaps honestly.

Do not begin by designing every deferred feature.

## Completion criteria

The initial task is complete when:

1. The standalone `jbboehr/akashi` package exists with PHP 8.2 support.
2. It has a tested example-source model.
3. Markdown examples execute in-process by default.
4. Individual examples can opt into separate-process execution.
5. Native `assert()` calls become unconditional PHPUnit assertions in in-process mode.
6. Duplicate declarations and top-level variables are safely isolated.
7. Yumemi’s PHPStan `//!` contract is supported.
8. Configurable marked-example extraction replaces both duplicated implementations.
9. Yumemi uses Akashi without losing documentation coverage.
10. Yumemi Apocrypha uses Akashi for its marked consumer fixtures.
11. Both projects retain PHP 8.2 compatibility.
12. Existing relevant consumer workflows remain intact.
13. Deferred prior-art features are documented but not implemented.
14. `docs/CLEAN_ROOM.md` confirms that prohibited implementation code and competing PHP doctest documentation were not
    consulted during the MVP.
15. All executed tests and remaining gaps are reported honestly.
16. The architecture uses PHP-idiomatic, sound, and type-safe designs rather than mechanically copying Rust.

At the end, provide a summary containing:

* architecture implemented;
* important public API decisions;
* files added and modified;
* behavior migrated from Yumemi;
* behavior migrated from Yumemi Apocrypha;
* commands actually run;
* test results;
* compatibility limitations;
* deferred work;
* unresolved design questions;
* confirmation that no prohibited implementation code or competing PHP doctest documentation was examined during the
  MVP.
