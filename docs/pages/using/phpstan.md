# PHPStan

<figure class="logion" data-logion="RAS 66:9">
<div class="logion-text">
<blockquote>
<p>Above the city of glass there appeared seven dim stars, each reflected in a different well. The priests drew no water
until every reflection had been compared with its appointed star, and dawn found the vessels empty but the heavens
rightly named.</p>
</blockquote>
<p class="logion-citation">— <cite>Revelation of the Artificial Sun 66:9</cite></p>
</div>
<img src="../images/logia/RAS-66_9.webp" alt="Seven stars reflected in seven luminous wells before observers in a glass city at dawn" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

A documentation example can be executed at runtime and independently checked as a static-analysis fixture. PHPStan is an
optional, first-class integration: projects that do not need it can use the documentation and PHPUnit workflow without
installing or configuring PHPStan.

The consumer supplies its PHPStan rule and extension configuration. Akashi supplies corpus selection, expectation
parsing, temporary analysis files, diagnostic matching, source-line mapping, and PHPUnit reporting through PHPStan's
`RuleTestCase`.

## Express an Expected Diagnostic

Prefer a standalone identifier expectation immediately before the PHP statement that should produce the diagnostic:

```php
// @akashi-phpstan-error argument.type
operationThatPHPStanShouldReject();
```

The identifier is matched exactly and case-sensitively. An optional nonempty text constraint can also match a
case-sensitive substring in PHPStan's message plus optional tip:

```php
// @akashi-phpstan-error argument.type: incompatible unit
operationThatPHPStanShouldReject();
```

Repeated directives may describe several diagnostics for the same next statement. Blank lines may separate the
directives from that statement, but other comments or code may not intervene. The reported diagnostic line must fall
within the statement's maintained source span. Malformed or misplaced identifier directives fail as authoring errors.

Akashi also retains the standalone message-only form for existing consumers:

```php
//! argument has an incompatible unit
operationThatPHPStanShouldReject();
```

The `//!` text must be nonempty. It matches message and tip text across the selected example without constraining a
PHPStan identifier or statement line. A trailing marker on the same line as PHP code is not recognized.

For both forms, Akashi requires actual and expected diagnostic counts to match and assigns every expectation to a
distinct diagnostic. A selected example with no expectations must analyze cleanly. Assignment considers the complete
expectation/diagnostic set rather than committing to the first greedy match, so overlapping broad and narrow
expectations remain deterministic.

## Select Relevant Examples

Select with any project-owned predicate:

```php
<?php

use jbboehr\Akashi\Example;
use jbboehr\Akashi\Integration\PHPStan\PhpStanExampleConfiguration;

$configuration = PhpStanExampleConfiguration::forProject(
    $projectRoot,
    static fn (Example $example): bool => str_contains($example->code->source, '@analyze-example'),
);
```

For a list of case-sensitive source tokens, use the convenience constructor:

```php
<?php

use jbboehr\Akashi\Integration\PHPStan\PhpStanExampleConfiguration;

$configuration = PhpStanExampleConfiguration::forTokens(
    $projectRoot,
    '@akashi-phpstan-error',
    '//!',
    '@analyze-example',
);
```

Token names are project policy, not Akashi directives. Blank or duplicate tokens are rejected, and the selected
subcorpus must not be empty.

## Connect a RuleTestCase

Add `VerifiesPhpStanExamples` to the consumer's `RuleTestCase`, build the same corpus used for runtime tests, and call:

```php
$this->assertPhpStanExamples($corpus, $configuration);
```

The consumer still implements `getRule()` and, when needed, `getAdditionalConfigFiles()` in the normal PHPStan way. See
[Reuse Examples for Runtime and PHPStan](../guides/reuse-runtime-phpstan.md) for a complete combined pattern and a clear
division between Akashi, PHPStan, and project-owned setup.

### PHPStan 1.12

Akashi supports both PHPStan 1.12 and PHPStan 2.x. PHPStan 2.x is the normal development and recommended integration
line. A PHPStan 1.12 project must explicitly select PHP-Parser 4 alongside Akashi:

```console
composer require --dev "phpstan/phpstan:^1.12" "nikic/php-parser:^4.19.5"
```

PHPStan 1 embeds APIs built against PHP-Parser 4, while Akashi's own parser integration supports PHP-Parser 4.19.5 and
5.x. The explicit pin prevents Composer from selecting PHP-Parser 5 for a PHPStan 1 process. PHPStan 2 projects need no
special parser pin during normal dependency resolution. A PHPStan 2 project that deliberately resolves with
`--prefer-lowest` should explicitly require `nikic/php-parser:^5.8`; otherwise Composer may select Parser 4 from
Akashi's dual compatibility range even though PHPStan 2 expects Parser 5 APIs in the shared process.

