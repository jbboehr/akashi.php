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

The Composer executable is `vendor/bin/akashi`. It provides marked-example extraction, optional inline formatting checks
or writes, and explicit synchronization checks or writes; it is not a standalone documentation-test runner. Runtime
examples are normally run through PHPUnit.

## Usage

```console
vendor/bin/akashi extract [--legacy-marker-name=NAME] [--project-root=PATH] FILE EXAMPLE-ID
vendor/bin/akashi format (--check|--write) [--project-root=PATH] [--php-cs-fixer=PATH] [--config=PATH] FILE [FILE ...]
vendor/bin/akashi sync (--check|--write) [--project-root=PATH] FILE [FILE ...]
vendor/bin/akashi --help
vendor/bin/akashi --version
```

Akashi uses Symfony Console for command discovery, argument parsing, generated help, and shell completion. Run the
executable without arguments to list commands, or inspect one command with `vendor/bin/akashi COMMAND --help`. Command
names are exact: abbreviations such as `ext` are rejected rather than guessed.

Generate a completion script for Bash, Fish, or Zsh with the built-in command, for example:

```console
vendor/bin/akashi completion bash
```

Symfony's standard `--quiet`, verbosity, ANSI, and non-interaction options are available. Quiet mode suppresses
successful command output but retains failure diagnostics. Akashi deliberately rejects `--silent` because its stable CLI
contract requires failures to remain visible.

## Extract a Named Example

`FILE` must use the case-sensitive `.md` or `.php` extension and may be absolute or relative to the current working
directory. Canonical `example=EXAMPLE-ID` metadata may precede its fence in Markdown or PHPDoc, or appear as an actual
PHP line comment inside fenced or referenced canonical code. `EXAMPLE-ID` uses lowercase kebab-case.

`--legacy-marker-name=NAME` optionally adds one lowercase kebab-case legacy marker-comment dialect, such as
`<!-- yumemi-example: chosen -->`. It may appear before or after the positional arguments and accepts either
`--legacy-marker-name=NAME` or `--legacy-marker-name NAME`. Canonical `akashi:` metadata remains recognized when this
compatibility option is present. The option may be specified at most once.

By default, Akashi treats `FILE`'s containing directory as the project root. Pass `--project-root=PATH` when `FILE`
lives deeper in the project or its PHPDoc contains project-relative external-example references. The path may be
absolute or relative to the current working directory, must contain `FILE`, and may be specified at most once.

On successful extraction, stdout contains only the authored PHP fence source. Akashi removes an authored final line
ending, if present, and appends exactly one LF for compatibility with its recorded consumer. It does not add headings,
metadata, source comments, or transformation output, and it preserves an authored opening PHP tag. Successful help and
version output also use stdout.

Usage, extraction, and unexpected-failure diagnostics use stderr, including under `--quiet`.

## Check or Write Inline Formatting

Check the inline PHP fences in explicitly selected Markdown or PHP documentation files:

```console
vendor/bin/akashi format --check --project-root=. README.md docs/examples.md src/Example.php
```

Select exactly one of `--check` or `--write`. `FILE` and `--project-root` follow the same current-working-directory,
project-containment, case-sensitive extension, duplicate-document, and symbolic-link discovery rules as the other
explicit-file commands. At least one `.md` or `.php` file is required.

The PHP-CS-Fixer executable defaults to the project-relative `vendor/bin/php-cs-fixer`. Override it with
`--php-cs-fixer=PATH`; select a project-relative configuration explicitly with `--config=PATH`. Both files must resolve
to readable regular files inside the canonical project root. Without `--config`, PHP-CS-Fixer performs its ordinary
configuration discovery from that root.

Akashi checks only inline Markdown and PHPDoc fences. PHPDoc references to whole external files or named regions are
loaded and validated but not sent through this adapter; run the project's ordinary formatter directly on those PHP
files. Each checked body is written to a private temporary PHP file, PHP-CS-Fixer runs through an explicit argument
vector without constructing a shell command, caching, or parallel execution, and a 60-second infrastructure timeout
applies.

A current set exits successfully without output. Each stale inline example produces a source-labelled unified diff on
stderr from the authored fence to the formatter result, followed by a deterministic count, and exits with status `1`. An
authored opening tag is preserved, while formatter changes to body line endings and the final newline remain visible.
Project-level material outside Akashi's protected body boundary, such as an inserted license header, is ignored.
Malformed formatter output, unsupported closing tags or inline HTML, configuration errors, process failures, timeouts,
and cleanup failures are command failures.

