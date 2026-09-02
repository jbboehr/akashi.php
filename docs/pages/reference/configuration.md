# Configuration

<figure class="logion" data-logion="RAS 61:9">
<div class="logion-text" data-nosnippet>
<blockquote>
<p>The steward kept one immutable chart naming the court, the optional scroll read before testimony, and the ordinary
road; each revision produced a new chart while the former hearing retained its own.</p>
</blockquote>
<p class="logion-citation">— <cite>Revelation of the Artificial Sun 61:9</cite></p>
</div>
<img src="../images/logia/RAS-61_9.webp" alt="A steward holding one geometric glass chart before preserved earlier charts, a court, and an open road" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

Akashi uses immutable configuration objects. There is no global Akashi registry or configuration file in the current
API. Optional PHP-CS-Fixer checks may point to that tool's existing project configuration.

## Documentation Sources

Start with an absolute project root:

```php
$source = DocumentationSource::forProject($projectRoot);
```

The fluent methods are:

| Method                              | Contract                                                                                      |
| ----------------------------------- | --------------------------------------------------------------------------------------------- |
| `withFile($path)`                   | Add one project-relative file with the case-sensitive `.md` or `.php` extension.              |
| `withFiles($paths)`                 | Add files from an array or iterator of strings, `ProjectPath`, or `SplFileInfo` values.       |
| `withDirectory($path)`              | Recursively add `.md` and `.php` files below one project-relative directory.                  |
| `withExcludedPath($path)`           | Exclude an exact path and, for a directory, its complete subtree.                             |
| `withLegacyMarkerName($name)`       | Add one lowercase kebab-case legacy marker-comment dialect across both source formats.        |
| `withPhpDocReferenceTags(...$tags)` | Replace the default `@akashi-example` external-reference tag with one or more accepted names. |
| `load()`                            | Read selected sources and return one nonempty, deterministically ordered `ExampleCorpus`.     |

`withFiles()` consumes its iterable immediately to preserve immutable configuration. Symfony Finder entries extend
`SplFileInfo`, so a Finder restricted to files can be passed directly. A `SplFileInfo` may carry an absolute pathname,
but the resolved file must remain inside the configured project root. Strings and `ProjectPath` values remain
project-relative.

`MarkdownSource` retains the Markdown-only API, including `loadDocuments()`, and now also accepts `withFiles()`. Its
explicit files and recursive directories continue to select only the case-sensitive `.md` extension.

Canonical `akashi:` metadata, including `example=ID`, requires no source configuration. `withLegacyMarkerName()` is
additive: it preserves a project-specific comment such as `<!-- yumemi-example: ID -->` while canonical metadata remains
active. IDs from both forms share one corpus-wide uniqueness check.

Includes and exclusions are evaluated when loading. Configured paths must exist, documents must be readable, and
resolved documents must remain inside the project root. Symlinked directories are not traversed. Reaching one physical
document through multiple includes is an error. Documents are ordered by slash-normalized project-relative path using
bytewise lexical comparison. The final mixed corpus is ordered by canonical code path, first code line, and corpus
example ID, so inline and referenced examples remain deterministic together.

External PHP files referenced by selected PHPDoc do not also need to appear in the include manifest. They must resolve
to readable case-sensitive `.php` files inside the same canonical project root. Repeated references to the same whole
file or named region produce one example with every PHPDoc presentation location retained. See
[Authoring Examples](../using/authoring.md#reference-canonical-php-examples).

An empty include set, an include/exclusion that does not exist, a selected set with no supported documents, and a corpus
with no PHP fences or external references are distinct failures. See [Authoring Examples](../using/authoring.md) for the
common setup.

## PHP-CS-Fixer Configuration

`Formatting\PhpCsFixerConfiguration::forProject()` validates one canonical project root, one project-relative
PHP-CS-Fixer executable, and an optional project-relative PHP-CS-Fixer configuration:

```php
use jbboehr\Akashi\Formatting\PhpCsFixerConfiguration;

$formatting = PhpCsFixerConfiguration::forProject(
    projectRoot: dirname(__DIR__),
    executable: 'vendor/bin/php-cs-fixer',
    config: '.php-cs-fixer.dist.php',
);
```

The executable defaults to `vendor/bin/php-cs-fixer`; the config defaults to `null`, allowing PHP-CS-Fixer to discover
its usual project configuration from the project root. Configured files must exist, be readable regular files, and
resolve inside the canonical root. The executable is invoked through the current `PHP_BINARY`, so it is expected to be
the PHP Composer binary proxy rather than an arbitrary native executable.

Pass this immutable configuration to `Formatting\FormattingChecker`. Constructing it does not run the formatter, and no
other Akashi workflow reads it implicitly.

## Runtime Configuration

`RuntimeConfiguration` is optional for in-process execution and required for separate-process execution:

```php
<?php

use jbboehr\Akashi\Execution\ExecutionMode;
use jbboehr\Akashi\Execution\RuntimeConfiguration;

$runtime = RuntimeConfiguration::forProject($projectRoot)
    ->withBootstrap('vendor/autoload.php')
    ->withDefaultExecutionMode(ExecutionMode::InProcess);
```

`forProject()` resolves a readable directory to its canonical path. `withBootstrap()` accepts a project-relative,
readable file that resolves within that root. `withDefaultExecutionMode()` accepts `ExecutionMode::InProcess` or
`ExecutionMode::SeparateProcess`.

Runtime processing precedence is:

1. authored `skip` disposition;
2. authored `compile-only` disposition;
3. authored `separate-process` selection;
4. configured default execution mode;
5. in-process fallback.

Compile-only validation selects no execution mode. Combining its directive with an authored `separate-process` directive
or expected exception is invalid.

An in-process example with configuration runs from the configured project root. Without configuration, it runs from the
caller's current working directory. A separate-process example without configuration is rejected.

In-process bootstraps use `require_once` and are loaded once per PHPUnit process. Separate-process bootstraps use
`auto_prepend_file` in every child.

## PHPStan Configuration

`PhpStanExampleConfiguration::forProject($projectRoot, $predicate)` accepts a callable from `Example` to `bool`.
`PhpStanExampleConfiguration::forTokens($projectRoot, ...$tokens)` creates a predicate that selects code containing any
supplied case-sensitive token. At least one nonblank, unique token is required.

The project root is canonicalized and must be a readable directory. Selection preserves corpus order and must produce a
nonempty relevant subcorpus.
