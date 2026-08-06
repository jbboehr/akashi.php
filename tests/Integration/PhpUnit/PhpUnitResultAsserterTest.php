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

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Example;
use jbboehr\Akashi\Execution\CleanupFailure;
use jbboehr\Akashi\Execution\ExecutionFailed;
use jbboehr\Akashi\Execution\ExecutionResult;
use jbboehr\Akashi\Execution\ExecutionSucceeded;
use jbboehr\Akashi\Execution\FailurePhase;
use jbboehr\Akashi\Execution\InProcess\InProcessExecutor;
use jbboehr\Akashi\Execution\StateResource;
use jbboehr\Akashi\Integration\PhpUnit\PhpUnitResultAsserter;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use jbboehr\Akashi\Transform\ExecutionScope;
use jbboehr\Akashi\Transform\InProcessTransformer;
use jbboehr\Akashi\Transform\PreparedExample;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

final class PhpUnitResultAsserterTest extends TestCase
{
    private int $scopeSequence = 0;

    public function testRecordsOneCompletionAssertionForSuccessfulExecution(): void
    {
        $result = new ExecutionSucceeded($this->transform("echo 'allowed output';"), 'allowed output', 0);
        $before = Assert::getCount();

        (new PhpUnitResultAsserter())->assertResult($result);

        $after = Assert::getCount();
        self::assertSame($before + 1, $after);
    }

    public function testReportsAnAuthoredFailureAtItsMaintainedMarkdownLine(): void
    {
        $result = $this->executeFailure("echo 'before failure';\nthrow new RuntimeException('example failed');");

        $failure = $this->assertionFailure($result);

        self::assertStringContainsString(
            'Documentation example example-phpunit-result-01 failed during execution.',
            $failure->getMessage(),
        );
        self::assertStringContainsString('Label: PHPUnit result fixture', $failure->getMessage());
        self::assertStringContainsString('Location: docs/phpunit-result.md:11', $failure->getMessage());
        self::assertStringContainsString("Cause:\n    RuntimeException: example failed", $failure->getMessage());
        self::assertStringContainsString("Captured stdout:\n    before failure", $failure->getMessage());
        self::assertSame($result->cause, $failure->getPrevious());
    }

    public function testMapsARewrittenAssertionWithACustomDescription(): void
    {
        $result = $this->executeFailure("echo 'before';\nassert(false, 'documented failure');");

        $failure = $this->assertionFailure($result);

        self::assertStringContainsString('Location: docs/phpunit-result.md:11', $failure->getMessage());
        self::assertStringContainsString('documented failure', $failure->getMessage());
        self::assertSame($result->cause, $failure->getPrevious());
    }

    public function testRetainsANonExceptionThrowableBeneathATransparentException(): void
    {
        $result = $this->executeFailure("declare(strict_types=1);\nstrlen(123);");

        $failure = $this->assertionFailure($result);
        $previous = $failure->getPrevious();

        self::assertInstanceOf(\TypeError::class, $result->cause);
        self::assertInstanceOf(\RuntimeException::class, $previous);
        self::assertSame('Execution failed with TypeError.', $previous->getMessage());
        self::assertSame(0, $previous->getCode());
        self::assertSame($result->cause, $previous->getPrevious());
        self::assertStringContainsString('Location: docs/phpunit-result.md:11', $failure->getMessage());
        self::assertStringContainsString('TypeError:', $failure->getMessage());
    }

