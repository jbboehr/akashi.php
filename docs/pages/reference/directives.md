# Directives

<figure class="logion" data-logion="AWC 4:37">
<div class="logion-text">
<blockquote>
<p>A dancer rehearsed her falls as carefully as her leaps. When a stage board split, she descended without injury and
guided another performer down. The audience praised her grace; she thanked the hours spent learning the ground. Wisdom
prepares dignity for the moment it cannot remain upright.</p>
</blockquote>
<p class="logion-citation">— <cite>Acts of the Western Court 4:37</cite></p>
</div>
<img src="../images/logia/AWC-4_37.webp" alt="A practiced dancer safely guiding another performer down through a split stage" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

Directives may be Akashi-owned HTML comments associated with the next documentation fence, or PHP line comments inside
the example code itself. The current runtime directives are:

```html
<!-- akashi: skip -->
```

```html
<!-- akashi: separate-process -->
```

Canonical external PHP examples use the equivalent line-comment forms:

```php
// akashi: skip
// akashi: separate-process
```

An example may also declare the throwable type that successful runtime verification requires. The recommended form is
visible inside the PHP example:

```php
// akashi: expect-exception RuntimeException
// akashi: expect-exception-message invalid documentation input
// akashi: expect-exception-code 73
```

The alternative HTML form keeps the annotation outside the extracted PHP:

```html
<!-- akashi: expect-exception RuntimeException -->
<!-- akashi: expect-exception-message invalid documentation input -->
<!-- akashi: expect-exception-code 73 -->
```

## Association Rules

Place directives immediately before a fenced PHP block. Blank lines are allowed. A configured marker and multiple
directives may be stacked in any order:

````markdown
<!-- akashi-example: isolated-greeting -->
<!-- akashi: separate-process -->

```php
namespace DocumentationExample;

echo "Hello!\n";
```
````

Prose or an unrelated CommonMark block breaks the association. Unknown directives, duplicate directives, malformed
exception class names, orphaned directives, and directives targeting non-PHP fences fail during extraction with the
comment's source location.

Message and code constraints are optional, but each requires an `expect-exception` directive in the same inline or HTML
form. Inside PHPDoc, retain the normal leading `*` on each authored line. Akashi removes the docblock decoration before
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

Directives are deliberately not encoded in the fence info string; ordinary `php` language tags remain readable to
renderers and syntax highlighters.

Any inline directive may appear anywhere as an actual PHP line comment and applies to the whole example. Place an
expected-exception comment immediately before the operation expected to throw when that makes the example easier to
read; Akashi does not infer or enforce control-flow order. Recognition uses PHP comment tokens, so matching text inside
strings or heredocs is not metadata. The comment remains part of the ordinary PHP source, so readers, IDEs, formatters,
static analyzers, direct execution, and marked extraction all see it unchanged.

Use the HTML form for documentation fences when surrounding prose already establishes the behavior or an extracted
consumer fixture should not contain Akashi metadata. An example may use only one form of each directive; combining HTML
and inline forms is rejected as duplicate metadata even when both express the same behavior. External whole-file and
named-region examples use inline comments because their canonical code is not physically adjacent to the PHPDoc
reference.

## Runtime Semantics

`skip` keeps the example in its corpus and named PHPUnit data set, but `PhpUnitRuntime` asks PHPUnit to mark it skipped
before configuration, transformation, bootstrap loading, or execution. Skip affects runtime only. PHPStan may still
select the example, and marked extraction still returns its authored source.

`separate-process` selects child-process execution. It overrides an in-process configured default and requires
`RuntimeConfiguration` with an explicit project root. Akashi rejects missing configuration rather than silently running
the example in-process.

`expect-exception` uses PHPUnit-familiar type semantics. Its argument is a global PHP class or interface name; a leading
`\` is accepted but normalized away. In the selected runtime, that name must identify an available class or interface
compatible with `Throwable`. A subtype satisfies an expectation for its parent type:

````markdown
```php
// akashi: expect-exception DomainException
// akashi: expect-exception-message Invalid documentation input
// akashi: expect-exception-code 73

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
is also present, skip takes precedence over configuration, transformation, and expectation handling.

## Not Implemented

Akashi does not currently implement a global ignore directive, compile-only mode, general expected runtime or
compilation failure, conditional or platform-specific skip, custom skip reasons, or hidden support-code syntax. These
remain roadmap items and must not be inferred from Rust or PHPUnit terminology.