Akashi preserves `RuleTestCase` semantics: the diagnostics under test come from the rule returned by the consumer's
`getRule()`. Additional configuration can register extensions that participate in parsing, reflection, or type
inference, but it does not turn the test into a complete `phpstan analyse` run or automatically execute every configured
PHPStan rule. If an example expects a diagnostic, the consumer-provided rule must report it.

## Verify an External PHPStan Run

For end-to-end consumer fixtures, a project can run the installed `phpstan analyse --error-format=json` command and
verify its output without loading PHPStan or PHPUnit classes. The consumer remains responsible for preparing the
disposable Composer project and installing the packages under test.

Keep the analyzed fixture as an ordinary PHP file. The selection token opts it into PHPStan verification, while each
identifier directive records an expected diagnostic immediately before the relevant statement:

```php
<?php

// @akashi-phpstan-example
// @akashi-phpstan-error method.notFound: Call to an undefined method Demo::missing()
(new Demo())->missing();
```

Reference that canonical file from a PHPDoc location included in the documentation corpus:

```php
<?php

/**
 * @akashi-example fixtures/demo.php
 */
final class DemoDocumentation
{
}
```

The planner selects the referenced external example, validates that its maintained bytes still match the loaded corpus,
and produces the analysis paths and exact expectation map consumed by the command verifier:

```php
<?php

use jbboehr\Akashi\Integration\PHPStan\PhpStanCommandVerifier;
use jbboehr\Akashi\Integration\PHPStan\PhpStanExampleConfiguration;
use jbboehr\Akashi\Integration\PHPStan\PhpStanExternalFixturePlanner;
use jbboehr\Akashi\Source\DocumentationSource;

$canonicalProjectRoot = realpath($projectRoot);
if ($canonicalProjectRoot === false) {
    throw new RuntimeException('The PHPStan project root is unavailable.');
}

$corpus = DocumentationSource::forProject($canonicalProjectRoot)
    ->withFile('src/DemoDocumentation.php')
    ->load();
$fixtures = (new PhpStanExternalFixturePlanner())->plan(
    $corpus,
    PhpStanExampleConfiguration::forTokens(
        $canonicalProjectRoot,
        '@akashi-phpstan-example',
    ),
);

$verified = (new PhpStanCommandVerifier())->verifyPlanOrThrow(
    plan: $fixtures,
    executable: PHP_BINARY,
    argumentsBeforePaths: [
        $canonicalProjectRoot . '/vendor/bin/phpstan',
        'analyse',
        '--error-format=json',
        '--no-progress',
    ],
    timeoutSeconds: 60.0,
);

$verification = $verified->verificationResult;
```

The plan analyzes each selected canonical PHP file once. Whole-file and named-region references to the same file are
grouped, and hard-link aliases are also grouped when the filesystem reports a stable device/inode identity. Duplicate
expectations from overlapping references are removed. Selection is intentionally limited to referenced external
examples: inline Markdown and PHPDoc examples still use the generated-source `RuleTestCase` path. Because PHPStan
analyzes the complete physical file, a diagnostic outside a selected named region is unexpected and causes a mismatch.
`PhpStanCommandVerifier::verifyPlanOrThrow()` consumes the complete plan, appends an owned `--` delimiter and its
analysis paths, and passes the plan's canonical root and expectation map through the lower-level verifier. Do not
include `--` in `argumentsBeforePaths`; the plan verifier rejects its owned delimiter there before launching PHPStan. It
returns concrete `PhpStanCommandVerified` evidence only when diagnostics match. Non-completion, rejected JSON output,
global analyzer errors, and diagnostic mismatches raise `PhpStanCommandVerificationFailedException`; its `result`
property retains the original typed variant, including command streams and any decoder cause. A nonzero analyzer exit
status is not independently a failure because expected diagnostics commonly produce one.

Use `verifyPlan()` instead when policy code needs every outcome as data. Callers that already own an expectation map may
use the parallel `verifyOrThrow()` or `verify()` methods without the planner. A project using both verification paths
should give them distinct selection tokens, or use a custom `forProject()` predicate that selects only referenced
sources.

The three result variants distinguish a command that did not complete, completed command output that could not be
decoded, and a completed verification. On the data-returning path, `PhpStanCommandVerified` means that verification ran;
inspect `verificationResult->isSuccessful()` to determine whether diagnostics matched.

`PhpStanJsonResult` keeps analyzer-wide errors separate from diagnostics associated with files. Each
`AnalyzerDiagnostic` retains its message, optional line, optional identifier and tip, and PHPStan's `ignorable` flag.
The decoder accepts the documented PHPStan 1.12 and 2.x JSON shape, including PHPStan 1.12's empty `files` list, ignores
unknown fields for forward compatibility, and rejects malformed or internally inconsistent results.

