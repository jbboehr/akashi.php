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

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Example;
use jbboehr\Akashi\Execution\CleanupFailure;
use jbboehr\Akashi\Execution\ExecutionFailed;
use jbboehr\Akashi\Execution\ExecutionResult;
use jbboehr\Akashi\Execution\ExecutionSucceeded;
use jbboehr\Akashi\Execution\FailurePhase;
use jbboehr\Akashi\Execution\StateResource;
use jbboehr\Akashi\Model\DocumentPath;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use jbboehr\Akashi\Transform\ExecutionScope;
use jbboehr\Akashi\Transform\InProcessPreparedExample;
use jbboehr\Akashi\Transform\PreparedCode;
use jbboehr\Akashi\Transform\PreparedExample;
use jbboehr\Akashi\Transform\SourceMap;
use PHPUnit\Framework\TestCase;

final class ExecutionResultTest extends TestCase
{
    public function testRepresentsSuccessfulExecutionWithoutNullableFailureFields(): void
    {
        $prepared = $this->preparedExample();
        $result = new ExecutionSucceeded($prepared, "documented output\n", 123_456, "documented warning\n");

        self::assertTrue((new \ReflectionClass($result))->implementsInterface(ExecutionResult::class));
        self::assertSame($prepared, $result->preparedExample);
        self::assertSame("documented output\n", $result->stdout);
        self::assertSame("documented warning\n", $result->stderr);
        self::assertSame(123_456, $result->durationNanoseconds);
    }

    public function testRepresentsExecutionFailureAndSecondaryCleanupFailures(): void
    {
        $prepared = $this->preparedExample();
        $cause = new \RuntimeException('Example failed.');
        $cleanupCause = new \RuntimeException('Buffer callback failed.');
        $cleanup = new CleanupFailure(
            StateResource::OutputBuffer,
            'Unable to restore output buffering.',
            $cleanupCause,
        );
        $result = new ExecutionFailed(
            $prepared,
            FailurePhase::Execution,
            $cause,
            'before failure',
            [$cleanup],
            789,
            3,
            'failure warning',
        );

        self::assertTrue((new \ReflectionClass($result))->implementsInterface(ExecutionResult::class));
        self::assertSame($prepared, $result->preparedExample);
        self::assertSame(FailurePhase::Execution, $result->phase);
        self::assertSame($cause, $result->cause);
        self::assertSame('before failure', $result->stdout);
        self::assertSame('failure warning', $result->stderr);
        self::assertSame([$cleanup], $result->cleanupFailures);
        self::assertSame($cleanupCause, $cleanup->cause);
        self::assertSame(789, $result->durationNanoseconds);
        self::assertSame(3, $result->generatedLine);
    }

    public function testRepresentsCleanupOnlyFailure(): void
    {
        $cleanup = new CleanupFailure(StateResource::WorkingDirectory, 'Working directory was not restored.');
        $result = new ExecutionFailed(
            $this->preparedExample(),
            FailurePhase::Cleanup,
            new \RuntimeException('Execution cleanup failed.'),
            '',
            [$cleanup],
            1,
        );

        self::assertSame(FailurePhase::Cleanup, $result->phase);
        self::assertSame([$cleanup], $result->cleanupFailures);
        self::assertNull($cleanup->cause);
        self::assertNull($result->generatedLine);
    }

    public function testFailurePhasesAndStateResourcesHaveStableValues(): void
    {
        $phaseValues = array_map(
            static fn (\ReflectionEnumBackedCase $case): int|string => $case->getBackingValue(),
            (new \ReflectionEnum(FailurePhase::class))->getCases(),
        );
        $resourceValues = array_map(
            static fn (\ReflectionEnumBackedCase $case): int|string => $case->getBackingValue(),
            (new \ReflectionEnum(StateResource::class))->getCases(),
        );

        self::assertSame(['execution', 'cleanup'], $phaseValues);
        self::assertSame(['output-buffer', 'working-directory', 'error-reporting', 'temporary-file'], $resourceValues);
    }

    public function testRejectsNegativeSuccessDuration(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Execution duration must not be negative.');

        new ExecutionSucceeded($this->preparedExample(), '', -1);
    }

    public function testAcceptsZeroSuccessDuration(): void
    {
        $result = new ExecutionSucceeded($this->preparedExample(), '', 0);

        self::assertSame(0, $result->durationNanoseconds);
        self::assertSame('', $result->stderr);
    }

    public function testRejectsNegativeFailureDuration(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Execution duration must not be negative.');

        new ExecutionFailed(
            $this->preparedExample(),
            FailurePhase::Execution,
            new \RuntimeException('Failure.'),
            '',
            [],
            -1,
        );
    }

    public function testAcceptsZeroFailureDuration(): void
    {
        $result = new ExecutionFailed(
            $this->preparedExample(),
            FailurePhase::Execution,
            new \RuntimeException('Failure.'),
            '',
            [],
            0,
        );

        self::assertSame(0, $result->durationNanoseconds);
        self::assertSame('', $result->stderr);
    }

    public function testRejectsANonPositiveGeneratedFailureLine(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Generated failure line must exist in the prepared source.');

        new ExecutionFailed(
            $this->preparedExample(),
            FailurePhase::Execution,
            new \RuntimeException('Failure.'),
            '',
            [],
            0,
            0,
        );
    }

    public function testRejectsAGeneratedFailureLineBeyondThePreparedSource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Generated failure line must exist in the prepared source.');

        new ExecutionFailed(
            $this->preparedExample(),
            FailurePhase::Execution,
            new \RuntimeException('Failure.'),
            '',
            [],
            0,
            4,
        );
    }

    public function testRejectsCleanupPhaseWithoutACleanupFailure(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A cleanup-phase failure must contain at least one cleanup failure.');

        new ExecutionFailed(
            $this->preparedExample(),
            FailurePhase::Cleanup,
            new \RuntimeException('Failure.'),
            '',
            [],
            0,
        );
    }

    public function testRejectsCleanupFailuresThatAreNotAList(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cleanup failures must form a list.');

        new ExecutionFailed(
            $this->preparedExample(),
            FailurePhase::Execution,
            new \RuntimeException('Failure.'),
            '',
            [1 => new CleanupFailure(StateResource::OutputBuffer, 'Failure.')],
            0,
        );
    }

    public function testRejectsInvalidCleanupFailureValues(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cleanup failures must contain only cleanup failure values.');

        new ExecutionFailed(
            $this->preparedExample(),
            FailurePhase::Execution,
            new \RuntimeException('Failure.'),
            '',
            ['not a cleanup failure'],
            0,
        );
    }

    public function testRejectsAnEmptyCleanupFailureMessage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A cleanup failure message must not be empty.');

        new CleanupFailure(StateResource::ErrorReporting, " \n ");
    }

    private function preparedExample(): PreparedExample
    {
        $source = 'echo 1;';
        $example = Example::fromInline(
            id: new ExampleId('example-execution-result-01'),
            label: 'Execution result fixture',
            document: new Document('docs/execution.md', $source),
            location: new SourceLocation(1, 2, 2, 3, new SourceSpan(0, 7), new SourceSpan(0, 7)),
            language: new Language('php'),
            code: new ExampleCode($source),
            fence: new FenceMetadata('php', '`', 3, 0),
            ordinal: 1,
        );
        $code = new PreparedCode("<?php\nnamespace Akashi\\Generated\\Result;\necho 1;");

        return new InProcessPreparedExample(
            $example,
            $code,
            new SourceMap(new DocumentPath('docs/execution.md'), [null, null, 2]),
            new ExecutionScope('Akashi\\Generated\\Result'),
        );
    }
}
