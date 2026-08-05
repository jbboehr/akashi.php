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

use jbboehr\Akashi\Model\MetadataLocation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MetadataLocationTest extends TestCase
{
    public function testDefaultsToNoAssociatedMetadataLines(): void
    {
        $location = new MetadataLocation();

        self::assertNull($location->markerLine);
        self::assertNull($location->separateProcessDirectiveLine);
    }

    public function testPreservesAssociatedMetadataLines(): void
    {
        $location = new MetadataLocation(3, 5);

        self::assertSame(3, $location->markerLine);
        self::assertSame(5, $location->separateProcessDirectiveLine);
    }

    #[DataProvider('invalidLineProvider')]
    public function testRejectsNonpositiveMetadataLines(?int $markerLine, ?int $directiveLine, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new MetadataLocation($markerLine, $directiveLine);
    }

    /**
     * @return iterable<string, array{?int, ?int, string}>
     */
    public static function invalidLineProvider(): iterable
    {
        yield 'marker' => [0, null, 'Marker line must be positive.'];
        yield 'directive' => [null, -1, 'Directive line must be positive.'];
    }
}
