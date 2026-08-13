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

The current syntax is a standalone `//!` line followed by a case-sensitive diagnostic substring:

```php
//! argument has an incompatible unit
operationThatPHPStanShouldReject();
```

The marker text must be nonempty. A trailing marker on the same line as PHP code is not recognized. Akashi requires the
actual and expected diagnostic counts to match and assigns every expectation to a distinct diagnostic. A selected
example with no expectations must analyze cleanly. Assignment considers the complete expectation/diagnostic set rather
than committing to the first greedy substring match, so overlapping broad and narrow expectations remain deterministic.

This text-oriented syntax is implemented for current consumer compatibility. PHPStan diagnostic identifiers are retained
and shown when available, but identifier-based expectations remain deferred.

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

## Decode External Analysis Results

Projects that already run `phpstan analyse --error-format=json` can decode its output without loading PHPStan or PHPUnit
classes:

```php
<?php

use jbboehr\Akashi\Integration\PHPStan\PhpStanJsonDecoder;

$result = (new PhpStanJsonDecoder())->decode($json);

foreach ($result->diagnosticsByFile as $path => $diagnostics) {
    // Associate each typed diagnostic with $path.
}
```

`PhpStanJsonResult` keeps analyzer-wide errors separate from diagnostics associated with files. Each
`AnalyzerDiagnostic` retains its message, optional line, optional identifier and tip, and PHPStan's `ignorable` flag.
The decoder accepts the documented PHPStan 1.12 and 2.x JSON shape, including PHPStan 1.12's empty `files` list, ignores
unknown fields for forward compatibility, and rejects malformed or internally inconsistent results.

This is a decoding boundary, not a command runner: the caller remains responsible for launching PHPStan and preserving
its exit status and standard streams. A typed command adapter and standalone verification result remain deferred.

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
