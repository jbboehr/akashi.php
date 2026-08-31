# Reuse Examples for Runtime and PHPStan

<figure class="logion" data-logion="RAS 31:24">
<div class="logion-text">
<blockquote>
<p>Beneath the glass mountain two processions appeared, one ascending and one descending, yet every pilgrim bore the same
wound upon the left hand.</p>
</blockquote>
<p class="logion-citation">— <cite>Revelation of the Artificial Sun 31:24</cite></p>
</div>
<img src="../images/logia/RAS-31_24.webp" alt="Two opposed processions crossing a glass mountain while pilgrims bear matching left-hand bandages" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

Runtime behavior and static-analysis behavior answer different questions, but they can begin from the same maintained
documentation. Define the corpus once in project code, then let PHPUnit execute all examples and a PHPStan
`RuleTestCase` select the relevant subset.

## Define a Project Corpus

This helper belongs to the consuming project:

```php
<?php

use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Source\DocumentationSource;

final class DocumentationCorpus
{
    public static function load(): ExampleCorpus
    {
        return DocumentationSource::forProject(dirname(__DIR__))
            ->withFile('README.md')
            ->withDirectory('docs/examples')
            ->withDirectory('src')
            ->load();
    }
}
```

This is an ordinary project helper, not an Akashi requirement. It keeps Markdown and PHPDoc source selection consistent
between tests.

## Execute It with PHPUnit

```php
<?php

use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Integration\PhpUnit\VerifiesPhpUnitExamples;
use PHPUnit\Framework\TestCase;

final class DocumentationRuntimeTest extends TestCase
{
    use VerifiesPhpUnitExamples;

    protected static function akashiExampleCorpus(): ExampleCorpus
    {
        return DocumentationCorpus::load();
    }
}
```

Akashi's trait provides the data provider and runtime test. The consuming project owns the corpus definition and PHPUnit
test class.

## Analyze a Relevant Subcorpus

The following is a template: replace `YourRule` and `extension.neon` with the consumer's real PHPStan rule and
configuration.

```php
<?php

use jbboehr\Akashi\Integration\PHPStan\PhpStanExampleConfiguration;
use jbboehr\Akashi\Integration\PHPStan\VerifiesPhpStanExamples;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<YourRule> */
final class DocumentationPhpStanTest extends RuleTestCase
{
    use VerifiesPhpStanExamples;

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(YourRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/extension.neon'];
    }

    public function testExamples(): void
    {
        $projectRoot = dirname(__DIR__);
        $configuration = PhpStanExampleConfiguration::forTokens(
            $projectRoot,
            '@akashi-phpstan-error',
            '//!',
            '@analyze-example',
        );

        $this->assertPhpStanExamples(DocumentationCorpus::load(), $configuration);
    }
}
```

PHPStan owns `RuleTestCase`, the container, rule construction, and extension configuration. Akashi owns selection from
the supplied corpus, identifier and legacy `//!` expectation parsing, analysis-file preparation, diagnostic matching,
source mapping, and reporting through PHPUnit. `@akashi-phpstan-error` is Akashi's preferred expectation directive;
tokens such as `@analyze-example` are chosen by the consuming project.

## Decide Which Workflow Sees an Example

- Every selected PHP fence enters the shared corpus.
- PHPUnit sees each example as a data set; `<!-- akashi: skip -->` reports one as skipped, while `compile-only` parses
  one without runtime execution.
- PHPStan sees only examples accepted by `PhpStanExampleConfiguration`; runtime skip and compile-only do not affect that
  selection.
- Marked extraction can select one example independently of both verifiers.

PHPStan requires selected files to make declarations available, which executes their top-level code. Its subcorpus must
therefore contain trusted, runtime-safe top-level code even when PHPUnit skips a fence or validates it as compile-only.
Compile-only changes PHPUnit behavior only; exclude an unsafe non-running fragment from PHPStan selection.
