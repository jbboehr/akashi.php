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

namespace jbboehr\Akashi\Tests\Integration\PhpUnit;

use jbboehr\Akashi\Integration\PhpUnit\NativeAssertion;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class NativeAssertionTest extends TestCase
{
    public function testRecordsATruthyValueAndReturnsTrue(): void
    {
        $before = Assert::getCount();

        $result = (new \ReflectionMethod(NativeAssertion::class, 'evaluate'))->invoke(
            null,
            1,
            null,
            '$value',
            'docs/example.md',
            12,
        );
        $after = Assert::getCount();

        self::assertTrue($result);
        self::assertSame($before + 1, $after);
    }

    public function testReportsTheExactExpressionAndMaintainedLocationByDefault(): void
    {
        $failure = null;
        try {
            NativeAssertion::evaluate([], null, '$value === 1', 'docs/guide.md', 27);
        } catch (\Throwable $throwable) {
            $failure = $throwable;
        }

        self::assertNotNull($failure, 'A false native assertion must fail.');
        self::assertTrue(is_a($failure, 'PHPUnit\Framework\AssertionFailedError'));
        self::assertStringContainsString('docs/guide.md:27: assert($value === 1)', $failure->getMessage());
    }

    public function testPreservesAnAuthoredStringDescription(): void
    {
        $failure = null;
        try {
            NativeAssertion::evaluate(false, 'Conversion must be exact.', '$result === 100', 'docs/guide.md', 31);
        } catch (\Throwable $throwable) {
            $failure = $throwable;
        }

        self::assertNotNull($failure, 'A false native assertion must fail.');
        self::assertTrue(is_a($failure, 'PHPUnit\Framework\AssertionFailedError'));
        self::assertStringContainsString('Conversion must be exact.', $failure->getMessage());
        self::assertStringNotContainsString('assert($result === 100)', $failure->getMessage());
    }

    public function testThrowsAnAuthoredThrowableWhenTheAssertionFails(): void
    {
        $description = new \DomainException('Conversion failed.');

        try {
            NativeAssertion::evaluate(false, $description, '$result === 100', 'docs/guide.md', 31);
            self::fail('A throwable assertion description must be thrown on failure.');
        } catch (\DomainException $failure) {
            self::assertSame($description, $failure);
        }
    }

    public function testDoesNotThrowAnAuthoredThrowableWhenTheAssertionPasses(): void
    {
        $before = Assert::getCount();

        $result = (new \ReflectionMethod(NativeAssertion::class, 'evaluate'))->invoke(
            null,
            new \stdClass(),
            new \DomainException('Unused description.'),
            '$object',
            'docs/guide.md',
            40,
        );
        $after = Assert::getCount();

        self::assertTrue($result);
        self::assertSame($before + 1, $after);
    }
}
