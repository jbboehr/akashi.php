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

namespace jbboehr\Akashi\Tests\Markdown;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Example;
use jbboehr\Akashi\Markdown\CommonMarkExampleExtractor;
use jbboehr\Akashi\Model\FenceCharacter;
use PHPUnit\Framework\TestCase;

final class CommonMarkExampleExtractorTest extends TestCase
{
    private CommonMarkExampleExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new CommonMarkExampleExtractor();
    }

    public function testExtractsCommonMarkFenceFormsInSourceOrder(): void
    {
        $document = $this->fixture('fixtures/commonmark.md', 'commonmark.txt');
        $examples = $this->extractor->extract($document);

        self::assertCount(4, $examples);
        self::assertSame([1, 2, 3, 4], array_map(static fn (Example $example): int => $example->ordinal, $examples));
        self::assertSame(
            [
                'fixtures/commonmark.md PHP example 1',
                'fixtures/commonmark.md PHP example 2',
                'fixtures/commonmark.md PHP example 3',
                'fixtures/commonmark.md PHP example 4',
            ],
            array_map(static fn (Example $example): string => $example->label, $examples),
        );
        self::assertSame(
            [
                "<?php\necho \"```\";\n",
                "<?php\necho 'indented';\n",
                "<?php\necho 'quoted';\n",
                "<?php\necho \"```\";\n",
            ],
            array_map(static fn (Example $example): string => $example->code->source, $examples),
        );

        self::assertSame('php extra', $examples[0]->fence->infoString);
        self::assertSame(FenceCharacter::Backtick, $examples[0]->fence->character);
        self::assertSame(4, $examples[0]->fence->length);
        self::assertSame(0, $examples[0]->fence->indentation);
        self::assertSame('PHP container-metadata', $examples[1]->fence->infoString);
        self::assertSame(FenceCharacter::Tilde, $examples[1]->fence->character);
        self::assertSame(3, $examples[1]->fence->length);
        self::assertSame(3, $examples[1]->fence->indentation);
        self::assertSame('php quote', $examples[2]->fence->infoString);
        self::assertSame(0, $examples[2]->fence->indentation);
        self::assertSame('php unclosed', $examples[3]->fence->infoString);
    }

    public function testMapsClosedAndUnclosedFenceLocationsAndRawSpans(): void
    {
        $document = $this->fixture('fixtures/commonmark.md', 'commonmark.txt');
        $examples = $this->extractor->extract($document);

        self::assertSame([3, 4, 5, 6], [
            $examples[0]->location->openingFenceLine,
            $examples[0]->location->firstCodeLine,
            $examples[0]->location->lastCodeLine,
            $examples[0]->location->closingFenceLine,
        ]);
        self::assertSame(
            "````php extra\n<?php\necho \"```\";\n`````\n",
            $document->lines->slice($examples[0]->location->fenceSpan),
        );
        self::assertSame(
            "<?php\necho \"```\";\n",
            $document->lines->slice($examples[0]->location->codeSpan),
        );

        self::assertSame([13, 14, 15, 16], [
            $examples[2]->location->openingFenceLine,
            $examples[2]->location->firstCodeLine,
            $examples[2]->location->lastCodeLine,
            $examples[2]->location->closingFenceLine,
        ]);
        self::assertSame(
            "> <?php\n> echo 'quoted';\n",
            $document->lines->slice($examples[2]->location->codeSpan),
        );
        self::assertNotSame(
            $examples[2]->code->source,
            $document->lines->slice($examples[2]->location->codeSpan),
        );

        self::assertSame([26, 27, 28, null], [
            $examples[3]->location->openingFenceLine,
            $examples[3]->location->firstCodeLine,
            $examples[3]->location->lastCodeLine,
            $examples[3]->location->closingFenceLine,
        ]);
        self::assertSame(
            "`````php unclosed\n<?php\necho \"```\";\n",
            $document->lines->slice($examples[3]->location->fenceSpan),
        );
    }

    public function testPreservesOriginalCrLfInSemanticCodeAndRawSpans(): void
    {
        $contents = "before\r\n```PHP metadata\r\n<?php\r\necho 1;\r\n```\r\nafter\r\n";
        $document = new Document('docs/crlf.md', $contents);
        $examples = $this->extractor->extract($document);

        self::assertCount(1, $examples);
        self::assertSame("<?php\r\necho 1;\r\n", $examples[0]->code->source);
        self::assertSame(
            "```PHP metadata\r\n<?php\r\necho 1;\r\n```\r\n",
            $document->lines->slice($examples[0]->location->fenceSpan),
        );
        self::assertSame(
            "<?php\r\necho 1;\r\n",
            $document->lines->slice($examples[0]->location->codeSpan),
        );
    }

    public function testRemovesListContainerSyntaxFromSemanticCodeOnly(): void
    {
        $document = new Document(
            'docs/list.md',
            "-   ```php list\n    <?php\n    echo 'listed';\n    ```\n",
        );
        $examples = $this->extractor->extract($document);

        self::assertCount(1, $examples);
        self::assertSame("<?php\necho 'listed';\n", $examples[0]->code->source);
        self::assertSame(
            "    <?php\n    echo 'listed';\n",
            $document->lines->slice($examples[0]->location->codeSpan),
        );
        self::assertSame(0, $examples[0]->fence->indentation);
    }

    public function testRepresentsClosedAndUnclosedEmptyFences(): void
    {
        $document = new Document('docs/empty.md', "~~~php\n~~~\n\n```PHP");
        $examples = $this->extractor->extract($document);

        self::assertCount(2, $examples);
        self::assertSame('', $examples[0]->code->source);
        self::assertNull($examples[0]->location->lastCodeLine);
        self::assertSame(2, $examples[0]->location->closingFenceLine);
        self::assertSame(
            $examples[0]->location->codeSpan->startOffset,
            $examples[0]->location->codeSpan->endOffsetExclusive,
        );
        self::assertSame('', $examples[1]->code->source);
        self::assertNull($examples[1]->location->lastCodeLine);
        self::assertNull($examples[1]->location->closingFenceLine);
    }

    public function testRequiresAnExactCaseInsensitivePhpInfoWord(): void
    {
        $document = new Document(
            'docs/languages.md',
            "```php8\nignored();\n```\n\n```php\\+escaped\nignored();\n```\n\n```PhP extra\nselected();\n```\n",
        );
        $examples = $this->extractor->extract($document);

        self::assertCount(1, $examples);
        self::assertSame("selected();\n", $examples[0]->code->source);
        self::assertSame(1, $examples[0]->ordinal);
    }

    public function testDefersToCommonMarkForInvalidFenceOpeners(): void
    {
        $document = new Document(
            'docs/invalid-openers.md',
            "    ```php\n    indentedCode();\n    ```\n\n```php`invalid\nnotAFence();\n```\n",
        );

        self::assertSame([], $this->extractor->extract($document));
    }

    public function testPreservesReducedYumemiCodeAsAByteIdenticalTopLevelSlice(): void
    {
        $document = $this->fixture('fixtures/yumemi.md', 'yumemi.md');
        $examples = $this->extractor->extract($document);

        self::assertCount(1, $examples);
        self::assertSame(
            $document->lines->slice($examples[0]->location->codeSpan),
            $examples[0]->code->source,
        );
        self::assertSame('example-c2bba259c960-01', $examples[0]->id->value);
        self::assertNull($examples[0]->explicitMarkerId);
    }

    private function fixture(string $path, string $file): Document
    {
        $contents = file_get_contents(__DIR__ . '/../Fixtures/Markdown/' . $file);
        self::assertNotFalse($contents);

        return new Document($path, $contents);
    }
}