Write mode applies those formatter results to the inline examples in the selected documents:

```console
vendor/bin/akashi format --write --project-root=. README.md docs/examples.md src/Example.php
```

Before changing the first file, Akashi renders every proposed document in memory, reloads the complete selected source,
and repeats every formatter invocation. The maintained bytes and formatter results must match the first pass. Akashi
then uses stale-byte protection and same-directory atomic replacement for each changed document. Direct symbolic-link
files and paths through symbolic-link directories are rejected. Successful writes are reported on stderr in
deterministic project-path order; an entirely current set is silent.

Validation and formatter failures leave the selected set unchanged. A later filesystem error can occur after earlier
documents in a validated batch were replaced, but each individual document remains an all-or-nothing replacement. The
writer preserves permission bits, but not ownership, ACLs, extended attributes, or hard-link identity. Because
replacement is a directory-level rename, a read-only file can still be replaced when its containing directory is
writable; its read-only permission bits are retained on the replacement.

## Synchronize Presentations

Use check mode to compare one or more explicitly selected Markdown or PHP files with their canonical PHP sources:

```console
vendor/bin/akashi sync --check --project-root=. README.md docs/examples.md src/Example.php
```

Select exactly one of `--check` or `--write`. Files and `--project-root` may be absolute or relative to the current
working directory. The project root defaults to the current working directory, must contain every selected document, and
provides the containment boundary for each project-relative synchronization target. Only case-sensitive `.md` and `.php`
files are accepted.

A current set of presentations exits successfully without output. For each stale presentation, stderr identifies the
start directive's maintained document and line, its authored canonical target, and the resolved canonical code location.
It then prints a unified diff from the stale presentation (`-`) to the canonical replacement (`+`); both diff headers
carry their maintained path and first code line. Diff input uses the same narrow line-ending and final-newline
normalization as synchronization comparison. Akashi finally prints a mismatch count and exits with status `1`.

Write mode uses the same parser and in-memory renderer:

```console
vendor/bin/akashi sync --write --project-root=. README.md docs/examples.md src/Example.php
```

Before changing any file, Akashi renders and validates the complete selected set, reloads every maintained document, and
verifies the canonical snapshots again. It refuses to overwrite a document whose bytes changed after loading. Each
changed document is written to a temporary sibling, flushed, assigned the original file's permission bits, and
atomically renamed over the original. Direct symbolic-link files and paths through symbolic-link directories are
rejected. Unchanged files are not replaced. Successful write reports use stderr in deterministic project-path order; an
entirely current set is silent.

Akashi rejects a selected batch when one selected PHP document would be rewritten while another selected presentation
uses that PHP document as its whole-file canonical source. Otherwise the dependent replacement would be calculated from
bytes that the same batch is about to change. Write the canonical document separately and rerun the dependent
synchronization. Named-region dependencies remain supported because PHPDoc presentation rewriting does not change the
executable named region.

Validation errors leave the selected set unchanged. A later filesystem error can occur after earlier documents in a
validated batch were replaced, but each individual document remains an all-or-nothing replacement; rerunning the command
finishes any remaining current-safe work. The writer preserves permission bits, but not ownership, ACLs, extended
attributes, or hard-link identity. Because replacement is a directory-level rename, a read-only file can still be
replaced when its containing directory is writable; its read-only permission bits are retained on the replacement.

Malformed regions, unresolved targets, duplicate input files, unreadable files, stale document snapshots, and paths
outside the project root use status `1`. Options may appear before or after file arguments, but the selected mode and
`--project-root` may each be specified at most once. Valued options accept either `--name=value` or `--name value`.

## Exit Statuses

| Status | Meaning                                                                                    |
| -----: | ------------------------------------------------------------------------------------------ |
|    `0` | Successful extraction, formatting/synchronization check or write, help, or version output. |
|    `1` | A command failed, or a check found stale formatting or synchronized code.                  |
|    `2` | Invalid command or command arguments.                                                      |
|   `70` | Unexpected internal software failure.                                                      |

Invalid, missing, duplicate, orphaned, and non-PHP example identities are extraction failures. Unknown commands or
options and missing required arguments are usage failures. The extraction command selects an explicit `example`
identity; a PHPDoc external reference is only a corpus source unless its resolved canonical code declares that metadata.
PHP-CS-Fixer is optional and is required only when the formatting command is invoked. Generated help, command listing,
and shell completion are supplied by Symfony Console; Akashi retains its own exact-command, duplicate-option, stream,
and exit-status contracts around that router.
