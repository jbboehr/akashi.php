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

Akashi discovers Markdown documents and PHP source files, extracts PHP fenced blocks and PHPDoc references to canonical
PHP files, and preserves their maintained source locations. Corpus selection controls which documentation files
participate; markers and directives add metadata to examples but do not make unmarked PHP fences disappear.

## Build a Corpus

Create a source configuration from an absolute project root, then add project-relative files and directories:

```php
<?php

use jbboehr\Akashi\Source\DocumentationSource;

$corpus = DocumentationSource::forProject(dirname(__DIR__))
    ->includeFile('README.md')
    ->includeDirectory('docs')
    ->includeDirectory('src')
    ->exclude('docs/archive')
    ->load();
```

Each configuration method returns a new immutable value. Scalar path syntax is checked immediately; filesystem paths,
readability, and document identity are checked by `load()`. `DocumentationSource` selects case-sensitive `.md` and
`.php` files and dispatches each format to its corresponding extractor.

`includeFiles()` accepts an array, generator, or iterator of project-relative strings, `ProjectPath` values, or
`SplFileInfo` objects. A Symfony Finder configured with `files()` can therefore be passed directly without adding
Symfony Finder as an Akashi dependency. Directory includes remain available for the zero-dependency common case.

Exclusions match an exact file or a complete directory subtree. See [Configuration](../reference/configuration.md) for
ordering, symlink, duplicate-document, and failure behavior. `MarkdownSource` remains available when a project wants an
explicitly Markdown-only manifest.

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

Every inline example retains the original code, document, fence metadata, line and byte spans, and its ordinal in the
document. Generated inline IDs combine a hash of the project-relative path with that ordinal. Moving the document or
inserting an earlier PHP fence therefore changes the generated ID. Referenced examples instead derive stable IDs from
their canonical project-relative path and optional region name.

The exact generated form is `example-{first 12 hexadecimal characters of sha1(project-relative path)}-{ordinal}`, with
the ordinal padded to at least two digits. Use an explicit marker ID when another tool needs an identity that survives
reordering.

## Write PHPDoc Fences

Every `php` fence on the interior lines of a selected `/** ... */` comment enters the corpus, whether or not the comment
is attached to a named declaration:

````php
<?php

namespace Acme;

/**
 * Return a stable display name.
 *
 * ```php
 * $name = \Acme\Text::displayName('akashi');
 *
 * assert($name === 'AKASHI');
 * ```
 */
final class Text
{
    public static function displayName(string $name): string
    {
        return strtoupper($name);
    }
}
````

Akashi removes conventional docblock indentation, the leading `*`, and one following space before CommonMark parsing.
The opening `/**` and closing `*/` lines are delimiters rather than Markdown content, so put fences and prose on the
interior lines. An opening `<?php` tag inside the fence remains optional.

The extracted code is prefix-free, while failures refer to the original `.php` path and PHPDoc line. Each docblock is
parsed independently, so markers and directives cannot associate with a fence in a later comment. Use another fence
language for a PHP fragment that should not enter any workflow.

Akashi extracts the fence, not its surrounding declaration. The example above therefore calls the project class through
its fully qualified, Composer-autoloadable name. Supporting declarations must already be available through normal
project bootstrap or autoloading, or be written inside the fence.

## Reference Canonical PHP Examples

Use an inline PHPDoc fence for a short demonstration tied closely to one symbol. For a substantial or reused example,
keep ordinary PHP as the source of truth and reference it from PHPDoc:

```php
/**
 * @akashi-example examples/conversion.php#basic-conversion
 */
```

The target is relative to the configured project root. It names either a whole case-sensitive `.php` file or one stable
named region. A canonical region file remains ordinary valid PHP:

```php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

// akashi-region: basic-conversion
$result = convert(1, 'meter', 'centimeter');

assert($result === 100);
// akashi-region-end: basic-conversion
```

Akashi executes and analyzes only the bytes between the named marker lines. The surrounding opening tag, bootstrap, and
other regions keep the complete file directly executable and friendly to IDEs, formatters, and static-analysis tools.
Whole-file references use the complete file instead. A named region must not rely on a surrounding `require`, `use`, or
other file-level setup being copied into the example; keep required source inside the region or provide project setup
through the normal runtime and PHPStan configuration.

