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
    public function testMatchesTheLegacyExtractorByteForByte(string $document, string $markerId): void
    {
        $root = realpath(__DIR__ . '/../../tmp/yumemi-apocrypha.php');
        if ($root === false) {
            self::markTestSkipped('The local Yumemi Apocrypha reference checkout is unavailable.');
        }

        $legacyExtractor = $root . '/tests/Documentation/MarkedCodeBlockExtractor.php';
        if (!is_file($legacyExtractor)) {
            self::markTestSkipped('The local Yumemi Apocrypha extractor is unavailable.');
        }

        require_once $legacyExtractor;

        $file = $root . '/' . $document;
        $legacyMethod = new \ReflectionMethod(
            'jbboehr\\Yumemi\\Apocrypha\\Tests\\Documentation\\MarkedCodeBlockExtractor',
            'extract',
        );
        $legacy = $legacyMethod->invoke(null, $file, $markerId);
        self::assertIsString($legacy);

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
        self::assertSame($legacy, $stdout);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function markedExampleProvider(): iterable
    {
        yield 'README cache example' => ['README.md', 'readme-cache-invalid'];
        yield 'getting started example' => ['docs/pages/getting-started.md', 'getting-started-invalid'];
        yield 'Guzzle example' => ['docs/pages/integrations.md', 'guzzle-invalid'];
        yield 'getID3 example' => ['docs/pages/integrations.md', 'getid3-invalid'];
        yield 'Illuminate cache example' => ['docs/pages/integrations.md', 'illuminate-cache-invalid'];
        yield 'Illuminate HTTP example' => ['docs/pages/integrations.md', 'illuminate-http-invalid'];
        yield 'PHPGeo example' => ['docs/pages/integrations.md', 'phpgeo-invalid'];
        yield 'Symfony Stopwatch example' => ['docs/pages/integrations.md', 'symfony-stopwatch-invalid'];
    }
}