`PhpStanCommandVerifier` validates the complete expectation map before launching the process, then composes
`PhpStanCommandRunner`, `PhpStanJsonDecoder`, and `PhpStanResultVerifier`. The lower-level classes remain available when
a consumer needs to apply its own command-status or decoding policy. The command timeout defaults to 60 seconds and may
be replaced with another finite positive duration, as shown explicitly above.

`PhpStanCommandRunner` executes an explicit executable and argument list from an explicit project root. Akashi never
constructs a command string or interpolates caller values into one, so arguments do not undergo shell word splitting,
globbing, or command substitution. The example uses the current PHP binary to run the project-installed PHPStan proxy,
which is portable across the supported operating-system runners. The immutable result preserves the termination kind,
exit status, standard streams, elapsed time, and any applicable timeout, signal, or infrastructure-failure message. A
nonzero exit status is still a completed invocation because PHPStan may return diagnostics with that status; decoding
and later verification decide what the output means.

The runner canonicalizes the project root and executable with `realpath()` and inherits the caller's environment. The
fixture planner derives exact expectation paths from that same canonical root. The runner neither installs PHPStan nor
chooses analysis paths or arguments, and it does not decode output automatically. Malformed arguments and timeout values
are programmer errors; unavailable paths, local instrumentation failures, and process failures surfaced as exceptions
are returned as typed infrastructure evidence.

Symfony Process may retry a failed direct POSIX launch through an escaped shell command line. Because it does not expose
whether that fallback occurred, a resulting status such as `126` or `127` remains raw `Completed` evidence rather than
being guessed to be an infrastructure failure. This boundary preserves caller-supplied argument boundaries, but it is
not a security sandbox: the configured executable runs with the caller's operating-system permissions and should be
treated as trusted project tooling.

`PhpStanResultVerifier` compares each expected file with the corresponding decoded diagnostics through the same
deterministic one-to-one matcher used by the `RuleTestCase` integration. The returned `PhpStanVerificationResult` keeps
successful file matches, file mismatches, and analyzer-wide errors separate. An expectation can require an exact
identifier, a case-sensitive message-or-tip substring, or both. When it carries a `sourceLineRange`, the diagnostic's
maintained `sourceLine`, or its analyzer line when no maintained mapping exists, must fall within that range. Matching
also requires equal counts and assigns every expectation to a distinct diagnostic. `DiagnosticExpectation::$sourceLine`
identifies the authored expectation for reporting and is not itself a matching constraint.

A missing expected file with at least one expectation and an unexpected diagnostic file become ordinary count mismatches
with their complete evidence. An expected path with an empty expectation list matches an absent analyzer entry, because
the decoded result is not a complete manifest of every analyzed file. Verification therefore cannot by itself prove that
a clean file was analyzed; callers that need that guarantee must cross-check their configured or invoked analysis paths.
Analyzer-wide errors make the result unsuccessful without discarding otherwise successful file matches. Use
`isSuccessful()` for disposition and inspect `matchesByFile`, `mismatchesByFile`, and `globalErrors` for reporting.

Paths are compared exactly. A caller that analyzes generated or relocated files remains responsible for associating the
analyzer paths with the maintained expectation paths before verification.

Command execution, JSON decoding, and expectation verification remain separate public stages beneath the convenience
orchestrator. Akashi does not create Composer projects, install packages, generate PHPStan configuration, or choose a
consumer's compatibility matrix.

## Analysis Lifecycle and Trust

Akashi parses all selected examples and validates their declarations before loading any of them. It rejects direct
`exit` or `die`, `__halt_compiler()`, built-in `define()`, duplicate class-like, function, or global-constant
declarations, and declarations already present in the hosting process. It then writes private temporary PHP files,
requires every selected file once so declarations are visible to reflection, and analyzes each file independently via
PHPStan's public `gatherAnalyserErrors()` API.

Requiring the files executes their top-level code. PHPStan verification is therefore for trusted, runtime-safe project
documentation. Akashi captures output, restores the working directory, error-reporting level, and output-buffer stack,
and removes temporary artifacts, but it is not a sandbox.

Analyzer lines are translated back to maintained Markdown, inline PHPDoc, or canonical external PHP lines when the
current mapping supports them. Low-level diagnostic metadata may retain a temporary path, while the user-facing failure
report prefers the canonical maintained document. This means one ordinary named-region file can serve direct tooling,
runtime verification, and PHPStan verification without copying its code into every PHPDoc presentation site.
