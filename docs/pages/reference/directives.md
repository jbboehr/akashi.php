# Example Metadata

<figure class="logion" data-logion="AWC 4:37">
<div class="logion-text" data-nosnippet>
<blockquote>
<p>A dancer rehearsed her falls as carefully as her leaps. When a stage board split, she descended without injury and
guided another performer down. The audience praised her grace; she thanked the hours spent learning the ground. Wisdom
prepares dignity for the moment it cannot remain upright.</p>
</blockquote>
<p class="logion-citation">— <cite>Acts of the Western Court 4:37</cite></p>
</div>
<img src="../images/logia/AWC-4_37.webp" alt="A practiced dancer safely guiding another performer down through a split stage" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

Akashi uses one small metadata grammar for an example's stable identity, runtime disposition, execution mode, and
expected runtime behavior. The normal authoring form is a tokenized PHP line comment inside a fence or referenced
canonical PHP file:

```php
// akashi: example=invalid-input, expect-exception=RuntimeException
// akashi: expect-exception-message="invalid documentation input", expect-exception-code=73

throw new RuntimeException('invalid documentation input', 73);
```

Use an associated HTML comment when the metadata should remain outside the maintained or extracted PHP:

````markdown
<!-- akashi: example=isolated-greeting, separate-process -->

```php
echo "Hello!\n";
```
````

## Grammar

Each comment contains a comma-separated list of flags and keyed properties:

```text
// akashi: flag, key=value, key="quoted value"
<!-- akashi: flag, key=value, key="quoted value" -->
```

Whitespace around commas and `=` is ignored. Unquoted values are nonempty single tokens. Double-quoted values use JSON
string escaping and may contain spaces, commas, or `=`. Property names and flags are case-sensitive lowercase
kebab-case. Empty clauses, unknown properties, values on flags, and missing keyed values fail with the maintained source
line. Keyed values must be nonempty except that `expect-output=""` explicitly requires silent stdout.

The flags are:

| Flag               | Meaning                                                                   |
| ------------------ | ------------------------------------------------------------------------- |
| `skip`             | Ask PHPUnit to report the example as skipped.                             |
| `compile-only`     | Parse the example for PHPUnit without executing it.                       |
| `separate-process` | Select child-process execution instead of the default in-process backend. |

The keyed properties are:

| Property                   | Value                                                              |
| -------------------------- | ------------------------------------------------------------------ |
| `example`                  | A lowercase kebab-case identity used by selection and extraction.  |
| `expect-exception`         | A global PHP throwable class or interface name.                    |
| `expect-exception-message` | A nonempty, case-sensitive message substring.                      |
| `expect-exception-code`    | A signed base-10 integer in the running PHP build's integer range. |
| `expect-output`            | The exact stdout bytes required from runtime execution.            |

Adjacent HTML comments are merged, so these forms are equivalent:

```markdown
<!-- akashi: example=conversion-basic, separate-process -->
```

```markdown
<!-- akashi: example=conversion-basic -->
<!-- akashi: separate-process -->
```

HTML and inline PHP metadata associated with the same fence are also merged. Every property may appear at most once
across the complete example; even repeated flags fail rather than relying on precedence. Message and code constraints
require `expect-exception` on that example.

## Association Rules

Inline metadata may appear anywhere as an actual PHP line comment and applies to the whole example. Place an
expected-exception comment immediately before the operation expected to throw when that makes the example easier to
read; Akashi does not infer or enforce control-flow order. Recognition uses PHP comment tokens, so matching text inside
strings or heredocs is not metadata. The comment remains part of the ordinary PHP source, so readers, IDEs, formatters,
static analyzers, direct execution, and named extraction all see it unchanged.

Place HTML metadata immediately before a fenced PHP block when the PHP should not contain the metadata. Blank lines and
adjacent Akashi metadata comments are allowed:

````markdown
<!-- akashi: example=isolated-greeting, separate-process -->

```php
namespace DocumentationExample;

echo "Hello!\n";
```
````

Prose or an unrelated CommonMark block breaks the association. Orphaned metadata and metadata targeting non-PHP fences
fail during extraction with the comment's source location.

Inside PHPDoc, retain the normal leading `*` on each authored line. Akashi removes the docblock decoration before
applying the same association rules, and metadata never crosses from one PHPDoc comment into another:

````php
/**
 * <!-- akashi: separate-process -->
 *
 * ```php
 * exit(0);
 * ```
 */
````

Metadata is deliberately not encoded in the fence info string; ordinary `php` language tags remain readable to renderers
and syntax highlighters.

Use the HTML form when surrounding prose already establishes the behavior or an extracted consumer fixture should not
contain Akashi metadata. External whole-file and named-region examples use inline comments because their canonical code
is not physically adjacent to the PHPDoc reference.

