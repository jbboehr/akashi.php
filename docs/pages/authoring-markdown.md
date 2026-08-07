# Authoring Markdown Examples

Akashi discovers Markdown documents, extracts PHP fenced blocks through CommonMark, and preserves their maintained
source locations. Discovery and metadata parsing are configured independently: adding a marker name does not hide
unmarked PHP examples from the corpus.

## Build a Corpus

Create an immutable source configuration from an absolute project root:

```php
<?php

use jbboehr\Akashi\Source\MarkdownSource;

$corpus = MarkdownSource::forProject(dirname(__DIR__))
    ->includeDirectory('docs/examples')
    ->withMarkerName('akashi-example')
    ->load();
```

Each fluent method returns a new configuration. Akashi validates scalar path syntax immediately and performs filesystem
validation during `load()`.

Only include documents whose `php` fences are intended to participate in the configured workflow. Akashi does not yet
provide a per-fence ignore or compile-only directive. Keep non-executable reference fragments outside the selected
documents or give those fences a language other than `php`.

Discovery follows these rules:

- Explicit files and recursively scanned directories are supported.
- Markdown filenames use the case-sensitive `.md` extension. Directory scans ignore other files.
- An exclusion matches one project-relative path or a complete directory subtree and must exist when loaded.
- Symlinked directories are not traversed, and resolved documents must remain inside the project root.
- Reaching one physical document through multiple includes is an error rather than a duplicate test run.
- Documents are ordered by their slash-normalized project-relative paths using bytewise lexical comparison.
- Missing, unreadable, duplicate, empty-document, and empty-example conditions have distinct source exceptions.

## PHP Fences

Akashi selects fenced code blocks whose first info-string word is `php`, compared case-insensitively. Backtick and tilde
fences, longer fences, indentation, block quotes, and other CommonMark structure are handled by the CommonMark parser.
Additional info-string words are preserved as metadata but currently have no Akashi semantics.

````markdown
```PHP example-metadata
<?php

echo "This is a PHP example.\n";
```
````

Each example retains its document, original extracted code, fence metadata, line and byte spans, and ordinal. Its
generated identity has the form:

```text
example-{first 12 hexadecimal characters of sha1(project-relative path)}-{ordinal}
```

The ordinal is padded to at least two digits. Moving a document or inserting an earlier PHP fence changes this generated
identity. Use an explicit marker when another tool needs a durable author-assigned identity.

## Explicit Markers

Configure one marker name with `withMarkerName()`, then place its HTML comment before a PHP fence:

````markdown
<!-- akashi-example: conversion-basic -->

```php
<?php

echo "marked\n";
```
````

Marker names and IDs use lowercase kebab-case. IDs must be unique across the loaded corpus. A configured marker that is
missing its fence, targets a non-PHP fence, appears twice, or shares one fence with another marker fails with its
Markdown location.

Markers are optional metadata. `MarkdownSource::load()` still returns every discovered PHP fence; use
`jbboehr\Akashi\Source\MarkedExampleSelector` or the [extraction CLI](reference/cli.md) when one marked example is
required:

```php
<?php

use jbboehr\Akashi\Source\MarkedExampleSelector;

$example = (new MarkedExampleSelector())->select($corpus, 'conversion-basic');
```

## Execution Directives

The only execution directive currently implemented is:

```html
<!-- akashi: separate-process -->
```

Place it immediately before the PHP fence. A marker and directive may be stacked in either order, with blank lines
between the comments and fence. Prose or unrelated block nodes break the association.

````markdown
<!-- akashi-example: isolated-example -->
<!-- akashi: separate-process -->

```php
<?php

namespace DocumentationExample;
```
````

Unknown, duplicate, orphaned, and non-PHP directives fail during extraction. Akashi never silently changes an example's
backend because an in-process transform rejected it; author the directive or configure the corpus default explicitly.
