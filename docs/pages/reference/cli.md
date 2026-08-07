# Extraction CLI

Composer exposes Akashi's command as `vendor/bin/akashi`. The current CLI deliberately performs one task: select an
explicitly marked PHP fence and write its source to standard output.

## Usage

```console
vendor/bin/akashi extract --marker-name=NAME FILE MARKER-ID
vendor/bin/akashi --help
vendor/bin/akashi --version
```

The option may appear before or after the positional arguments, but it is required exactly once. `FILE` may be absolute
or relative to the current working directory. The marker name and ID use lowercase kebab-case.

For example:

```console
vendor/bin/akashi extract \
    --marker-name=akashi-example \
    docs/pages/getting-started.md \
    hello-world
```

On success, stdout contains only the selected PHP source. Akashi does not parse, transform, or execute the PHP. It
preserves the authored opening tag and normalizes the selected output to exactly one final LF for compatibility with the
Yumemi extraction contract. Successful `--help` and `--version` output goes to stdout. Usage errors, extraction
diagnostics, and unexpected failures go to stderr.

## Exit Statuses

| Status | Meaning                                                                    |
| -----: | -------------------------------------------------------------------------- |
|    `0` | The command succeeded.                                                     |
|    `1` | Document loading, metadata validation, or marked-example selection failed. |
|    `2` | The command invocation was invalid.                                        |
|   `70` | An unexpected internal failure occurred.                                   |

The extraction command distinguishes malformed or duplicate markers, markers attached to non-PHP fences, missing
markers, unsafe source paths, and unreadable documents in its error text.

The CLI is currently an extraction tool, not a standalone test runner. Runtime and analyzer integrations remain normal
PHPUnit and PHPStan tests owned by the consuming project.