    public function testReportsOutputAndSecondaryCleanupFailuresWithoutInventingAnExactLine(): void
    {
        $cause = new \RuntimeException('primary failure');
        $cleanupCause = new \UnexpectedValueException('handler failure');
        $result = new ExecutionFailed(
            $this->transform('echo 1;'),
            FailurePhase::Execution,
            $cause,
            "first line\nsecond line",
            [new CleanupFailure(
                StateResource::OutputBuffer,
                'The output buffer could not be restored.',
                $cleanupCause,
            )],
            456,
            2,
            "first warning\nsecond warning",
        );
        $expected = <<<'TEXT'
Documentation example example-phpunit-result-01 failed during execution.
Label: PHPUnit result fixture
Location: docs/phpunit-result.md:10 (example start; exact failing line unavailable)
Cause:
    RuntimeException: primary failure
Captured stdout:
    first line
    second line
Captured stderr:
    first warning
    second warning
Cleanup failures:
    - output-buffer: The output buffer could not be restored.
      Caused by: UnexpectedValueException: handler failure
TEXT;

        $failure = $this->assertionFailure($result);

        self::assertSame($expected, $failure->getMessage());
        self::assertSame($cause, $failure->getPrevious());
    }

    public function testReportsCleanupOnlyFailureAndAnEmptyCauseMessage(): void
    {
        $result = new ExecutionFailed(
            $this->transform('echo 1;'),
            FailurePhase::Cleanup,
            new \RuntimeException(),
            '',
            [new CleanupFailure(StateResource::WorkingDirectory, 'The working directory was not restored.')],
            789,
        );

        $failure = $this->assertionFailure($result);

        self::assertStringContainsString('failed during cleanup.', $failure->getMessage());
        self::assertStringContainsString("Cause:\n    RuntimeException: (no message)", $failure->getMessage());
        self::assertStringNotContainsString('Captured stdout:', $failure->getMessage());
        self::assertStringNotContainsString('Captured stderr:', $failure->getMessage());
        self::assertStringContainsString(
            "Cleanup failures:\n    - working-directory: The working directory was not restored.",
            $failure->getMessage(),
        );
    }

    public function testRejectsAnUnknownExecutionResultVariant(): void
    {
        $result = new class () implements ExecutionResult {
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unsupported execution result variant');

        (new PhpUnitResultAsserter())->assertResult($result);
    }

    private function executeFailure(string $source): ExecutionFailed
    {
        $result = (new InProcessExecutor())->execute($this->transform($source));
        self::assertInstanceOf(ExecutionFailed::class, $result);

        return $result;
    }

    private function assertionFailure(ExecutionFailed $result): ExpectationFailedException
    {
        try {
            (new PhpUnitResultAsserter())->assertResult($result);
        } catch (ExpectationFailedException $failure) {
            return $failure;
        }

        self::fail('A failed execution result must produce a PHPUnit assertion failure.');
    }

    private function transform(string $source): PreparedExample
    {
        ++$this->scopeSequence;

        return (new InProcessTransformer())->transform(
            $this->example($source),
            new ExecutionScope(sprintf('Akashi\\Generated\\PhpUnitResultFixture_%d', $this->scopeSequence)),
        );
    }

    private function example(string $source): Example
    {
        $sourceLength = strlen($source);
        $lineBreaks = preg_match_all('/\r\n|\r|\n/', $source);
        self::assertNotFalse($lineBreaks);
        $lineCount = $lineBreaks + 1;
        if ($sourceLength > 0 && preg_match('/(?:\r\n|\r|\n)\z/', $source) === 1) {
            --$lineCount;
        }

        $firstCodeLine = 10;
        $lastCodeLine = $sourceLength === 0 ? null : $firstCodeLine + $lineCount - 1;
        $closingFenceLine = $lastCodeLine === null ? $firstCodeLine : $lastCodeLine + 1;

        return new Example(
            id: new ExampleId('example-phpunit-result-01'),
            label: 'PHPUnit result fixture',
            document: new Document('docs/phpunit-result.md', $source),
            location: new SourceLocation(
                $firstCodeLine - 1,
                $firstCodeLine,
                $lastCodeLine,
                $closingFenceLine,
                new SourceSpan(0, max(1, $sourceLength)),
                new SourceSpan(0, $sourceLength),
            ),
            language: new Language('php'),
            code: new ExampleCode($source),
            fence: new FenceMetadata('php', '`', 3, 0),
            ordinal: 1,
        );
    }
}
