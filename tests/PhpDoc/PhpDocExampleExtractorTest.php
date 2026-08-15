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

namespace jbboehr\Akashi\Tests\PhpDoc;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Example;
use jbboehr\Akashi\Markdown\Exception\OrphanedMarkerException;
use jbboehr\Akashi\Model\Directive;
use jbboehr\Akashi\Model\InlineExampleSource;
use jbboehr\Akashi\Model\MarkerName;
use jbboehr\Akashi\PhpDoc\PhpDocExampleExtractor;
use PHPUnit\Framework\TestCase;

final class PhpDocExampleExtractorTest extends TestCase
{
    public function testExtractsEveryPhpFenceAndRestoresOriginalPhpLocations(): void
    {
        $contents = <<<'PHP'
<?php

/**
 * Demonstrates a value.
 *
 * <!-- akashi-example: first-value -->
 * <!-- akashi: separate-process -->
 *
 * ```PHP extra
 * $value = 41 + 1;
 * assert($value === 42);
 * ```
 */
function answer(): int
{
    return 42;
}

/**
 * ```php
 * // akashi: expect-exception RuntimeException
 * // akashi: expect-exception-message expected
 * // akashi: expect-exception-code 73
 * throw new RuntimeException('expected', 73);
 * ```
 */
PHP;
        $document = new Document('src/answer.php', str_replace("\n", "\r\n", $contents));

        $examples = (new PhpDocExampleExtractor(new MarkerName('akashi-example')))->extract($document);

        self::assertCount(2, $examples);
        self::assertSame('src/answer.php PHPDoc example 1', $examples[0]->label);
        self::assertSame('src/answer.php PHPDoc example 2', $examples[1]->label);
        self::assertSame('first-value', $examples[0]->explicitMarkerId?->value);
        self::assertTrue($examples[0]->directives->contains(Directive::SeparateProcess));
        self::assertSame("\$value = 41 + 1;\r\nassert(\$value === 42);\r\n", $examples[0]->code->source);
        self::assertSame(9, $this->inlineSource($examples[0])->location->openingFenceLine);
        self::assertSame(10, $this->inlineSource($examples[0])->location->firstCodeLine);
        self::assertSame(11, $this->inlineSource($examples[0])->location->lastCodeLine);
        self::assertSame(12, $this->inlineSource($examples[0])->location->closingFenceLine);
        self::assertSame(6, $this->inlineSource($examples[0])->location->metadata->markerLine);
        self::assertSame(7, $this->inlineSource($examples[0])->location->metadata->separateProcessDirectiveLine);
        self::assertSame('RuntimeException', $examples[1]->expectedException?->className);
        self::assertSame('expected', $examples[1]->expectedException->message);
        self::assertSame(73, $examples[1]->expectedException->code);
        self::assertSame(21, $this->inlineSource($examples[1])->location->metadata->expectedExceptionDirectiveLine);
        self::assertSame(
            " * ```PHP extra\r\n * \$value = 41 + 1;\r\n * assert(\$value === 42);\r\n * ```\r\n",
            $document->lines->slice($this->inlineSource($examples[0])->location->fenceSpan),
        );
        self::assertSame(
            " * \$value = 41 + 1;\r\n * assert(\$value === 42);\r\n",
            $document->lines->slice($this->inlineSource($examples[0])->location->codeSpan),
        );
    }

    public function testExtractsUnattachedDocblocksAndAssignsFileWideOrdinals(): void
    {
        $document = new Document('src/examples.php', <<<'PHP'
<?php

/**
 * ```php
 * echo 'first';
 * ```
 */

$value = 1;

/**
 * ```text
 * ignored
 * ```
 *
 * ```php
 * echo 'second';
 * ```
 */
PHP);

        $examples = (new PhpDocExampleExtractor())->extract($document);

        self::assertCount(2, $examples);
        self::assertSame(['example-47019d4862eb-01', 'example-47019d4862eb-02'], [
            $examples[0]->id->value,
            $examples[1]->id->value,
        ]);
        self::assertSame("echo 'first';\n", $examples[0]->code->source);
        self::assertSame("echo 'second';\n", $examples[1]->code->source);
    }

    public function testDoesNotAssociateMetadataAcrossDocblockBoundaries(): void
    {
        $document = new Document('src/examples.php', <<<'PHP'
<?php
/**
 * <!-- akashi-example: misplaced -->
 */
/**
 * ```php
 * echo 'separate';
 * ```
 */
PHP);

        $this->expectException(OrphanedMarkerException::class);
        $this->expectExceptionMessage(
            'Marker misplaced at src/examples.php:3 is not followed by a fenced code block.',
        );

        (new PhpDocExampleExtractor(new MarkerName('akashi-example')))->extract($document);
    }

    public function testIgnoresOrdinaryCommentsAndFenceContentOnDelimiterLines(): void
    {
        $document = new Document('src/examples.php', <<<'PHP'
<?php
/*
 * ```php
 * echo 'ordinary';
 * ```
 */
/** ```php
 * echo 'opening-line';
 * ``` */
PHP);

        self::assertSame([], (new PhpDocExampleExtractor())->extract($document));
    }

    private function inlineSource(Example $example): InlineExampleSource
    {
        self::assertInstanceOf(InlineExampleSource::class, $example->source);

        return $example->source;
    }
}
