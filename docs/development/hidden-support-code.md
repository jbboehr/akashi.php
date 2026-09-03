# Hidden Support Code Design

Status: accepted design; source resolution implemented, public model and execution not implemented.

Hidden support code lets an example use per-example setup without showing that setup to readers. The first
implementation will reference an ordinary PHP file or one stable named region within it. Akashi will not adopt
Rustdoc-style hidden-line preprocessing or make code inside a visible fence disappear.

## Consumer model

Primary caller: a PHP library author who tests Markdown or PHPDoc examples through PHPUnit and may reuse the same corpus
with PHPStan.

Job: keep the reader-facing example short while executing necessary declarations, fixtures, or input setup.

Context: PHP 8.1+, PHPUnit 10.5 or 11.5, optional PHPStan verification, and source-aware failures.

Likely approach: copy an authoring example, edit a normal PHP support file in an IDE, and rely on Akashi to resolve and
compose it.

## Authoring contract

An example may declare one `setup` property in the existing `akashi:` metadata grammar. Its value is a
project-root-relative PHP path with an optional stable named region:

````markdown
<!-- akashi: setup=examples/conversion.php#conversion-input -->

```php
$result = convert($input, $from, $to);

assert($result === 100);
```
````

The canonical support file remains ordinary valid PHP:

```php
<?php

// akashi-region: conversion-input
$input = 1;
$from = 'meter';
$to = 'centimeter';
// akashi-region-end: conversion-input
```

The same metadata works before an inline PHPDoc fence. A referenced canonical example may place the equivalent
`// akashi: setup=examples/conversion.php#conversion-input` comment inside its selected code. Paths are always resolved
from the configured project root, not from the containing document.

The property composes with the existing runtime metadata rather than creating another directive dialect:

````php
/**
 * <!-- akashi: setup=examples/service.php#failing-request, separate-process -->
 * <!-- akashi: expect-exception=DomainException -->
 *
 * ```php
 * $service->handle($request);
 * ```
 */
````

A dedicated file is the shortest form:

````markdown
<!-- akashi: setup=examples/conversion-setup.php -->

```php
$result = convert($input, $from, $to);
```
````

Use a named region when one ordinary PHP file contains several reusable setups. Whole files and selected slices must
contain valid PHP and are subject to the same transform and safety rules as visible code. A selected slice must contain
one or more complete top-level statements in the full-file AST. Line-number ranges remain unsupported.

Only one setup source may be attached to an example. Repeating `setup` is a duplicate keyed property and fails at the
second declaration:

````markdown
<!-- akashi: setup=examples/setup.php#first -->
<!-- akashi: setup=examples/setup.php#second -->

```php
// Rejected before execution.
```
````

Authors who need several inputs can collect them in one file or named region. This avoids ordered lists, recursive
includes, and an additional setup-composition language. A setup source cannot itself declare another setup reference.

This shape keeps the common call to one discoverable property, reuses the existing path and region vocabulary, and
leaves advanced backend and outcome choices orthogonal. `setup` is explicitly per-example; it is not a suite fixture or
an alias for the project bootstrap.

## Semantic contract

Akashi treats setup and reader-facing code as two maintained sources in one example:

```text
external PHP setup source ─┐
                           ├─> prepared execution unit ─> diagnostic origin
visible example body ──────┘
```

The following rules apply equally to in-process and separate-process execution:

- Setup runs once for each example invocation, immediately before the visible body and in the same variable scope.
- Setup and visible code are parsed and prepared as separate source segments under the same transformation and safety
  policies, then executed as one logical invocation. In-process setup receives no escape from the normal safety
  validator.
- Runtime exception expectations apply only to the visible body. A setup exception always fails the example and points
  to the support file.
- Setup must not write to stdout. Captured setup output is failure evidence; it cannot satisfy the visible example's
  expected-output contract. Stderr retains the selected backend's existing diagnostic behavior.
- A skipped example resolves and validates its setup reference during discovery but executes neither source.
- A compile-only example parses and validates both source segments but executes neither source.
- PHPStan verification analyzes the setup together with the visible body. Unexpected diagnostics in setup fail at the
  support-file line; expectation annotations inside setup are not accepted initially.
- The project bootstrap remains separate. It is process configuration loaded according to the existing runtime rules;
  setup is example-owned code run for each invocation.

A named setup region must correspond to one or more complete statements in the full canonical file AST. Akashi must
validate that boundary from the full file rather than reparsing an arbitrary text slice as a program. Regions inside a
class, function, namespace block, or other context-dependent construct are rejected for setup use even when they remain
valid as ordinary external PHPStan fixtures. A whole-file setup retains its own file-level PHP semantics. Setup and body
are separate compilation segments within one logical invocation: they share runtime variables, but `namespace`, `use`,
and `declare` in one segment do not silently change the other segment.

## Source and public model

The canonical `Example` model should gain one optional immutable support value rather than merging hidden bytes into
`Example::code`. A proposed public shape is:

```php
final class SetupReferenceLocation
{
    /** @param positive-int $line */
    public function __construct(
        public readonly Document $document,
        public readonly int $line,
        public readonly SourceSpan $span,
    ) {}
}

final class ExampleSetup
{
    public function __construct(
        public readonly CodeOrigin $origin,
        public readonly ExampleCode $code,
        public readonly ?RegionName $region,
        public readonly SetupReferenceLocation $reference,
    ) {}
}

final class Example
{
    public readonly ?ExampleSetup $setup;
}
```

