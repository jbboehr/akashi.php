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

use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\InvalidMarkerException;
use jbboehr\Akashi\Model\MarkerId;
use jbboehr\Akashi\Model\MarkerName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IdentifierTest extends TestCase
{
    public function testAcceptsFileSafeExampleIds(): void
    {
        self::assertSame('example-a1_b.c-2', (new ExampleId('example-a1_b.c-2'))->value);
    }

    #[DataProvider('invalidExampleIdProvider')]
    public function testRejectsInvalidExampleIds(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Example ID must be a lowercase file-safe identifier.');

        new ExampleId($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidExampleIdProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'uppercase' => ['Example-01'];
        yield 'punctuation' => ['example-01!'];
        yield 'repeated separator' => ['example--01'];
        yield 'leading separator' => ['-example-01'];
        yield 'trailing separator' => ['example-01_'];
    }

    public function testAcceptsKebabCaseMarkerIds(): void
    {
        self::assertSame('selected-example-2', (new MarkerId('selected-example-2'))->value);
    }

    public function testAcceptsKebabCaseMarkerNames(): void
    {
        self::assertSame('yumemi-example', (new MarkerName('yumemi-example'))->value);
    }

    #[DataProvider('invalidMarkerNameProvider')]
    public function testRejectsInvalidOrReservedMarkerNames(string $value, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new MarkerName($value);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidMarkerNameProvider(): iterable
    {
        yield 'empty' => ['', 'Marker name must use lowercase kebab-case.'];
        yield 'uppercase' => ['Yumemi-example', 'Marker name must use lowercase kebab-case.'];
        yield 'underscore' => ['yumemi_example', 'Marker name must use lowercase kebab-case.'];
        yield 'reserved directive prefix' => ['akashi', 'Marker name akashi is reserved for Akashi directives.'];
    }

    #[DataProvider('invalidMarkerIdProvider')]
    public function testRejectsInvalidMarkerIds(string $value): void
    {
        $this->expectException(InvalidMarkerException::class);
        $this->expectExceptionMessage('Marker ID must use lowercase kebab-case.');

        new MarkerId($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidMarkerIdProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'uppercase' => ['Selected-example'];
        yield 'underscore' => ['selected_example'];
        yield 'repeated separator' => ['selected--example'];
        yield 'leading separator' => ['-selected-example'];
        yield 'trailing separator' => ['selected-example-'];
    }
}
