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

namespace jbboehr\Akashi\Tests\Transform;

use jbboehr\Akashi\Transform\SourceEditApplier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SourceEditApplierTest extends TestCase
{
    public function testAppliesOrderedReplacementsAndInsertionsAgainstOriginalOffsets(): void
    {
        $result = SourceEditApplier::apply('abcdef', [
            ['start' => 1, 'end' => 3, 'replacement' => 'X'],
            ['start' => 4, 'end' => 4, 'replacement' => '!'],
        ]);

        self::assertSame('aXd!ef', $result);
    }

    public function testAcceptsAdjacentEdits(): void
    {
        $result = SourceEditApplier::apply('abcd', [
            ['start' => 0, 'end' => 1, 'replacement' => 'A'],
            ['start' => 1, 'end' => 2, 'replacement' => 'B'],
        ]);

        self::assertSame('ABcd', $result);
    }

    public function testRejectsOverlappingEdits(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('PHP source edits must not overlap.');

        SourceEditApplier::apply('abcdef', [
            ['start' => 1, 'end' => 4, 'replacement' => 'X'],
            ['start' => 3, 'end' => 5, 'replacement' => 'Y'],
        ]);
    }

    /**
     * @param array{start: non-negative-int, end: non-negative-int, replacement: string} $edit
     */
    #[DataProvider('invalidRangeProvider')]
    public function testRejectsInvalidEditRanges(array $edit): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('PHP source edit ranges must be ordered and within the source.');

        SourceEditApplier::apply('abc', [$edit]);
    }

    /**
     * @return iterable<string, array{array{start: non-negative-int, end: non-negative-int, replacement: string}}>
     */
    public static function invalidRangeProvider(): iterable
    {
        yield 'reversed' => [['start' => 2, 'end' => 1, 'replacement' => 'X']];
        yield 'past source' => [['start' => 3, 'end' => 4, 'replacement' => 'X']];
    }
}