The eventual constructor parameter belongs at the end with a `null` default. The exact declaration remains subject to
implementation review, but these invariants do not:

- the visible `Example::code` stays unchanged;
- the support origin and support bytes remain independently inspectable;
- the authored setup-property location remains distinct from its resolved target;
- one support source may be reused by any number of examples without becoming a separate corpus example; and
- generated preparation never becomes the maintained representation of either source.

The current `SourceMap` assumes one document path for every mapped generated line. Before execution support ships, its
internal representation must give every generated line a typed `Setup`, `Body`, or `Harness` segment and an optional
maintained coordinate. `Harness` represents synthetic preparation lines with no authored counterpart. Every public
failure evidence path must retain the segment instead of requiring message parsing. A failure must report the actual
maintained path and line when available while retaining the visible example ID and label as context. It must not
collapse both sources to the documentation fence or report only a temporary file.

`ExampleExecutionSegment` should be stable typed evidence wherever direct execution failures are public. The concrete
mapping entry remains internal, but the required shape is conceptually:

```php
enum ExampleExecutionSegment
{
    case Setup;
    case Body;
    case Harness;
}

final class MappedSourceLine
{
    /** @param positive-int|null $line */
    public function __construct(
        public readonly ExampleExecutionSegment $segment,
        public readonly ?DocumentPath $path,
        public readonly ?int $line,
    ) {}
}

final class ExecutionFailed
{
    public readonly ExampleExecutionSegment $segment;
}
```

The setup reference line and span must identify the exact authored metadata line. A mapped path and line are both
present for setup and body segments and both absent for harness segments; mixed states are invalid. Setup exceptions,
forbidden setup stdout, and body failures therefore remain distinguishable to PHPUnit and direct result consumers.

## Integration boundaries

Formatting remains source-oriented. The visible inline fence uses Akashi's configured formatter integration; the
external setup file uses ordinary project PHP formatting. Akashi does not copy setup into the fence or rewrite the
canonical support region.

Synchronization remains a presentation mechanism for visible example code. It does not synchronize setup into a
presentation.

Named extraction remains lexical and returns the visible authored PHP exactly as it does for other runtime metadata. It
does not include, execute, or transform setup. Documentation must state this directly so callers do not mistake ordinary
extraction for a standalone materialization. A future explicit materialization mode may combine sources without changing
the existing extraction contract.

## Error model

Discovery rejects a missing, unreadable, out-of-project, symlink-escaping, malformed, empty, overlapping, or contextual
setup target through the existing catchable source-exception boundary. Diagnostics name both the typed setup-reference
location and target where useful.

Preparation and execution distinguish setup failures from visible-body failures without requiring consumers to parse
message text. PHPUnit reporting should lead with the maintained failing location and identify it as setup for the
visible example. Cleanup and infrastructure failures keep their existing meanings.

## Implementation sequence

Each slice must be working and reviewable without advertising incomplete behavior:

1. **Implemented:** resolve setup targets internally as whole files or named regions and validate selected boundaries
   against the complete file AST. The metadata grammar does not expose the dormant `setup` property yet.
2. Add the immutable support value to `Example` and preserve the typed metadata reference, canonical support origin, and
   optional region.
3. Generalize internal generated-line mappings to typed setup, body, and harness segments with optional maintained
   coordinates while retaining current single-origin behavior.
4. Prepare setup and visible statements as separate segments with an explicit phase boundary and shared runtime variable
   scope; implement compile-only, safety, exception, stream, and source-reporting semantics for both execution backends.
5. Include setup in PHPUnit and PHPStan verification, with focused cross-backend and multi-origin tests.
6. Confirm setup-backed named extraction retains the visible authored source and that formatter and synchronization
   behavior remains source-oriented.
7. Publish authoring, directive, compatibility, API, and changelog documentation only after the complete supported path
   passes the normal compatibility matrix.

No new parser, formatter, renderer plugin, or general transform registry is required. The implementation should reuse
the current metadata grammar, project-root path rules, named-region scanner, PHP parser, fixed transformation pipeline,
and execution backends.

## Acceptance criteria

- Happy-path Markdown and PHPDoc examples share variables from one external whole-file or named-region setup.
- One setup source can support several examples without duplicate execution or identity conflicts.
- Missing, duplicate, recursive, overlapping, contextual, and unsafe setup references fail before visible execution.
- Setup exceptions and output cannot satisfy visible expected-exception or expected-output contracts.
- Runtime, parse, assertion, and PHPStan diagnostics expose a typed segment and identify the correct setup or visible
  maintained line when one exists.
- Skip and compile-only preserve their documented precedence and non-execution guarantees.
- In-process and separate-process backends expose the same setup ordering, scope, exception, and stdout semantics.
- Existing examples without setup preserve their model, source mapping, extraction, formatting, and execution behavior.

## Explicitly deferred

- Rust-style inline hidden lines or any other line-hiding preprocessing;
- several ordered setup references per example;
- inline hidden assertions;
- recursive setup composition;
- setup teardown or suite-level lifecycle hooks;
- renderer-specific inclusion; and
- an extraction mode that materializes setup and visible code into one fixture.

A separate future authoring mode may let one canonical PHP file execute as a whole while an unnamed delimited window
identifies only the code presented to readers. That is a presentation boundary, not an anonymous setup selection. Its
delimiters and its interaction with source mapping, expected outcomes, extraction, and synchronization remain unsettled;
the initial hidden-support implementation does not include it.
