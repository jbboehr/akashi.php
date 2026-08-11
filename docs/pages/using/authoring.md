# Authoring Examples

<figure class="logion" data-logion="OSD 30:27">
<div class="logion-text">
<blockquote>
<p>Receive the stranger who beareth one seed as gladly as the caravan bearing a thousand jars; harvest judgeth the gift
by what awakeneth, not by the noise of its arrival.</p>
</blockquote>
<p class="logion-citation">— <cite>Ordinances of the Synthetic Dawn 30:27</cite></p>
</div>
<img src="../images/logia/OSD-30_27.webp" alt="A traveler bearing one luminous seed and a caravan of jars welcomed at the same harvest gate" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

Akashi discovers Markdown documents, extracts PHP fenced blocks with CommonMark, and preserves their maintained source
locations. Corpus selection controls which documents participate; markers and directives add metadata to examples but do
not make unmarked PHP fences disappear.

## Build a Corpus

Create a source configuration from an absolute project root, then add project-relative files and directories:

```php
<?php

use jbboehr\Akashi\Source\MarkdownSource;

$corpus = MarkdownSource::forProject(dirname(__DIR__))
    ->includeFile('README.md')
    ->includeDirectory('docs')
    ->exclude('docs/archive')
    ->load();
```

Each configuration method returns a new immutable value. Scalar path syntax is checked immediately; filesystem paths,
readability, and document identity are checked by `load()`.

Directory includes are recursive and select only files with the case-sensitive `.md` extension. Exclusions match an
exact file or a complete directory subtree. See [Configuration](../reference/configuration.md) for ordering, symlink,
duplicate-document, and failure behavior.

Choose documents whose PHP fences are meant to participate in at least one configured workflow. Akashi currently has a
runtime `skip` directive, but no global ignore or compile-only directive. Use another fence language for fragments that
should not enter the corpus, or exclude their containing document.

## Write PHP Fences

Akashi selects a fenced block when the first word of its info string is `php`, compared case-insensitively. An opening
PHP tag is optional:

````markdown
```php
$message = sprintf('Hello, %s!', 'Akashi');

assert($message === 'Hello, Akashi!');
```
````

Backtick and tilde fences, longer fences, indentation, block quotes, and other CommonMark structure are handled by the
CommonMark parser. Additional info-string words are retained as metadata but do not currently change Akashi behavior.

Every example retains the original code, document, fence metadata, line and byte spans, and its ordinal in the document.
Generated IDs combine a hash of the project-relative path with that ordinal. Moving the document or inserting an earlier
PHP fence therefore changes the generated ID.

The exact generated form is `example-{first 12 hexadecimal characters of sha1(project-relative path)}-{ordinal}`, with
the ordinal padded to at least two digits. Use an explicit marker ID when another tool needs an identity that survives
reordering.

## Labels and PHPUnit Data Sets

The human-readable example label is derived from its source location and becomes the PHPUnit data-set name.
`PhpUnitExampleDataSets::fromCorpus()` rejects duplicate labels before yielding the first data set, which keeps PHPUnit
filters and reports unambiguous.

Use ordinary prose immediately around a fence to explain the example. Akashi does not require each example to carry a
special name unless another consumer needs a durable identity.

## Add a Stable Marker

For consumer extraction, configure one marker name and place a matching HTML comment before the fence:

````markdown
<!-- akashi-example: conversion-basic -->

```php
$result = convert(1, 'meter', 'centimeter');
```
````

```php
<?php

use jbboehr\Akashi\Source\MarkdownSource;

$corpus = MarkdownSource::forProject(dirname(__DIR__))
    ->includeDirectory('docs')
    ->withMarkerName('akashi-example')
    ->load();
```

Marker names and IDs use lowercase kebab-case. IDs must be unique across the corpus. Markers are optional metadata:
`load()` still returns every PHP fence. Continue to [Extracting Named Examples](extracting.md) when a consumer needs one
marked example.

## Add a Runtime Directive

Akashi currently recognizes `skip` and `separate-process`. Place directives immediately before the PHP fence; a marker,
multiple directives, and blank lines may be stacked together. Prose or an unrelated block breaks the association.

````markdown
<!-- akashi: separate-process -->

```php
exit(0);
```
````

Unknown, duplicated, orphaned, or non-PHP directives fail during extraction. See
[Directives](../reference/directives.md) for precedence and [Separate-Process Execution](separate-process.md) for
backend configuration.
