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

namespace jbboehr\Akashi\Tests\Model;

use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use jbboehr\Akashi\Model\MetadataLocation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SourceLocationTest extends TestCase
{
    public function testRepresentsAClosedNonemptyFence(): void
    {
        $location = new SourceLocation(4, 5, 7, 8, new SourceSpan(10, 50), new SourceSpan(20, 40));

        self::assertSame(4, $location->openingFenceLine);
        self::assertSame(5, $location->firstCodeLine);
        self::assertSame(7, $location->lastCodeLine);
        self::assertSame(8, $location->closingFenceLine);
        self::assertSame(10, $location->fenceSpan->startOffset);
        self::assertSame(50, $location->fenceSpan->endOffsetExclusive);
        self::assertSame(20, $location->codeSpan->startOffset);
        self::assertSame(40, $location->codeSpan->endOffsetExclusive);
    }

    public function testRepresentsAClosedEmptyFence(): void
    {
        $location = new SourceLocation(4, 5, null, 5, new SourceSpan(10, 30), new SourceSpan(20, 20));

        self::assertNull($location->lastCodeLine);
        self::assertSame(5, $location->closingFenceLine);
    }

    public function testRepresentsAnUnclosedFence(): void
    {
        $location = new SourceLocation(4, 5, 7, null, new SourceSpan(10, 40), new SourceSpan(20, 40));

        self::assertSame(7, $location->lastCodeLine);
        self::assertNull($location->closingFenceLine);
    }

    #[DataProvider('invalidLocationProvider')]
    public function testRejectsInvalidLocations(
        int $opening,
        int $first,
        ?int $last,
        ?int $closing,
        string $message,
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $unexpectedLocation = (new \ReflectionClass(SourceLocation::class))->newInstanceArgs([
            $opening,
            $first,
            $last,
            $closing,
            new SourceSpan(0, 10),
            new SourceSpan(2, $last === null ? 2 : 8),
        ]);

        self::fail('Unexpectedly constructed ' . $unexpectedLocation::class . '.');
    }

    /**
     * @return iterable<string, array{int, int, ?int, ?int, string}>
     */
    public static function invalidLocationProvider(): iterable
    {
        yield 'nonpositive opening' => [0, 1, null, 1, 'Opening fence line must be positive.'];
        yield 'first line gap' => [1, 3, null, 3, 'First code line must immediately follow the opening fence.'];
        yield 'last precedes first' => [1, 2, 1, null, 'Last code line must not precede the first code line.'];
        yield 'empty fence gap' => [1, 2, null, 3, 'Closing fence line must immediately follow the code content.'];
        yield 'closing fence gap' => [1, 2, 2, 4, 'Closing fence line must immediately follow the code content.'];
        yield 'closing fence overlaps code' => [1, 2, 3, 3, 'Closing fence line must immediately follow the code content.'];
    }

    public function testRejectsAnEmptyFenceSpan(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Fence source span must not be empty.');

        new SourceLocation(1, 2, null, 2, new SourceSpan(4, 4), new SourceSpan(4, 4));
    }

    public function testRejectsACodeSpanOutsideTheFenceSpan(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Code source span must be contained within the fence source span.');

        new SourceLocation(1, 2, 2, 3, new SourceSpan(4, 20), new SourceSpan(3, 12));
    }

    public function testRejectsANonemptySpanForAnEmptyCodeLocation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('An empty code location must have an empty source span.');

        new SourceLocation(1, 2, null, 2, new SourceSpan(4, 20), new SourceSpan(8, 9));
    }

    /**
     * @param positive-int|null $markerLine
     * @param positive-int|null $directiveLine
     * @param positive-int|null $skipDirectiveLine
     */
    #[DataProvider('invalidMetadataLineProvider')]
    public function testRejectsMetadataThatDoesNotPrecedeTheFence(
        ?int $markerLine,
        ?int $directiveLine,
        ?int $skipDirectiveLine,
        string $message,
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new SourceLocation(
            4,
            5,
            5,
            6,
            new SourceSpan(10, 50),
            new SourceSpan(20, 40),
            new MetadataLocation($markerLine, $directiveLine, $skipDirectiveLine),
        );
    }

    /**
     * @return iterable<string, array{?int, ?int, ?int, string}>
     */
    public static function invalidMetadataLineProvider(): iterable
    {
        yield 'marker on fence' => [4, null, null, 'Marker line must precede the opening fence.'];
        yield 'separate-process directive after fence' => [
            null,
            5,
            null,
            'Separate-process directive line must precede the opening fence.',
        ];
        yield 'skip directive after fence' => [
            null,
            null,
            5,
            'Skip directive line must precede the opening fence.',
        ];
    }
}
