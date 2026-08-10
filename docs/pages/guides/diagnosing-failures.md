# Diagnose Failures

<figure class="logion" data-logion="AWC 57:15">
<div class="logion-text">
<blockquote>
<p>The physician marked whether the wound arose beneath the blade or beneath the bandage, for one grief may require two
remedies and neither is served by an unnamed hour.</p>
</blockquote>
<p class="logion-citation">— <cite>Acts of the Western Court 57:15</cite></p>
</div>
<img src="../images/logia/AWC-57_15.webp" alt="A physician distinguishing two injuries beneath amber and cyan diagnostic rings" width="960" height="540" loading="lazy">
</figure>

Akashi reports failures at the maintained documentation source whenever the current transformation has enough mapping
information. Start with the phase and source path in the message, then inspect generated or temporary details only when
the report says an exact maintained line is unavailable.

## Discovery and Metadata Failures

Corpus loading fails before PHPUnit yields data sets when an include or exclusion is missing, a path escapes the project
root, the same physical document is reached twice, no documents or PHP examples are found, or marker/directive metadata
is malformed. These messages name the configured path or Markdown line responsible.

Fix the source set or comment placement; rerunning individual data sets cannot bypass a corpus-level discovery error.

## Parse and Transform Failures

PHP syntax errors and unsupported in-process constructs report the example ID and maintained Markdown location.
Unsupported examples commonly need either a source change or an explicit `<!-- akashi: separate-process -->` directive.
Akashi does not silently reroute them, because doing so would hide a change in execution semantics.

An assertion-transform error usually means the native `assert()` call uses unsupported argument syntax. See
[Assertion Behavior](../using/phpunit.md#assertion-behavior) for the accepted forms.

## Runtime Failures

The PHPUnit report includes:

- the example ID and human-readable data-set label;
- the failure phase;
- the maintained document and line, or the example start when an exact line is unavailable;
- the original exception or child-process outcome;
- captured stdout and stderr when nonempty;
- state-restoration or temporary-file cleanup failures.

For in-process examples, rewritten assertion messages identify the expression and source line unless the author supplied
a custom message. For child examples, PHP parse and runtime lines are translated when PHP reports the temporary-file
location in a recognized form. The original cause remains in the exception chain for debugger inspection.

## PHPStan Failures

PHPStan verification distinguishes configuration and preflight failures from diagnostic mismatches. A mismatch report
shows authored expectations, analyzer diagnostics, and their maintained Markdown locations. Common causes are:

- a diagnostic changed wording and no longer contains the `//!` substring;
- the actual and expected diagnostic counts differ;
- two expectations can match only one diagnostic;
- an example selected by a relevance token unexpectedly reports a diagnostic;
- declarations collide within the selected corpus or with the hosting process.

PHPStan identifiers are displayed when available, but the current expectation grammar matches message and tip text.

## Temporary Locations

Akashi keeps original example code separate from prepared execution or analysis source. Current source maps translate
generated PHP lines back to Markdown for parse errors, runtime failures, rewritten assertions, and PHPStan diagnostics
where the underlying tool exposes a usable line. A low-level exception may still mention an Akashi temporary file; the
user-facing report should prefer the original document when a mapping exists.

If a failure reports only an opaque temporary location even though PHP or PHPStan supplied a clear generated line, treat
that as a reporting defect and include both locations when filing an issue.