Region markers must be standalone PHP line comments with matching lowercase kebab-case names. Missing, malformed,
orphaned, mismatched, nested, duplicate, and empty regions fail during `load()` instead of being guessed at. Stable
names are deliberately used instead of line-number ranges, which unrelated edits would shift.

The default reference tag is `@akashi-example`. To consume another public tag convention, replace the accepted set:

```php
$source = DocumentationSource::forProject(dirname(__DIR__))
    ->includeDirectory('src')
    ->withPhpDocReferenceTags('example');
```

Pass more than one name to accept a migration overlap, for example
`withPhpDocReferenceTags('akashi-example', 'example')`. Akashi's model remains independent of PHPDocumentor; configuring
`example` does not adopt PHPDocumentor's line-range behavior or trailing-description syntax.

The canonical PHP file need not also be in the include manifest. It must resolve to a readable `.php` file inside the
same canonical project root. Multiple PHPDoc sites may reference the same whole file or region; Akashi creates one
example and retains every presentation site for tooling and future renderer integrations. Failures point to the
canonical PHP code line, while `ReferencedExampleSource::$references` preserves the referring PHPDoc locations.
Resolution is not recursive: PHPDoc inside a referenced file is scanned only when that file is also selected by the
source manifest.

External references are currently a PHPDoc authoring mode. Markdown continues to use physically embedded fences.

## Synchronized Presentations

Some documentation renderers cannot include an external file directly. Akashi can inspect an inline presentation whose
canonical source remains an ordinary PHP file or named region:

````markdown
<!-- akashi-sync: examples/conversion.php#basic-conversion -->

```php
$result = convert(1, 'meter', 'centimeter');
assert($result === 100);
```

<!-- akashi-sync-end -->
````

The start comment, PHP fence, and end comment must remain consecutive Markdown blocks, with only optional blank lines
between them. This form is stable under formatters such as Prettier, which insert those blank lines. The fence must be
explicitly closed and labelled `php`. The target follows the same project-relative `.php` path and optional lowercase
named-region rules as a PHPDoc external reference. The same canonical target may be presented in several documentation
locations. Directive names are case-sensitive; a case variant that resembles an Akashi directive is rejected as
malformed rather than silently ignored.

`SynchronizationChecker` parses this form in Markdown and conventional multiline PHPDoc comments and returns typed
`SynchronizationMismatch` values without modifying the document. To check explicit files in CI without writing them,
run:

```console
vendor/bin/akashi sync --check --project-root=. README.md docs/examples.md src/Example.php
```

The command is silent when every presentation is current. Stale presentations receive a source-labelled unified diff
from their embedded code to the canonical replacement. Stale or invalid presentations are reported on stderr and exit
with status `1`. See the [CLI reference](../reference/cli.md#check-synchronized-presentations) for the exact path,
stream, diff, and exit-status contract. Synchronization write mode remains deferred.

`SynchronizationRegion::$embeddedCode` contains the logical, undecorated PHP seen by CommonMark. Its `location` and
`regionSpan` point into the original maintained document, so slicing those spans returns raw Markdown indentation or
PHPDoc leading `*` decoration. This distinction preserves both comparison-ready code and the exact authored bytes that a
future writer would need.

Comparison is intentionally narrow and deterministic:

- CRLF and CR line endings compare as LF;
- a missing final newline is treated as one final LF, while additional trailing blank lines remain significant;
- Markdown fence indentation and conventional PHPDoc indentation and leading `*` decoration are containers, not code;
- indentation inside the logical PHP fence remains significant; and
- PHP opening tags are canonical code and are not inserted, removed, or case-normalized. A synchronized whole-file
  presentation therefore includes its opening tag when the canonical file does.

Malformed, orphaned, nested, overlapping, or incomplete synchronization structures fail rather than being guessed at.
Canonical named-region validation continues to reject missing, malformed, nested, mismatched, empty, or duplicate
regions. Formatter, hidden-support-code, renderer-inclusion, and rewriting features remain deferred.

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

use jbboehr\Akashi\Source\DocumentationSource;

$corpus = DocumentationSource::forProject(dirname(__DIR__))
    ->includeFile('README.md')
    ->includeDirectory('docs')
    ->includeDirectory('src')
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
