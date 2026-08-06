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

use jbboehr\Akashi\Execution\CleanupFailure;
use jbboehr\Akashi\Execution\InProcess\InProcessStateGuard;
use jbboehr\Akashi\Execution\InProcess\StateRestoration;
use jbboehr\Akashi\Execution\StateResource;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class InProcessStateGuardTest extends TestCase
{
    public function testCapturesNestedOutputAndRestoresWorkingDirectoryAndErrorReporting(): void
    {
        $initialOutputLevel = ob_get_level();
        $initialWorkingDirectory = getcwd();
        $initialErrorReporting = error_reporting();
        self::assertIsString($initialWorkingDirectory);

        $guard = new InProcessStateGuard();
        echo 'outer:';
        ob_start(static fn (string $buffer): string => strtoupper($buffer));
        echo 'nested';
        ob_start();
        echo ':deep';
        self::assertTrue(chdir(sys_get_temp_dir()));
        error_reporting(E_ERROR);

        $restoration = $guard->restore();

        self::assertSame('outer:NESTED:DEEP', $restoration->stdout);
        self::assertSame([], $restoration->cleanupFailures);
        self::assertSame($initialOutputLevel, ob_get_level());
        self::assertSame($initialWorkingDirectory, getcwd());
        self::assertSame($initialErrorReporting, error_reporting());
    }

    public function testRestoresStateAfterTheCallerCatchesAnExecutionThrowable(): void
    {
        $guard = new InProcessStateGuard();
        $cause = null;
        $restoration = null;

        try {
            echo 'before failure';
            throw new \RuntimeException('Example failed.');
        } catch (\Throwable $throwable) {
            $cause = $throwable;
        } finally {
            $restoration = $guard->restore();
        }

        self::assertInstanceOf(\RuntimeException::class, $cause);
        self::assertSame('before failure', $restoration->stdout);
        self::assertSame([], $restoration->cleanupFailures);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testReportsRemovalOfTheOwnedOutputBuffer(): void
    {
        $initialOutputLevel = ob_get_level();
        $guard = new InProcessStateGuard();
        self::assertTrue(ob_end_clean());

        $restoration = $guard->restore();

        self::assertSame('', $restoration->stdout);
        self::assertSame($initialOutputLevel, ob_get_level());
        self::assertCount(1, $restoration->cleanupFailures);
        self::assertSame(StateResource::OutputBuffer, $restoration->cleanupFailures[0]->resource);
        self::assertStringContainsString('owned by Akashi was removed', $restoration->cleanupFailures[0]->message);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testReportsReplacementOfTheOwnedOutputBufferAtTheSameDepth(): void
    {
        $initialOutputLevel = ob_get_level();
        $guard = new InProcessStateGuard();
        self::assertTrue(ob_end_clean());
        self::assertTrue(ob_start());
        echo 'replacement output';

        $restoration = $guard->restore();

        self::assertSame('', $restoration->stdout);
        self::assertSame($initialOutputLevel, ob_get_level());
        self::assertCount(1, $restoration->cleanupFailures);
        self::assertStringContainsString('removed and replaced', $restoration->cleanupFailures[0]->message);
    }

    public function testReportsAnUnremovableNestedOutputBuffer(): void
    {
        $restoration = $this->runUnremovableBufferScenario('nested');

        self::assertSame('', $restoration['stdout']);
        self::assertSame(['An output buffer created during execution is not removable.'], $restoration['failures']);
    }

    public function testReportsAnUnremovableReplacementOutputBuffer(): void
    {
        $restoration = $this->runUnremovableBufferScenario('replacement');

        self::assertSame('', $restoration['stdout']);
        self::assertSame(
            [
                'The output buffer owned by Akashi was removed and replaced during execution.',
                'The replacement output buffer is not removable.',
            ],
            $restoration['failures'],
        );
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testReportsRemovalOfAPreExistingOutputBuffer(): void
    {
        $outerOutputLevel = ob_get_level();
        self::assertTrue(ob_start());
        $guard = new InProcessStateGuard();
        self::assertTrue(ob_end_clean());
        self::assertTrue(ob_end_clean());

        $restoration = $guard->restore();

        self::assertSame($outerOutputLevel, ob_get_level());
        self::assertCount(2, $restoration->cleanupFailures);
        self::assertStringContainsString('owned by Akashi was removed', $restoration->cleanupFailures[0]->message);
        self::assertStringContainsString('pre-existing output buffer was removed', $restoration->cleanupFailures[1]->message);
    }

    public function testReportsAWorkingDirectoryThatCannotBeRestored(): void
    {
        $originalWorkingDirectory = getcwd();
        self::assertIsString($originalWorkingDirectory);
        $temporaryDirectory = sys_get_temp_dir() . '/akashi-state-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($temporaryDirectory, 0700));
        self::assertTrue(chdir($temporaryDirectory));
        $guard = new InProcessStateGuard();
        self::assertTrue(chdir($originalWorkingDirectory));
        self::assertTrue(rmdir($temporaryDirectory));

        $restoration = $guard->restore();

        self::assertSame($originalWorkingDirectory, getcwd());
        self::assertCount(1, $restoration->cleanupFailures);
        self::assertSame(StateResource::WorkingDirectory, $restoration->cleanupFailures[0]->resource);
        self::assertStringContainsString($temporaryDirectory, $restoration->cleanupFailures[0]->message);
    }

    public function testRecordsAThrowableRaisedWhileFoldingNestedOutput(): void
    {
        $initialOutputLevel = ob_get_level();
        $cause = new \RuntimeException('Output callback failed.');
        $guard = new InProcessStateGuard(static function () use ($cause): bool {
            throw $cause;
        });
        echo 'owned';
        self::assertTrue(ob_start());
        echo 'nested';

        $restoration = $guard->restore();
        $nestedOutput = ob_get_clean();
        $ownedOutput = ob_get_clean();

        self::assertSame('nested', $nestedOutput);
        self::assertSame('owned', $ownedOutput);
        self::assertSame($initialOutputLevel, ob_get_level());
        self::assertCount(1, $restoration->cleanupFailures);
        self::assertSame(StateResource::OutputBuffer, $restoration->cleanupFailures[0]->resource);
        self::assertSame($cause, $restoration->cleanupFailures[0]->cause);
        self::assertStringContainsString('handler failed', $restoration->cleanupFailures[0]->message);
    }

    public function testRestorationCannotRunTwice(): void
    {
        $guard = new InProcessStateGuard();
        $first = $guard->restore();
        self::assertSame([], $first->cleanupFailures);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('In-process state has already been restored.');

        $guard->restore();
    }

    public function testStateRestorationRejectsFailuresThatAreNotAList(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cleanup failures must form a list.');

        new StateRestoration('', [1 => new CleanupFailure(StateResource::OutputBuffer, 'Failure.')]);
    }

    public function testStateRestorationRejectsInvalidFailureValues(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cleanup failures must contain only cleanup failure values.');

        new StateRestoration('', ['not a cleanup failure']);
    }

    /**
     * @return array{stdout: string, failures: list<string>}
     */
    private function runUnremovableBufferScenario(string $scenario): array
    {
        $sourceFile = (new \ReflectionClass(InProcessStateGuard::class))->getFileName();
        self::assertIsString($sourceFile);

        $script = <<<'PHP'
require $argv[1];
require $argv[2];

$guard = new \jbboehr\Akashi\Execution\InProcess\InProcessStateGuard();
if ($argv[3] === 'replacement') {
    ob_end_clean();
}
ob_start(null, 0, PHP_OUTPUT_HANDLER_STDFLAGS ^ PHP_OUTPUT_HANDLER_REMOVABLE);
$restoration = $guard->restore();
$payload = json_encode(
    [
        'stdout' => $restoration->stdout,
        'failures' => array_map(
            static fn (\jbboehr\Akashi\Execution\CleanupFailure $failure): string => $failure->message,
            $restoration->cleanupFailures,
        ),
    ],
    JSON_THROW_ON_ERROR,
);
fwrite(STDERR, "AKASHI_STATE:" . base64_encode($payload) . "\n");
PHP;
        $process = new Process([
            PHP_BINARY,
            '-r',
            $script,
            dirname(__DIR__, 2) . '/vendor/autoload.php',
            $sourceFile,
            $scenario,
        ]);
        $process->mustRun();

        $matched = preg_match('/^AKASHI_STATE:(?<payload>[A-Za-z0-9+\/=]+)$/m', $process->getErrorOutput(), $matches);
        self::assertSame(1, $matched, $process->getErrorOutput());
        $payload = base64_decode($matches['payload'], true);
        self::assertIsString($payload);
        $decoded = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

        if (
            !is_array($decoded)
            || !isset($decoded['stdout'])
            || !is_string($decoded['stdout'])
            || !isset($decoded['failures'])
            || !is_array($decoded['failures'])
            || !array_is_list($decoded['failures'])
        ) {
            throw new \LogicException('The output-buffer fixture returned an invalid payload.');
        }

        foreach ($decoded['failures'] as $failure) {
            if (!is_string($failure)) {
                throw new \LogicException('The output-buffer fixture returned an invalid failure.');
            }
        }

        /** @var list<string> $failures */
        $failures = $decoded['failures'];

        return ['stdout' => $decoded['stdout'], 'failures' => $failures];
    }
}
