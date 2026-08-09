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

use jbboehr\Akashi\Model\ExpectedException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExpectedExceptionTest extends TestCase
{
    #[DataProvider('validClassNameProvider')]
    public function testNormalizesAValidGlobalClassName(string $authored, string $normalized): void
    {
        self::assertSame($normalized, (new ExpectedException($authored))->className);
    }

    /** @return iterable<string, array{string, non-empty-string}> */
    public static function validClassNameProvider(): iterable
    {
        yield 'global built-in' => ['RuntimeException', 'RuntimeException'];
        yield 'leading separator' => ['\\Domain\\DocumentationException', 'Domain\\DocumentationException'];
        yield 'surrounding whitespace' => ['  Domain\\Failure  ', 'Domain\\Failure'];
        yield 'extended identifier bytes' => ['Domain\\Échec', 'Domain\\Échec'];
    }

    #[DataProvider('invalidClassNameProvider')]
    public function testRejectsAnInvalidClassName(string $className): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected exception class must be a syntactically valid global PHP class name.',
        );

        new ExpectedException($className);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidClassNameProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'only whitespace' => ['  '];
        yield 'two leading separators' => ['\\\\RuntimeException'];
        yield 'trailing separator' => ['Domain\\'];
        yield 'empty segment' => ['Domain\\\\Failure'];
        yield 'invalid first character' => ['2FastException'];
        yield 'class constant expression' => ['RuntimeException::class'];
    }
}