## Compatibility and Structural Syntax

Legacy one-property directives remain accepted, including `<!-- akashi: expect-exception RuntimeException -->` and
`// akashi: expect-exception-message invalid documentation input`. Projects may additionally recognize one legacy
identity comment such as `<!-- yumemi-example: conversion-basic -->` through `withLegacyMarkerName('yumemi-example')` or
the CLI's `--legacy-marker-name` option. Canonical and legacy metadata share the same typed result and
duplicate-property checks. The legacy space form consumes the rest of its comment, including commas and `=`. Use
canonical `key=value` syntax or an adjacent metadata comment when combining an exception constraint with another
property.

Structural constructs remain separate because they delimit or reference source rather than describe one example:
`akashi-region` and `akashi-region-end` delimit canonical named regions, `akashi-sync` and `akashi-sync-end` delimit
synchronized presentations, and PHPDoc `@akashi-example` references an external canonical source.

## Runtime Semantics

`skip` keeps the example in its corpus and named PHPUnit data set, but `PhpUnitRuntime` asks PHPUnit to mark it skipped
before configuration, transformation, bootstrap loading, or execution. Skip affects runtime only. PHPStan may still
select the example, and marked extraction still returns its authored source.

`compile-only` keeps the example in the same corpus and named PHPUnit data set. PHPUnit asks Akashi's host-version PHP
parser to validate it and records one successful assertion without selecting an execution backend, applying runtime
transforms, loading the configured bootstrap, or executing authored code:

<!-- akashi: example=compile-only-runtime -->

```php
// akashi: compile-only

throw new RuntimeException('PHPUnit compile-only validation does not execute this.');
```

Parse failures retain the maintained documentation path and line. PHPStan and marked extraction may still select the
same example independently. PHPStan verification requires selected files and therefore executes their top-level code; do
not select a compile-only fragment whose top-level code is unsafe to run. `compile-only` cannot be combined with
`separate-process`, an expected-exception contract, or expected output, because backend selection and runtime evidence
have no meaning when PHPUnit execution is disabled.

`separate-process` selects child-process execution. It overrides an in-process configured default and requires
`RuntimeConfiguration` with an explicit project root. Akashi rejects missing configuration rather than silently running
the example in-process.

`expect-exception` uses PHPUnit-familiar type semantics. Its argument is a global PHP class or interface name; a leading
`\` is accepted but normalized away. In the selected runtime, that name must identify an available class or interface
compatible with `Throwable`. A subtype satisfies an expectation for its parent type:

````markdown
```php
// akashi: expect-exception=DomainException
// akashi: expect-exception-message="Invalid documentation input", expect-exception-code=73

throw new DomainException('Invalid documentation input.', 73);
```
````

`expect-exception-message` requires a nonempty, case-sensitive substring in the actual exception message. This follows
PHPUnit's `expectExceptionMessage()` behavior rather than requiring an exact string. `expect-exception-code` accepts a
signed base-10 integer within the running PHP build's integer range and compares it exactly with `Throwable::getCode()`.
A runtime string code, such as a PDO SQLSTATE, is preserved but cannot match that integer constraint. The example fails
if it completes normally, throws an incompatible type, has a mismatched message or code, or cannot complete its backend
cleanup. In-process mismatches preserve the actual throwable as the previous exception. Separate-process mismatches
preserve a typed parent-side representation of the child evidence; the expected type may be defined only inside the
child. Process exits, signals, timeouts, and infrastructure failures never satisfy an exception expectation. When `skip`
is also present, skip takes precedence over compile-only validation, configuration, transformation, and expectation
handling.

`expect-output` compares captured stdout exactly after the ordinary execution contract succeeds. Double-quoted JSON
escapes make line endings and other control characters explicit:

<!-- akashi: example=exact-output -->

```php
// akashi: expect-output="Hello, Akashi!\n"

echo "Hello, Akashi!\n";
```

Akashi does not normalize line endings, trim whitespace, or interpret patterns. `expect-output=""` asserts that stdout
is empty. The property may accompany `expect-exception`; in that case Akashi first requires the expected throwable,
message, and code contract to succeed, then compares output emitted before the throwable. Execution, cleanup, or
exception mismatches remain the primary failure instead of being masked by an output mismatch. Stderr remains diagnostic
evidence and has no expectation property in this release.

## Not Implemented

Akashi does not currently implement a global ignore directive, expected compilation failure, general expected runtime
failure, expected stderr, conditional or platform-specific skip, custom skip reasons, or hidden support-code execution.
The accepted roadmap reserves `setup=path.php` and `setup=path.php#region` for that future behavior, but current
releases reject them. The other items must not be inferred from Rust or PHPUnit terminology.
