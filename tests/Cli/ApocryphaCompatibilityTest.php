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

namespace jbboehr\Akashi\Tests\Cli;

use jbboehr\Akashi\Application;
use jbboehr\Akashi\Cli\ExitCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ApocryphaCompatibilityTest extends TestCase
{
    #[DataProvider('markedExampleProvider')]
    public function testExtractsCapturedApocryphaExamplesByteForByte(string $markerId, string $expectedFile): void
    {
        $fixtures = __DIR__ . '/../Fixtures/Compatibility/Apocrypha';
        $file = $fixtures . '/marked-examples.md';
        $expected = file_get_contents($fixtures . '/expected/' . $expectedFile);
        self::assertNotFalse($expected);

        $stdout = '';
        $stderr = '';
        $status = Application::run(
            ['extract', '--marker-name=yumemi-example', $file, $markerId],
            static function (string $message) use (&$stdout): void {
                $stdout .= $message;
            },
            static function (string $message) use (&$stderr): void {
                $stderr .= $message;
            },
        );

        self::assertSame(ExitCode::Success->value, $status, $stderr);
        self::assertSame('', $stderr);
        self::assertSame($expected, $stdout);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function markedExampleProvider(): iterable
    {
        yield 'README cache example' => ['readme-cache-invalid', 'readme-cache-invalid.txt'];
        yield 'getting started example' => ['getting-started-invalid', 'getting-started-invalid.txt'];
        yield 'Guzzle example' => ['guzzle-invalid', 'guzzle-invalid.txt'];
        yield 'getID3 example' => ['getid3-invalid', 'getid3-invalid.txt'];
        yield 'Illuminate cache example' => ['illuminate-cache-invalid', 'illuminate-cache-invalid.txt'];
        yield 'Illuminate HTTP example' => ['illuminate-http-invalid', 'illuminate-http-invalid.txt'];
        yield 'PHPGeo example' => ['phpgeo-invalid', 'phpgeo-invalid.txt'];
        yield 'Symfony Stopwatch example' => ['symfony-stopwatch-invalid', 'symfony-stopwatch-invalid.txt'];
    }
}
