<?php

/**
 * +--------------------------------------------------------------------------------------------------------------+
 * |        *                 .                         *                  .                         *            |
 * |   .              *                      .                    *                      .                        |
 * |             .                 .                  *                         .                 *               |
 * -      *                    .             *                    .                         .                     -
 *
 *                               Probatio Verborum Viventium『証』〜ＡＫＡＳＨＩ〜
 *
 * -                                          .----------------.                                                  -
 * |                                      .--'        __        '--.                                              |
 * |                                  .--'          .'  '.          '--.                                          |
 * |                             .---'            .'      '.            '---.                                     |
 * +--------------------------------------------------------------------------------------------------------------+
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * SPDX-License-Identifier: AGPL-3.0-only WITH romic-exception
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License version 3,
 * as published by the Free Software Foundation, together with the Romic
 * Exception (an additional permission under section 7 of that license).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * and the Romic Exception along with this program.  If not, see
 * <http://www.gnu.org/licenses/> and the LICENSE_EXCEPTION file.
 */

declare(strict_types=1);

namespace jbboehr\Akashi\Tests\Formatting;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Example;
use jbboehr\Akashi\Formatting\Exception\FormattingRewriteException;
use jbboehr\Akashi\Formatting\FormattingMismatch;
use jbboehr\Akashi\Formatting\FormattingRewriter;
use jbboehr\Akashi\Markdown\CommonMarkExampleExtractor;
use jbboehr\Akashi\Model\CodeOrigin;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\CorpusExampleId;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\PhpDocTagName;
use jbboehr\Akashi\Model\ReferencedExampleSource;
use jbboehr\Akashi\Model\ReferenceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use jbboehr\Akashi\PhpDoc\PhpDocExampleExtractor;
use PHPUnit\Framework\TestCase;

final class FormattingRewriterTest extends TestCase
{
    public function testRewritesSeveralMarkdownExamplesInAnyMismatchOrder(): void
    {
        $document = new Document('docs/example.md', <<<'MARKDOWN'
Before.

> ```php
> $first=1;
> ```

  ```php
  $second=2;
  ```

After.
MARKDOWN);
        $examples = (new CommonMarkExampleExtractor())->extract($document);
        self::assertCount(2, $examples);

        $rewritten = (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch($examples[1], new ExampleCode("if (true) {\n    \$second = 2;\n}\n")),
            new FormattingMismatch($examples[0], new ExampleCode("\$first = 1;\n")),
        );

        self::assertSame(<<<'MARKDOWN'
Before.

> ```php
> $first = 1;
> ```

  ```php
  if (true) {
      $second = 2;
  }
  ```

After.
MARKDOWN, $rewritten->contents);
    }

    public function testRewritesPhpDocWithoutChangingCommentDecorationOrProse(): void
    {
        $document = new Document('src/Example.php', <<<'PHP'
<?php
/**
 * Existing prose.
 *
 * ```php
 * $value=1;
 * ```
 *
 * More prose.
 */
final class Example {}
PHP);
        $examples = (new PhpDocExampleExtractor())->extract($document);
        self::assertCount(1, $examples);

        $rewritten = (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch($examples[0], new ExampleCode("if (true) {\n\n    \$value = 1;\n}\n")),
        );

        self::assertSame(<<<'PHP'
<?php
/**
 * Existing prose.
 *
 * ```php
 * if (true) {
 *
 *     $value = 1;
 * }
 * ```
 *
 * More prose.
 */
final class Example {}
PHP, $rewritten->contents);
    }

    public function testPreservesFormatterProposedLineEndingsInsideTheCodeSpan(): void
    {
        $document = new Document(
            'docs/example.md',
            "Before.\r\n```php\r\n\$value=1;\r\n```\r\nAfter.\r\n",
        );
        $example = (new CommonMarkExampleExtractor())->extract($document)[0];

        $rewritten = (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch($example, new ExampleCode("\$value = 1;\n")),
        );

        self::assertSame(
            "Before.\r\n```php\r\n\$value = 1;\n```\r\nAfter.\r\n",
            $rewritten->contents,
        );
    }

    public function testRestoresTheAuthoredCodePrefixInsideAListItem(): void
    {
        $document = new Document('docs/example.md', <<<'MARKDOWN'
- ```php
  $value=1;
  ```
MARKDOWN);
        $example = (new CommonMarkExampleExtractor())->extract($document)[0];

        $rewritten = (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch($example, new ExampleCode("\$value = 1;\n")),
        );

        self::assertSame(<<<'MARKDOWN'
- ```php
  $value = 1;
  ```
MARKDOWN, $rewritten->contents);
    }

