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

namespace jbboehr\Akashi\Tests\Integration\PHPStan;

use jbboehr\Akashi\Integration\PHPStan\DiagnosticExpectation;
use jbboehr\Akashi\Integration\PHPStan\Exception\PhpStanJsonDecodeException;
use jbboehr\Akashi\Integration\PHPStan\PhpStanCommandNotCompleted;
use jbboehr\Akashi\Integration\PHPStan\PhpStanCommandOutputRejected;
use jbboehr\Akashi\Integration\PHPStan\PhpStanCommandResult;
use jbboehr\Akashi\Integration\PHPStan\PhpStanCommandTermination;
use jbboehr\Akashi\Integration\PHPStan\PhpStanCommandVerified;
use jbboehr\Akashi\Integration\PHPStan\PhpStanCommandVerifier;
use jbboehr\Akashi\Integration\PHPStan\PhpStanJsonResult;
use jbboehr\Akashi\Integration\PHPStan\PhpStanVerificationResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhpStanCommandVerifierTest extends TestCase
{
    private string $workspace;

    protected function setUp(): void
    {
        $workspace = tempnam(sys_get_temp_dir(), 'akashi-phpstan-verifier-');
        self::assertNotFalse($workspace);
        self::assertTrue(unlink($workspace));
        self::assertTrue(mkdir($workspace, 0o700));

        $canonicalWorkspace = realpath($workspace);
        self::assertNotFalse($canonicalWorkspace);
        $this->workspace = $canonicalWorkspace;
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->workspace)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->workspace, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $path) {
            if (!$path instanceof \SplFileInfo) {
                continue;
            }
            if ($path->isLink() || $path->isFile()) {
                self::assertTrue(unlink($path->getPathname()));
            } else {
                self::assertTrue(rmdir($path->getPathname()));
            }
        }

        self::assertTrue(rmdir($this->workspace));
    }

    public function testRunsDecodesAndVerifiesExpectedDiagnostics(): void
    {
        $file = $this->workspace . '/example.php';
        $json = self::phpStanJson([
            $file => [
                'errors' => 1,
                'messages' => [[
                    'message' => 'Call to an undefined method Example::missing().',
                    'line' => 7,
                    'ignorable' => true,
                    'identifier' => 'method.notFound',
                ]],
            ],
        ]);

        $result = (new PhpStanCommandVerifier())->verify(
            $this->workspace,
            PHP_BINARY,
            self::emit($json, 'analysis warning', 1),
            [$file => [new DiagnosticExpectation(
                null,
                12,
                'method.notFound',
                ['first' => 7, 'last' => 7],
            )]],
        );

        self::assertInstanceOf(PhpStanCommandVerified::class, $result);
        self::assertSame(1, $result->commandResult->exitCode);
        self::assertSame('analysis warning', $result->commandResult->stderr);
        self::assertSame(1, $result->analyzerResult->fileErrorCount);
        self::assertTrue($result->verificationResult->isSuccessful());
        self::assertArrayHasKey($file, $result->verificationResult->matchesByFile);
        self::assertSame(
            'method.notFound',
            $result->verificationResult->matchesByFile[$file]->assignments[0]->expectation->identifier,
        );
    }

    public function testRejectsAJsonDiagnosticOutsideItsExpectedSourceRange(): void
    {
        $file = $this->workspace . '/example.php';
        $json = self::phpStanJson([
            $file => [
                'errors' => 1,
                'messages' => [[
                    'message' => 'Call to an undefined method Example::missing().',
                    'line' => 7,
                    'ignorable' => true,
                    'identifier' => 'method.notFound',
                ]],
            ],
        ]);

        $result = (new PhpStanCommandVerifier())->verify(
            $this->workspace,
            PHP_BINARY,
            self::emit($json, '', 1),
            [$file => [new DiagnosticExpectation(
                null,
                12,
                'method.notFound',
                ['first' => 8, 'last' => 8],
            )]],
        );

        self::assertInstanceOf(PhpStanCommandVerified::class, $result);
        self::assertFalse($result->verificationResult->isSuccessful());
        self::assertArrayHasKey($file, $result->verificationResult->mismatchesByFile);
    }

    public function testPreservesValidAnalyzerErrorsAndDiagnosticMismatches(): void
    {
        $file = $this->workspace . '/example.php';
        $json = self::phpStanJson([
            $file => [
                'errors' => 1,
                'messages' => [[
                    'message' => 'Actual diagnostic',
                    'line' => null,
                    'ignorable' => false,
                ]],
            ],
        ], ['Analyzer-wide failure']);

        $result = (new PhpStanCommandVerifier())->verify(
            $this->workspace,
            PHP_BINARY,
            self::emit($json, '', 1),
            [$file => [new DiagnosticExpectation('Expected diagnostic', 4)]],
        );

        self::assertInstanceOf(PhpStanCommandVerified::class, $result);
        self::assertFalse($result->verificationResult->isSuccessful());
        self::assertSame(['Analyzer-wide failure'], $result->verificationResult->globalErrors);
        self::assertArrayHasKey($file, $result->verificationResult->mismatchesByFile);
    }

    public function testReturnsRejectedOutputWithCompleteCommandEvidence(): void
    {
        $result = (new PhpStanCommandVerifier())->verify(
            $this->workspace,
            PHP_BINARY,
            self::emit('not json', 'proxy warning', 2),
            [],
        );

        self::assertInstanceOf(PhpStanCommandOutputRejected::class, $result);
        self::assertSame(2, $result->commandResult->exitCode);
        self::assertSame('not json', $result->commandResult->stdout);
        self::assertSame('proxy warning', $result->commandResult->stderr);
        self::assertStringContainsString('Unable to decode PHPStan JSON output', $result->cause->getMessage());
    }

    public function testReturnsNonCompletedTimeoutEvidenceWithoutDecoding(): void
    {
        $result = (new PhpStanCommandVerifier())->verify(
            $this->workspace,
            PHP_BINARY,
            ['-r', "fwrite(STDOUT, 'partial'); usleep(500000);"],
            [],
            0.05,
        );

        self::assertInstanceOf(PhpStanCommandNotCompleted::class, $result);
        self::assertSame(PhpStanCommandTermination::TimedOut, $result->commandResult->termination);
        self::assertSame('partial', $result->commandResult->stdout);
        self::assertSame(0.05, $result->commandResult->timeoutSeconds);
    }

    public function testReturnsNonCompletedInfrastructureEvidence(): void
    {
        $missingRoot = $this->workspace . '/missing';

        $result = (new PhpStanCommandVerifier())->verify(
            $missingRoot,
            PHP_BINARY,
            [],
            [],
        );

        self::assertInstanceOf(PhpStanCommandNotCompleted::class, $result);
        self::assertSame(PhpStanCommandTermination::InfrastructureFailed, $result->commandResult->termination);
        self::assertStringContainsString($missingRoot, $result->commandResult->failureMessage ?? '');
    }

    public function testReturnsNonCompletedSignalEvidence(): void
    {
        if (DIRECTORY_SEPARATOR === '\\' || !function_exists('posix_kill')) {
            self::markTestSkipped('Process-signal evidence requires a Unix-like platform with ext-posix.');
        }

        $result = (new PhpStanCommandVerifier())->verify(
            $this->workspace,
            PHP_BINARY,
            ['-r', 'posix_kill(getmypid(), 15); usleep(500000);'],
            [],
        );

        self::assertInstanceOf(PhpStanCommandNotCompleted::class, $result);
        self::assertSame(PhpStanCommandTermination::Signaled, $result->commandResult->termination);
        self::assertSame(15, $result->commandResult->termSignal);
    }

    public function testRejectsMalformedExpectationsBeforeLaunchingTheCommand(): void
    {
        $sideEffect = $this->workspace . '/must-not-exist';

        try {
            (new \ReflectionMethod(PhpStanCommandVerifier::class, 'verify'))->invoke(
                new PhpStanCommandVerifier(),
                $this->workspace,
                PHP_BINARY,
                ['-r', sprintf('touch(%s);', var_export($sideEffect, true))],
                ['/example.php' => ['not an expectation']],
            );
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('must contain only expectation values', $exception->getMessage());
            self::assertFileDoesNotExist($sideEffect);

            return;
        }

        self::fail('Malformed expectations must be rejected before command execution.');
    }

    public function testRejectsMalformedExpectationsBeforeMalformedCommandInput(): void
    {
        try {
            (new \ReflectionMethod(PhpStanCommandVerifier::class, 'verify'))->invoke(
                new PhpStanCommandVerifier(),
                ' ',
                PHP_BINARY,
                [],
                ['/example.php' => ['not an expectation']],
            );
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('must contain only expectation values', $exception->getMessage());

            return;
        }

        self::fail('Malformed expectations must be rejected before malformed command input.');
    }

    /** @param array<array-key, mixed> $arguments */
    #[DataProvider('invalidCommandInputProvider')]
    public function testRejectsMalformedCommandInputBeforeLaunchingTheCommand(
        ?string $projectRoot,
        array $arguments,
        float $timeoutSeconds,
        string $message,
    ): void {
        $sideEffect = $this->workspace . '/must-not-exist';
        $arguments = array_map(
            static fn (mixed $argument): mixed => is_string($argument)
                ? str_replace('{SIDE_EFFECT}', var_export($sideEffect, true), $argument)
                : $argument,
            $arguments,
        );

        try {
            (new \ReflectionMethod(PhpStanCommandVerifier::class, 'verify'))->invoke(
                new PhpStanCommandVerifier(),
                $projectRoot ?? $this->workspace,
                PHP_BINARY,
                $arguments,
                [],
                $timeoutSeconds,
            );
        } catch (\InvalidArgumentException $exception) {
            self::assertSame($message, $exception->getMessage());
            self::assertFileDoesNotExist($sideEffect);

            return;
        }

        self::fail('Malformed PHPStan command input must be rejected before command execution.');
    }

    /** @return iterable<string, array{string|null, array<array-key, mixed>, float, non-empty-string}> */
    public static function invalidCommandInputProvider(): iterable
    {
        $sideEffectArguments = ['-r', 'touch({SIDE_EFFECT});'];

        yield 'blank project root' => [
            ' ',
            $sideEffectArguments,
            1.0,
            'Project root must not be empty.',
        ];
        yield 'non-list arguments' => [
            null,
            ['option' => '-r', 'code' => 'touch({SIDE_EFFECT});'],
            1.0,
            'PHPStan command arguments must form a list.',
        ];
        yield 'non-string argument' => [
            null,
            [...$sideEffectArguments, 1],
            1.0,
            'Every PHPStan command argument must be a string.',
        ];
        yield 'NUL argument' => [
            null,
            [...$sideEffectArguments, "bad\0argument"],
            1.0,
            'PHPStan command arguments must not contain NUL bytes.',
        ];
        yield 'zero timeout' => [
            null,
            $sideEffectArguments,
            0.0,
            'PHPStan command timeout must be finite and positive.',
        ];
        yield 'negative timeout' => [
            null,
            $sideEffectArguments,
            -1.0,
            'PHPStan command timeout must be finite and positive.',
        ];
        yield 'infinite timeout' => [
            null,
            $sideEffectArguments,
            INF,
            'PHPStan command timeout must be finite and positive.',
        ];
        yield 'not-a-number timeout' => [
            null,
            $sideEffectArguments,
            NAN,
            'PHPStan command timeout must be finite and positive.',
        ];
    }

    public function testVerifiedOutcomeRejectsNonCompletedEvidence(): void
    {
        $timedOut = new PhpStanCommandResult(
            PhpStanCommandTermination::TimedOut,
            '',
            '',
            0,
            timeoutSeconds: 1.0,
        );
        $analysis = new PhpStanJsonResult(0, 0, [], []);
        $verification = new PhpStanVerificationResult([], [], []);
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Verified PHPStan command evidence must have completed.');
        new PhpStanCommandVerified($timedOut, $analysis, $verification);
    }

    public function testNotCompletedOutcomeRejectsCompletedEvidence(): void
    {
        $completed = new PhpStanCommandResult(
            PhpStanCommandTermination::Completed,
            '',
            '',
            0,
            exitCode: 0,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Non-completed PHPStan command evidence must not have completed.');
        new PhpStanCommandNotCompleted($completed);
    }

    public function testRejectedOutputOutcomeRejectsNonCompletedEvidence(): void
    {
        $timedOut = new PhpStanCommandResult(
            PhpStanCommandTermination::TimedOut,
            '',
            '',
            0,
            timeoutSeconds: 1.0,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rejected PHPStan output requires completed command evidence.');
        new PhpStanCommandOutputRejected($timedOut, new PhpStanJsonDecodeException('Rejected output.'));
    }

    /**
     * @param array<non-empty-string, array{errors: non-negative-int, messages: list<array<string, mixed>>}> $files
     * @param list<non-empty-string> $globalErrors
     */
    private static function phpStanJson(array $files, array $globalErrors = []): string
    {
        $fileErrorCount = 0;
        foreach ($files as $file) {
            $fileErrorCount += $file['errors'];
        }

        return json_encode([
            'totals' => [
                'errors' => count($globalErrors),
                'file_errors' => $fileErrorCount,
            ],
            'errors' => $globalErrors,
            'files' => $files === [] ? new \stdClass() : $files,
        ], JSON_THROW_ON_ERROR);
    }

    /** @return list<string> */
    private static function emit(string $stdout, string $stderr, int $exitCode): array
    {
        return [
            '-r',
            sprintf(
                'fwrite(STDOUT, base64_decode(%s)); fwrite(STDERR, base64_decode(%s)); exit(%d);',
                var_export(base64_encode($stdout), true),
                var_export(base64_encode($stderr), true),
                $exitCode,
            ),
        ];
    }
}
