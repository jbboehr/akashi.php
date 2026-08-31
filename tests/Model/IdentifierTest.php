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

use jbboehr\Akashi\Model\CorpusExampleId;
use jbboehr\Akashi\Model\InvalidNamedExampleIdException;
use jbboehr\Akashi\Model\NamedExampleId;
use jbboehr\Akashi\Model\LegacyMarkerName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IdentifierTest extends TestCase
{
    public function testAcceptsFileSafeCorpusExampleIds(): void
    {
        self::assertSame('example-a1_b.c-2', (new CorpusExampleId('example-a1_b.c-2'))->value);
    }

    #[DataProvider('invalidCorpusExampleIdProvider')]
    public function testRejectsInvalidCorpusExampleIds(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Corpus example ID must be a lowercase file-safe identifier.');

        new CorpusExampleId($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidCorpusExampleIdProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'uppercase' => ['Example-01'];
        yield 'punctuation' => ['example-01!'];
        yield 'repeated separator' => ['example--01'];
        yield 'leading separator' => ['-example-01'];
        yield 'trailing separator' => ['example-01_'];
    }

    public function testAcceptsKebabCaseNamedExampleIds(): void
    {
        self::assertSame('selected-example-2', (new NamedExampleId('selected-example-2'))->value);
    }

    public function testAcceptsKebabCaseLegacyMarkerNames(): void
    {
        self::assertSame('yumemi-example', (new LegacyMarkerName('yumemi-example'))->value);
    }

    #[DataProvider('invalidLegacyMarkerNameProvider')]
    public function testRejectsInvalidOrReservedLegacyMarkerNames(string $value, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new LegacyMarkerName($value);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidLegacyMarkerNameProvider(): iterable
    {
        yield 'empty' => ['', 'Legacy marker name must use lowercase kebab-case.'];
        yield 'uppercase' => ['Yumemi-example', 'Legacy marker name must use lowercase kebab-case.'];
        yield 'underscore' => ['yumemi_example', 'Legacy marker name must use lowercase kebab-case.'];
        yield 'reserved directive prefix' => ['akashi', 'Legacy marker name akashi is reserved for Akashi directives.'];
    }

    #[DataProvider('invalidNamedExampleIdProvider')]
    public function testRejectsInvalidNamedExampleIds(string $value): void
    {
        $this->expectException(InvalidNamedExampleIdException::class);
        $this->expectExceptionMessage('Named example ID must use lowercase kebab-case.');

        new NamedExampleId($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidNamedExampleIdProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'uppercase' => ['Selected-example'];
        yield 'underscore' => ['selected_example'];
        yield 'repeated separator' => ['selected--example'];
        yield 'leading separator' => ['-selected-example'];
        yield 'trailing separator' => ['selected-example-'];
    }
}
