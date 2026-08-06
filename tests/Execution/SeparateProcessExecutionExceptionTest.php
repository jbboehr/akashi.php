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

use jbboehr\Akashi\Execution\Exception\SeparateProcessExecutionException;
use jbboehr\Akashi\Execution\SeparateProcessFailureKind;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SeparateProcessExecutionExceptionTest extends TestCase
{
    public function testRepresentsExitSignalAndTimeoutWithoutSymfonyTypes(): void
    {
        $exit = new SeparateProcessExecutionException(SeparateProcessFailureKind::Exit, 7);
        $lowestSignal = new SeparateProcessExecutionException(SeparateProcessFailureKind::Signal, null, 1);
        $signal = new SeparateProcessExecutionException(SeparateProcessFailureKind::Signal, 143, 15);
        $timeout = new SeparateProcessExecutionException(
            SeparateProcessFailureKind::Timeout,
            timeoutSeconds: 60.0,
        );

        self::assertSame(SeparateProcessFailureKind::Exit, $exit->kind);
        self::assertSame(7, $exit->exitCode);
        self::assertNull($exit->termSignal);
        self::assertNull($exit->timeoutSeconds);
        self::assertSame('Separate PHP process exited with status 7.', $exit->getMessage());

        self::assertSame(SeparateProcessFailureKind::Signal, $signal->kind);
        self::assertSame(143, $signal->exitCode);
        self::assertSame(15, $signal->termSignal);
        self::assertNull($signal->timeoutSeconds);
        self::assertSame('Separate PHP process was terminated by signal 15.', $signal->getMessage());
        self::assertSame(1, $lowestSignal->termSignal);

        self::assertSame(SeparateProcessFailureKind::Timeout, $timeout->kind);
        self::assertNull($timeout->exitCode);
        self::assertNull($timeout->termSignal);
        self::assertSame(60.0, $timeout->timeoutSeconds);
        self::assertSame('Separate PHP process exceeded the 60-second execution timeout.', $timeout->getMessage());
        $kindValues = array_map(
            static fn (\ReflectionEnumBackedCase $case): int|string => $case->getBackingValue(),
            (new \ReflectionEnum(SeparateProcessFailureKind::class))->getCases(),
        );
        self::assertSame(['exit', 'signal', 'timeout'], $kindValues);
    }

    /**
     * @return iterable<string, array{SeparateProcessFailureKind, int|null, int|null, float|null, string}>
     */
    public static function invalidFailureProvider(): iterable
    {
        yield 'exit without status' => [
            SeparateProcessFailureKind::Exit,
            null,
            null,
            null,
            'An exit failure requires a nonzero exit code and no signal or timeout.',
        ];
        yield 'successful exit' => [
            SeparateProcessFailureKind::Exit,
            0,
            null,
            null,
            'An exit failure requires a nonzero exit code and no signal or timeout.',
        ];
        yield 'exit with signal' => [
            SeparateProcessFailureKind::Exit,
            1,
            15,
            null,
            'An exit failure requires a nonzero exit code and no signal or timeout.',
        ];
        yield 'exit with timeout' => [
            SeparateProcessFailureKind::Exit,
            1,
            null,
            60.0,
            'An exit failure requires a nonzero exit code and no signal or timeout.',
        ];
        yield 'signal without number' => [
            SeparateProcessFailureKind::Signal,
            null,
            null,
            null,
            'A signal failure requires a positive signal and no successful exit or timeout.',
        ];
        yield 'nonpositive signal' => [
            SeparateProcessFailureKind::Signal,
            null,
            0,
            null,
            'A signal failure requires a positive signal and no successful exit or timeout.',
        ];
        yield 'signal with successful exit' => [
            SeparateProcessFailureKind::Signal,
            0,
            15,
            null,
            'A signal failure requires a positive signal and no successful exit or timeout.',
        ];
        yield 'signal with timeout' => [
            SeparateProcessFailureKind::Signal,
            null,
            15,
            60.0,
            'A signal failure requires a positive signal and no successful exit or timeout.',
        ];
        yield 'timeout without limit' => [
            SeparateProcessFailureKind::Timeout,
            null,
            null,
            null,
            'A timeout failure requires a finite positive timeout and no exit code or signal.',
        ];
        yield 'timeout with exit' => [
            SeparateProcessFailureKind::Timeout,
            1,
            null,
            60.0,
            'A timeout failure requires a finite positive timeout and no exit code or signal.',
        ];
        yield 'timeout with signal' => [
            SeparateProcessFailureKind::Timeout,
            null,
            15,
            60.0,
            'A timeout failure requires a finite positive timeout and no exit code or signal.',
        ];
        yield 'timeout with zero limit' => [
            SeparateProcessFailureKind::Timeout,
            null,
            null,
            0.0,
            'A timeout failure requires a finite positive timeout and no exit code or signal.',
        ];
        yield 'timeout with infinite limit' => [
            SeparateProcessFailureKind::Timeout,
            null,
            null,
            INF,
            'A timeout failure requires a finite positive timeout and no exit code or signal.',
        ];
    }

    #[DataProvider('invalidFailureProvider')]
    public function testRejectsContradictoryFailureMetadata(
        SeparateProcessFailureKind $kind,
        ?int $exitCode,
        ?int $termSignal,
        ?float $timeoutSeconds,
        string $message,
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new SeparateProcessExecutionException($kind, $exitCode, $termSignal, $timeoutSeconds);
    }
}
