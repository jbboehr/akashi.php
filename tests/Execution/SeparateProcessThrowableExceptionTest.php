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

namespace jbboehr\Akashi\Tests\Execution;

use jbboehr\Akashi\Execution\Exception\SeparateProcessThrowableException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SeparateProcessThrowableExceptionTest extends TestCase
{
    public function testPreservesValidatedChildThrowableEvidence(): void
    {
        $exception = new SeparateProcessThrowableException(
            \RuntimeException::class,
            [\RuntimeException::class, \Exception::class, \Stringable::class, \Throwable::class],
            "invalid \xFF input",
            -73,
            true,
            true,
        );

        self::assertSame(\RuntimeException::class, $exception->actualClassName);
        self::assertSame(
            [\RuntimeException::class, \Exception::class, \Stringable::class, \Throwable::class],
            $exception->typeNames,
        );
        self::assertSame("invalid \xFF input", $exception->getMessage());
        self::assertSame(-73, $exception->getCode());
        self::assertSame(-73, $exception->actualCode);
        self::assertTrue($exception->expectedTypeAvailable);
        self::assertTrue($exception->matchesExpectedType);
    }

    public function testPreservesAStringCodeWithoutMisrepresentingItAsTheParentExceptionCode(): void
    {
        $exception = new SeparateProcessThrowableException(
            'DatabaseException',
            ['DatabaseException', \RuntimeException::class, \Exception::class, \Throwable::class],
            'database failure',
            'HY000',
            true,
            true,
        );

        self::assertSame('HY000', $exception->actualCode);
        self::assertSame(0, $exception->getCode());
    }

    /** @param array<int, mixed> $typeNames */
    #[DataProvider('invalidEvidenceProvider')]
    public function testRejectsContradictoryEvidence(
        string $actualClassName,
        array $typeNames,
        bool $expectedTypeAvailable,
        bool $matchesExpectedType,
        string $expectedMessage,
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        new SeparateProcessThrowableException(
            $actualClassName,
            $typeNames,
            'message',
            0,
            $expectedTypeAvailable,
            $matchesExpectedType,
        );
    }

    /** @return iterable<string, array{string, array<int, mixed>, bool, bool, string}> */
    public static function invalidEvidenceProvider(): iterable
    {
        yield 'empty class' => ['', [''], false, false, 'class name must not be empty'];
        yield 'empty ancestry' => [\RuntimeException::class, [], true, true, 'nonempty list'];
        yield 'non-list ancestry' => [
            \RuntimeException::class,
            [1 => \RuntimeException::class],
            true,
            true,
            'nonempty list',
        ];
        yield 'wrong first type' => [
            \RuntimeException::class,
            [\Exception::class],
            true,
            true,
            'beginning with its actual class',
        ];
        yield 'non-string type' => [
            \RuntimeException::class,
            [\RuntimeException::class, 1],
            true,
            true,
            'invalid type name',
        ];
        yield 'empty type' => [
            \RuntimeException::class,
            [\RuntimeException::class, ''],
            true,
            true,
            'invalid type name',
        ];
        yield 'case-insensitive duplicate type' => [
            \RuntimeException::class,
            [\RuntimeException::class, strtolower(\RuntimeException::class)],
            true,
            true,
            'duplicate types',
        ];
        yield 'non-Throwable ancestry' => [
            \RuntimeException::class,
            [\RuntimeException::class],
            true,
            true,
            'must contain Throwable',
        ];
        yield 'match without availability' => [
            \RuntimeException::class,
            [\RuntimeException::class, \Throwable::class],
            false,
            true,
            'cannot match an unavailable expected type',
        ];
    }
}
