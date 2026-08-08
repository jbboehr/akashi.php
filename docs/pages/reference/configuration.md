# Configuration

Akashi uses immutable configuration objects. There is no global registry or configuration file in the current API.

## Markdown Sources

Start with an absolute project root:

```php
$source = MarkdownSource::forProject($projectRoot);
```

The fluent methods are:

| Method                    | Contract                                                               |
| ------------------------- | ---------------------------------------------------------------------- |
| `includeFile($path)`      | Add one project-relative file with the case-sensitive `.md` extension. |
| `includeDirectory($path)` | Recursively add `.md` files below one project-relative directory.      |
| `exclude($path)`          | Exclude an exact path and, for a directory, its complete subtree.      |
| `withMarkerName($name)`   | Recognize one lowercase kebab-case marker-comment name.                |
| `loadDocuments()`         | Read and return selected documents in deterministic path order.        |
| `load()`                  | Read documents and extract a nonempty `ExampleCorpus` of PHP fences.   |

Includes and exclusions are evaluated when loading. Configured paths must exist, documents must be readable, and
resolved documents must remain inside the project root. Symlinked directories are not traversed. Reaching one physical
document through multiple includes is an error. Documents are ordered by slash-normalized project-relative path using
bytewise lexical comparison; examples within each document retain source order.

An empty include set, an include/exclusion that does not exist, a selected set with no documents, and a corpus with no
PHP fences are distinct failures. See [Authoring Examples](../using/authoring.md) for the common setup.

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