    public function testIgnoresIndependentClosingFenceIndentation(): void
    {
        $document = new Document('docs/example.md', <<<'MARKDOWN'
```php
$value=1;
  ```
MARKDOWN);
        $example = (new CommonMarkExampleExtractor())->extract($document)[0];

        $rewritten = (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch($example, new ExampleCode("\$value = 1;\n")),
        );

        self::assertSame(<<<'MARKDOWN'
```php
$value = 1;
  ```
MARKDOWN, $rewritten->contents);
    }

    public function testRestoresTheAuthoredCodePrefixInsideAnUnclosedListFence(): void
    {
        $document = new Document('docs/example.md', <<<'MARKDOWN'
- ```php
  $value=1;
MARKDOWN);
        $example = (new CommonMarkExampleExtractor())->extract($document)[0];

        $rewritten = (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch($example, new ExampleCode("\$value = 1;\n")),
        );

        self::assertSame("- ```php\n  \$value = 1;\n", $rewritten->contents);
    }

    public function testPreservesANonterminatedFinalLineInAnUnclosedFence(): void
    {
        $document = new Document('docs/example.md', "```php\n\$value=1;");
        $example = (new CommonMarkExampleExtractor())->extract($document)[0];

        $rewritten = (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch($example, new ExampleCode('$value = 1;')),
        );

        self::assertSame("```php\n\$value = 1;", $rewritten->contents);
    }

    public function testReturnsTheSameDocumentWhenNoMismatchesAreSupplied(): void
    {
        $document = new Document('docs/example.md', "```php\necho 1;\n```\n");

        self::assertSame($document, (new FormattingRewriter())->rewrite($document));
    }

    public function testCanRemoveAllCodeFromAnInlineFence(): void
    {
        $document = new Document('docs/example.md', "```php\n\$value=1;\n```\n");
        $example = (new CommonMarkExampleExtractor())->extract($document)[0];

        $rewritten = (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch($example, new ExampleCode('')),
        );

        self::assertSame("```php\n```\n", $rewritten->contents);
    }

    public function testAttributesAnInvalidMaintainedDocumentToValidation(): void
    {
        $valid = new Document('docs/example.md', "```php\n\$value=1;\n```\n");
        $example = (new CommonMarkExampleExtractor())->extract($valid)[0];

        $this->expectException(FormattingRewriteException::class);
        $this->expectExceptionMessage('Unable to validate formatting replacements for docs/example.md');

        (new FormattingRewriter())->rewrite(
            new Document('docs/example.md', "```php\n\$value = \"\xFF\";\n```\n"),
            new FormattingMismatch($example, new ExampleCode("\$value = 1;\n")),
        );
    }

    public function testAttributesInvalidMaintainedDirectiveMetadataToValidation(): void
    {
        $valid = new Document('docs/example.md', "```php\n\$value=1;\n```\n");
        $example = (new CommonMarkExampleExtractor())->extract($valid)[0];

        $this->expectException(FormattingRewriteException::class);
        $this->expectExceptionMessage('Unable to validate formatting replacements for docs/example.md');

        (new FormattingRewriter())->rewrite(
            new Document('docs/example.md', <<<'MARKDOWN'
<!-- akashi: skip -->
```php
// akashi: skip
$value=1;
```
MARKDOWN),
            new FormattingMismatch($example, new ExampleCode("\$value = 1;\n")),
        );
    }

    public function testRejectsAMismatchAfterTheDocumentChanges(): void
    {
        $document = new Document('docs/example.md', "Before.\n```php\n\$value=1;\n```\n");
        $example = (new CommonMarkExampleExtractor())->extract($document)[0];
        $stale = new FormattingMismatch($example, new ExampleCode("\$value = 1;\n"));

        $this->expectException(FormattingRewriteException::class);
        $this->expectExceptionMessage('is stale because docs/example.md has changed');

        (new FormattingRewriter())->rewrite(
            new Document('docs/example.md', "Changed.\n```php\n\$value=1;\n```\n"),
            $stale,
        );
    }

    public function testRejectsAMismatchFromAnotherDocument(): void
    {
        $document = new Document('docs/first.md', "```php\n\$value=1;\n```\n");
        $example = (new CommonMarkExampleExtractor())->extract($document)[0];

        $this->expectException(FormattingRewriteException::class);
        $this->expectExceptionMessage('belongs to docs/first.md, not docs/second.md');

        (new FormattingRewriter())->rewrite(
            new Document('docs/second.md', $document->contents),
            new FormattingMismatch($example, new ExampleCode("\$value = 1;\n")),
        );
    }

    public function testRejectsTheSameMismatchTwice(): void
    {
        $document = new Document('docs/example.md', "```php\n\$value=1;\n```\n");
        $example = (new CommonMarkExampleExtractor())->extract($document)[0];
        $mismatch = new FormattingMismatch($example, new ExampleCode("\$value = 1;\n"));

        $this->expectException(FormattingRewriteException::class);
        $this->expectExceptionMessage('was supplied more than once');

        (new FormattingRewriter())->rewrite($document, $mismatch, $mismatch);
    }

    public function testRejectsAMismatchWhoseIdentityIsNotInTheCurrentDocument(): void
    {
        $document = new Document('docs/example.md', "```php\n\$value=1;\n```\n");
        $current = (new CommonMarkExampleExtractor())->extract($document)[0];
        $forged = new Example(
            new CorpusExampleId('another-example'),
            $current->label,
            $current->source,
            $current->language,
            $current->code,
            $current->ordinal,
            $current->namedId,
            $current->directives,
            $current->expectedException,
        );

        $this->expectException(FormattingRewriteException::class);
        $this->expectExceptionMessage('does not match the current inline example');

        (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch($forged, new ExampleCode("\$value = 1;\n")),
        );
    }

    public function testRejectsAMismatchWhoseOrdinalDoesNotMatchItsIdentity(): void
    {
        $document = new Document('docs/example.md', "```php\n\$value=1;\n```\n");
        $current = (new CommonMarkExampleExtractor())->extract($document)[0];
        $forged = new Example(
            $current->corpusId,
            $current->label,
            $current->source,
            $current->language,
            $current->code,
            2,
            $current->namedId,
            $current->directives,
            $current->expectedException,
        );

        $this->expectException(FormattingRewriteException::class);
        $this->expectExceptionMessage('does not match the current inline example');

        (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch($forged, new ExampleCode("\$value = 1;\n")),
        );
    }

    public function testRejectsAReferencedExampleMismatch(): void
    {
        $document = new Document('docs/example.md', 'Reference only.');
        $canonical = new Document('examples/example.php', "echo 1;\n");
        $source = new ReferencedExampleSource(
            new CodeOrigin($canonical, 1, 1, new SourceSpan(0, strlen($canonical->contents))),
            null,
            [new ReferenceLocation(
                $document,
                new PhpDocTagName('akashi-example'),
                1,
                new SourceSpan(0, strlen($document->contents)),
            )],
        );
        $example = new Example(
            new CorpusExampleId('referenced-example'),
            'Referenced example',
            $source,
            new Language('php'),
            new ExampleCode($canonical->contents),
            1,
        );

        $this->expectException(FormattingRewriteException::class);
        $this->expectExceptionMessage('does not describe an inline documentation example');

        (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch($example, new ExampleCode("echo 2;\n")),
        );
    }

    public function testRejectsFormatterOutputThatWouldCloseAMarkdownFence(): void
    {
        $document = new Document('docs/example.md', "```php\n\$value=1;\n```\n");
        $example = (new CommonMarkExampleExtractor())->extract($document)[0];

        $this->expectException(FormattingRewriteException::class);
        $this->expectExceptionMessage('cannot be rendered safely');

        (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch($example, new ExampleCode("\$value = <<<'MARKDOWN'\n```\nMARKDOWN;\n")),
        );
    }

    public function testRejectsFormatterOutputThatWouldCloseAPhpDocComment(): void
    {
        $document = new Document('src/Example.php', <<<'PHP'
<?php
/**
 * ```php
 * $value=1;
 * ```
 */
final class Example {}
PHP);
        $example = (new PhpDocExampleExtractor())->extract($document)[0];

        $this->expectException(FormattingRewriteException::class);
        $this->expectExceptionMessage('cannot be rendered safely');

        (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch($example, new ExampleCode("/*\n*/\n\$value = 1;\n")),
        );
    }

    public function testRejectsFormatterOutputThatChangesRuntimeDirectives(): void
    {
        $document = new Document('docs/example.md', <<<'MARKDOWN'
```php
// akashi: skip
$value=1;
```
MARKDOWN);
        $example = (new CommonMarkExampleExtractor())->extract($document)[0];

        $this->expectException(FormattingRewriteException::class);
        $this->expectExceptionMessage('cannot be rendered safely');

        (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch($example, new ExampleCode("\$value = 1;\n")),
        );
    }

    public function testRejectsFormatterOutputThatChangesAnInlineCanonicalMarker(): void
    {
        $document = new Document('docs/example.md', <<<'MARKDOWN'
```php
// akashi: example=formatted-example
$value=1;
```
MARKDOWN);
        $example = (new CommonMarkExampleExtractor())->extract($document)[0];

        $this->expectException(FormattingRewriteException::class);
        $this->expectExceptionMessage('cannot be rendered safely');

        (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch($example, new ExampleCode("\$value = 1;\n")),
        );
    }

    public function testAttributesInvalidCandidateDirectiveMetadataToTheExample(): void
    {
        $document = new Document('docs/example.md', <<<'MARKDOWN'
<!-- akashi: skip -->
```php
$value=1;
```
MARKDOWN);
        $example = (new CommonMarkExampleExtractor())->extract($document)[0];

        $this->expectException(FormattingRewriteException::class);
        $this->expectExceptionMessage('cannot be rendered safely');

        (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch($example, new ExampleCode("// akashi: skip\n\$value = 1;\n")),
        );
    }

    public function testRejectsFormatterOutputThatChangesAnExpectedException(): void
    {
        $document = new Document('docs/example.md', <<<'MARKDOWN'
```php
// akashi: expect-exception RuntimeException
throw new RuntimeException();
```
MARKDOWN);
        $example = (new CommonMarkExampleExtractor())->extract($document)[0];

        $this->expectException(FormattingRewriteException::class);
        $this->expectExceptionMessage('cannot be rendered safely');

        (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch(
                $example,
                new ExampleCode("// akashi: expect-exception LogicException\nthrow new RuntimeException();\n"),
            ),
        );
    }

    public function testRejectsFormatterOutputThatChangesAnExpectedExceptionMessage(): void
    {
        $document = new Document('docs/example.md', <<<'MARKDOWN'
```php
// akashi: expect-exception RuntimeException
// akashi: expect-exception-message original
throw new RuntimeException('original');
```
MARKDOWN);
        $example = (new CommonMarkExampleExtractor())->extract($document)[0];

        $this->expectException(FormattingRewriteException::class);
        $this->expectExceptionMessage('cannot be rendered safely');

        (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch(
                $example,
                new ExampleCode(
                    "// akashi: expect-exception RuntimeException\n"
                        . "// akashi: expect-exception-message changed\n"
                        . "throw new RuntimeException('original');\n",
                ),
            ),
        );
    }

    public function testRejectsFormatterOutputThatChangesAnExpectedExceptionCode(): void
    {
        $document = new Document('docs/example.md', <<<'MARKDOWN'
```php
// akashi: expect-exception RuntimeException
// akashi: expect-exception-code 73
throw new RuntimeException('documented', 73);
```
MARKDOWN);
        $example = (new CommonMarkExampleExtractor())->extract($document)[0];

        $this->expectException(FormattingRewriteException::class);
        $this->expectExceptionMessage('cannot be rendered safely');

        (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch(
                $example,
                new ExampleCode(
                    "// akashi: expect-exception RuntimeException\n"
                        . "// akashi: expect-exception-code 74\n"
                        . "throw new RuntimeException('documented', 73);\n",
                ),
            ),
        );
    }

    public function testRejectsFormatterOutputThatChangesExpectedOutput(): void
    {
        $document = new Document('docs/example.md', <<<'MARKDOWN'
```php
// akashi: expect-output="original\n"
echo "original\n";
```
MARKDOWN);
        $example = (new CommonMarkExampleExtractor())->extract($document)[0];

        $this->expectException(FormattingRewriteException::class);
        $this->expectExceptionMessage('cannot be rendered safely');

        (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch(
                $example,
                new ExampleCode("// akashi: expect-output=\"changed\\n\"\necho \"original\\n\";\n"),
            ),
        );
    }

    public function testAttributesNonUtf8FormatterOutputToTheInlineExample(): void
    {
        $document = new Document('docs/example.md', "```php\n\$value=1;\n```\n");
        $example = (new CommonMarkExampleExtractor())->extract($document)[0];

        $this->expectException(FormattingRewriteException::class);
        $this->expectExceptionMessage(
            'Formatter output for inline example ' . $example->corpusId->value . ' at docs/example.md:2 cannot be rendered safely.',
        );

        (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch($example, new ExampleCode("\$value = \"\xFF\";\n")),
        );
    }

    public function testRejectsFormatterOutputWithoutANewlineBeforeAClosingFence(): void
    {
        $document = new Document('docs/example.md', "```php\n\$value=1;\n```\n");
        $example = (new CommonMarkExampleExtractor())->extract($document)[0];

        $this->expectException(FormattingRewriteException::class);
        $this->expectExceptionMessage('cannot be rendered safely');

        (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch($example, new ExampleCode('$value = 1;')),
        );
    }

    public function testRejectsUnsupportedDocumentExtensions(): void
    {
        $document = new Document('docs/example.txt', "```php\n\$value=1;\n```\n");
        $example = (new CommonMarkExampleExtractor())->extract($document)[0];

        $this->expectException(FormattingRewriteException::class);
        $this->expectExceptionMessage('require a case-sensitive .md or .php document');

        (new FormattingRewriter())->rewrite(
            $document,
            new FormattingMismatch($example, new ExampleCode("\$value = 1;\n")),
        );
    }
}
