# Directives

Directives are Akashi-owned HTML comments associated with the next PHP fence. The current runtime directives are:

```html
<!-- akashi: skip -->
```

```html
<!-- akashi: separate-process -->
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

Prose or an unrelated CommonMark block breaks the association. Unknown directives, duplicate directives, orphaned
directives, and directives targeting non-PHP fences fail during extraction with the comment's source location.

Directives are deliberately not encoded in the fence info string; ordinary `php` language tags remain readable to
renderers and syntax highlighters.

## Runtime Semantics

`skip` keeps the example in its corpus and named PHPUnit data set, but `PhpUnitRuntime` asks PHPUnit to mark it skipped
before configuration, transformation, bootstrap loading, or execution. Skip affects runtime only. PHPStan may still
select the example, and marked extraction still returns its authored source.

`separate-process` selects child-process execution. It overrides an in-process configured default and requires
`RuntimeConfiguration` with an explicit project root. Akashi rejects missing configuration rather than silently running
the example in-process.

When both directives are present, skip takes precedence.

## Not Implemented

Akashi does not currently implement a global ignore directive, compile-only mode, expected runtime or compilation
failure, conditional or platform-specific skip, custom skip reasons, hidden support-code syntax, or expected-exception
directive. These remain roadmap items and must not be inferred from Rust or PHPUnit terminology.
