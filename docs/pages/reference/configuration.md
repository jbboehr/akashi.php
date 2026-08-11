# Configuration

<figure class="logion" data-logion="RAS 61:9">
<div class="logion-text">
<blockquote>
<p>The steward kept one immutable chart naming the court, the optional scroll read before testimony, and the ordinary
road; each revision produced a new chart while the former hearing retained its own.</p>
</blockquote>
<p class="logion-citation">— <cite>Revelation of the Artificial Sun 61:9</cite></p>
</div>
<img src="../images/logia/RAS-61_9.webp" alt="A steward holding one geometric glass chart before preserved earlier charts, a court, and an open road" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

Akashi uses immutable configuration objects. There is no global registry or configuration file in the current API.

## Documentation Sources

Start with an absolute project root:

```php
$source = DocumentationSource::forProject($projectRoot);
```

The fluent methods are:

| Method                    | Contract                                                                                 |
| ------------------------- | ---------------------------------------------------------------------------------------- |
| `includeFile($path)`      | Add one project-relative file with the case-sensitive `.md` or `.php` extension.         |
| `includeFiles($paths)`    | Add files from an array or iterator of strings, `ProjectPath`, or `SplFileInfo` values.  |
| `includeDirectory($path)` | Recursively add `.md` and `.php` files below one project-relative directory.             |
| `exclude($path)`          | Exclude an exact path and, for a directory, its complete subtree.                        |
| `withMarkerName($name)`   | Recognize one lowercase kebab-case marker-comment name across both source formats.       |
| `load()`                  | Read selected files and extract one nonempty, deterministically ordered `ExampleCorpus`. |

`includeFiles()` consumes its iterable immediately to preserve immutable configuration. Symfony Finder entries extend
`SplFileInfo`, so a Finder restricted to files can be passed directly. A `SplFileInfo` may carry an absolute pathname,
but the resolved file must remain inside the configured project root. Strings and `ProjectPath` values remain
project-relative.

`MarkdownSource` retains the Markdown-only API, including `loadDocuments()`, and now also accepts `includeFiles()`. Its
explicit files and recursive directories continue to select only the case-sensitive `.md` extension.

Includes and exclusions are evaluated when loading. Configured paths must exist, documents must be readable, and
resolved documents must remain inside the project root. Symlinked directories are not traversed. Reaching one physical
document through multiple includes is an error. Documents are ordered by slash-normalized project-relative path using
bytewise lexical comparison; examples within each document retain source order.

An empty include set, an include/exclusion that does not exist, a selected set with no supported documents, and a corpus
with no PHP fences are distinct failures. See [Authoring Examples](../using/authoring.md) for the common setup.

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

Runtime mode precedence is:

1. authored `skip` disposition;
2. authored `separate-process` selection;
3. configured default execution mode;
4. in-process fallback.

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
