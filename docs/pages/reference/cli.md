# CLI

<figure class="logion" data-logion="SFA 52:45">
<div class="logion-text">
<blockquote>
<p>Do not pity the silver mask when the actor departeth. It was fashioned to bear one sorrow before the multitude, and
fulfillment is not diminished because the face beneath it hath returned to ordinary joy.</p>
</blockquote>
<p class="logion-citation">— <cite>Scholia of the Fifth Archive 52:45</cite></p>
</div>
<img src="../images/logia/SFA-52_45.webp" alt="A silver sorrow-mask resting beneath a fading stage light as its actor departs into dawn" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

The Composer executable is `vendor/bin/akashi`. It provides marked-example extraction and read-only synchronization
checks; it is not a standalone documentation-test runner. Runtime examples are normally run through PHPUnit.

## Usage

```console
vendor/bin/akashi extract --marker-name=NAME [--project-root=PATH] FILE MARKER-ID
vendor/bin/akashi sync --check [--project-root=PATH] FILE [FILE ...]
vendor/bin/akashi --help
vendor/bin/akashi --version
```

## Extract a Named Example

`FILE` must use the case-sensitive `.md` or `.php` extension and may be absolute or relative to the current working
directory. Markdown markers precede their fence in the document; PHPDoc markers precede their fence within the same
docblock. `NAME` and `MARKER-ID` use lowercase kebab-case. The marker option may appear before or after the positional
arguments, but it is required exactly once. Its explicit value lets the generic command support a project's existing
comment convention.

By default, Akashi treats `FILE`'s containing directory as the project root. Pass `--project-root=PATH` when `FILE`
lives deeper in the project or its PHPDoc contains project-relative external-example references. The path may be
absolute or relative to the current working directory, must contain `FILE`, and may be specified at most once.

On successful extraction, stdout contains only the authored PHP fence source. Akashi removes an authored final line
ending, if present, and appends exactly one LF for compatibility with its recorded consumer. It does not add headings,
metadata, source comments, or transformation output, and it preserves an authored opening PHP tag. Successful help and
version output also use stdout.

Usage, extraction, and unexpected-failure diagnostics use stderr.

## Check Synchronized Presentations

Use the check-only synchronization command to compare one or more explicitly selected Markdown or PHP files with their
canonical PHP sources:

```console
vendor/bin/akashi sync --check --project-root=. README.md docs/examples.md src/Example.php
```

`--check` is required. Akashi does not currently provide a synchronization write mode, so the command never changes the
selected documents or their canonical sources. Files and `--project-root` may be absolute or relative to the current
working directory. The project root defaults to the current working directory, must contain every selected document, and
provides the containment boundary for each project-relative synchronization target. Only case-sensitive `.md` and `.php`
files are accepted.

A current set of presentations exits successfully without output. For each stale presentation, stderr identifies the
start directive's maintained document and line, its authored canonical target, and the resolved canonical code location.
It then prints a unified diff from the stale presentation (`-`) to the canonical replacement (`+`); both diff headers
carry their maintained path and first code line. Diff input uses the same narrow line-ending and final-newline
normalization as synchronization comparison. Akashi finally prints a mismatch count and exits with status `1`. Malformed
regions, unresolved targets, duplicate input files, unreadable files, and paths outside the project root also use status
`1`. Options may appear before or after file arguments, but `--check` and `--project-root` may each be specified at most
once.

## Exit Statuses

| Status | Meaning                                                                                          |
| -----: | ------------------------------------------------------------------------------------------------ |
|    `0` | Successful extraction, synchronization check, help, or version output.                           |
|    `1` | An extraction failed, synchronized code is stale, or synchronization input could not be checked. |
|    `2` | Invalid command or command arguments.                                                            |
|   `70` | Unexpected internal software failure.                                                            |

Invalid, missing, duplicate, orphaned, and non-PHP markers are extraction failures. Unknown commands or options and
missing required arguments are usage failures. The extraction command still selects explicit fence markers; PHPDoc
external references are corpus sources, not extraction marker IDs.
