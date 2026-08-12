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

namespace jbboehr\Akashi\Tests\Synchronization;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\ProjectPath;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use jbboehr\Akashi\Synchronization\SynchronizationRegion;
use PHPUnit\Framework\TestCase;

final class SynchronizationRegionTest extends TestCase
{
    public function testRetainsACompleteValidRegion(): void
    {
        [$document, $location, $span] = $this->coordinates();

        $region = new SynchronizationRegion(
            $document,
            new ProjectPath('examples/example.php'),
            null,
            new ExampleCode("echo 1;\n"),
            $location,
            new FenceMetadata('php', '`', 3, 0),
            1,
            5,
            $span,
        );

        self::assertSame($document, $region->document);
        self::assertSame('examples/example.php', $region->targetPath->value);
        self::assertSame(1, $region->directiveLine);
        self::assertSame(5, $region->endDirectiveLine);
        self::assertSame($span, $region->regionSpan);
    }

    public function testRejectsAStartDirectiveThatDoesNotPrecedeTheFence(): void
    {
        [$document, $location, $span] = $this->coordinates();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('start directive must precede');

        new SynchronizationRegion(
            $document,
            new ProjectPath('examples/example.php'),
            null,
            new ExampleCode("echo 1;\n"),
            $location,
            new FenceMetadata('php', '`', 3, 0),
            2,
            5,
            $span,
        );
    }

    public function testRejectsAnEndDirectiveThatDoesNotFollowTheFence(): void
    {
        [$document, $location, $span] = $this->coordinates();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('end directive must follow');

        new SynchronizationRegion(
            $document,
            new ProjectPath('examples/example.php'),
            null,
            new ExampleCode("echo 1;\n"),
            $location,
            new FenceMetadata('php', '`', 3, 0),
            1,
            4,
            $span,
        );
    }

    public function testRejectsASpanThatDoesNotCoverTheAuthoredRegion(): void
    {
        [$document, $location] = $this->coordinates();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('span must cover its complete authored region');

        new SynchronizationRegion(
            $document,
            new ProjectPath('examples/example.php'),
            null,
            new ExampleCode("echo 1;\n"),
            $location,
            new FenceMetadata('php', '`', 3, 0),
            1,
            5,
            new SourceSpan($document->lines->lineStartOffset(2), $document->lines->lineStartOffset(6)),
        );
    }

    public function testAcceptsBlankContainerLinesBetweenTheDelimitersAndFence(): void
    {
        $document = new Document('src/Example.php', <<<'PHP'
 * <!-- akashi-sync: examples/example.php -->
 *
 * ```php
 * echo 1;
 * ```
 *
 * <!-- akashi-sync-end -->
PHP);
        $location = new SourceLocation(
            3,
            4,
            4,
            5,
            new SourceSpan($document->lines->lineStartOffset(3), $document->lines->lineStartOffset(6)),
            new SourceSpan($document->lines->lineStartOffset(4), $document->lines->lineStartOffset(5)),
        );

        $region = new SynchronizationRegion(
            $document,
            new ProjectPath('examples/example.php'),
            null,
            new ExampleCode("echo 1;\n"),
            $location,
            new FenceMetadata('php', '`', 3, 0),
            1,
            7,
            new SourceSpan(0, $document->lines->lineStartOffset(8)),
        );

        self::assertSame(1, $region->directiveLine);
        self::assertSame(7, $region->endDirectiveLine);
    }

    public function testRejectsNonblankContentBetweenAStartDirectiveAndFence(): void
    {
        $document = new Document('README.md', <<<'MARKDOWN'
<!-- akashi-sync: examples/example.php -->
prose
```php
echo 1;
```
<!-- akashi-sync-end -->
MARKDOWN);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('may be separated only by blank lines');

        new SynchronizationRegion(
            $document,
            new ProjectPath('examples/example.php'),
            null,
            new ExampleCode("echo 1;\n"),
            new SourceLocation(
                3,
                4,
                4,
                5,
                new SourceSpan(
                    $document->lines->lineStartOffset(3),
                    $document->lines->lineStartOffset(6),
                ),
                new SourceSpan(
                    $document->lines->lineStartOffset(4),
                    $document->lines->lineStartOffset(5),
                ),
            ),
            new FenceMetadata('php', '`', 3, 0),
            1,
            6,
            new SourceSpan(0, $document->lines->lineStartOffset(7)),
        );
    }

    public function testRejectsNonblankContentBetweenAClosingFenceAndEndDirective(): void
    {
        $document = new Document('README.md', <<<'MARKDOWN'
<!-- akashi-sync: examples/example.php -->
```php
echo 1;
```
prose
<!-- akashi-sync-end -->
MARKDOWN);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('may be separated only by blank lines');

        new SynchronizationRegion(
            $document,
            new ProjectPath('examples/example.php'),
            null,
            new ExampleCode("echo 1;\n"),
            new SourceLocation(
                2,
                3,
                3,
                4,
                new SourceSpan(
                    $document->lines->lineStartOffset(2),
                    $document->lines->lineStartOffset(5),
                ),
                new SourceSpan(
                    $document->lines->lineStartOffset(3),
                    $document->lines->lineStartOffset(4),
                ),
            ),
            new FenceMetadata('php', '`', 3, 0),
            1,
            6,
            new SourceSpan(0, $document->lines->lineStartOffset(7)),
        );
    }

    /** @return array{Document, SourceLocation, SourceSpan} */
    private function coordinates(): array
    {
        $document = new Document('README.md', <<<'MARKDOWN'
<!-- akashi-sync: examples/example.php -->
```php
echo 1;
```
<!-- akashi-sync-end -->
MARKDOWN);

        return [
            $document,
            new SourceLocation(
                2,
                3,
                3,
                4,
                new SourceSpan(
                    $document->lines->lineStartOffset(2),
                    $document->lines->lineStartOffset(5),
                ),
                new SourceSpan(
                    $document->lines->lineStartOffset(3),
                    $document->lines->lineStartOffset(4),
                ),
            ),
            new SourceSpan(
                $document->lines->lineStartOffset(1),
                $document->lines->lineStartOffset(6),
            ),
        ];
    }
}
