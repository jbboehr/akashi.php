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

The Composer executable is `vendor/bin/akashi`. It currently provides marked-example extraction; it is not a standalone
documentation-test runner. Runtime examples are normally run through PHPUnit.

## Usage

```console
vendor/bin/akashi extract --marker-name=NAME FILE MARKER-ID
vendor/bin/akashi --help
vendor/bin/akashi --version
```

`FILE` may be absolute or relative to the current working directory. `NAME` and `MARKER-ID` use lowercase kebab-case.
The marker option may appear before or after the positional arguments, but it is required exactly once. Its explicit
value lets the generic command support a project's existing comment convention.

On successful extraction, stdout contains only the authored PHP fence source. Akashi removes an authored final line
ending, if present, and appends exactly one LF for compatibility with its recorded consumer. It does not add headings,
metadata, source comments, or transformation output, and it preserves an authored opening PHP tag. Successful help and
version output also use stdout.

Usage, extraction, and unexpected-failure diagnostics use stderr.

## Exit Statuses

| Status | Meaning                                                                       |
| -----: | ----------------------------------------------------------------------------- |
|    `0` | Successful extraction, help, or version output.                               |
|    `1` | Document loading, marker association, marker selection, or extraction failed. |
|    `2` | Invalid command or command arguments.                                         |
|   `70` | Unexpected internal software failure.                                         |

Invalid, missing, duplicate, orphaned, and non-PHP markers are extraction failures. Unknown commands or options and
missing required arguments are usage failures.
