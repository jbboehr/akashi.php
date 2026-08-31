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
use jbboehr\Akashi\Integration\PHPStan\Exception\PhpStanCommandVerificationFailedException;
use jbboehr\Akashi\Integration\PHPStan\Exception\PhpStanJsonDecodeException;
use jbboehr\Akashi\Integration\PHPStan\PhpStanCommandNotCompleted;
use jbboehr\Akashi\Integration\PHPStan\PhpStanCommandOutputRejected;
use jbboehr\Akashi\Integration\PHPStan\PhpStanCommandResult;
use jbboehr\Akashi\Integration\PHPStan\PhpStanCommandTermination;
use jbboehr\Akashi\Integration\PHPStan\PhpStanCommandVerified;
use jbboehr\Akashi\Integration\PHPStan\PhpStanCommandVerifier;
use jbboehr\Akashi\Integration\PHPStan\PhpStanExternalFixturePlan;
use jbboehr\Akashi\Integration\PHPStan\PhpStanJsonResult;
use jbboehr\Akashi\Integration\PHPStan\PhpStanVerificationResult;
use jbboehr\Akashi\Model\ProjectRoot;
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

    public function testVerifiesAnExternalFixturePlanWithoutManualDisassembly(): void
    {
        $optionLikeFile = $this->nativePath('-option-like.php');
        $nestedDirectory = $this->nativePath('nested');
        $nestedFile = $this->nativePath('nested/example file.php');
        self::assertTrue(mkdir($nestedDirectory));
        self::assertNotFalse(file_put_contents($optionLikeFile, "<?php\nmissingFunction();\n"));
        self::assertNotFalse(file_put_contents($nestedFile, "<?php\n\nmissingVariable();\n"));
        $json = self::phpStanJson([
            $optionLikeFile => [
                'errors' => 1,
                'messages' => [[
                    'message' => 'Function missingFunction not found.',
                    'line' => 2,
                    'ignorable' => true,
                    'identifier' => 'function.notFound',
                ]],
            ],
            $nestedFile => [
                'errors' => 1,
                'messages' => [[
                    'message' => 'Function missingVariable not found.',
                    'line' => 3,
                    'ignorable' => true,
                    'identifier' => 'function.notFound',
                ]],
            ],
        ]);
        $runner = $this->nativePath('phpstan-fixture.php');
        self::assertNotFalse(file_put_contents($runner, sprintf(
            <<<'PHP'
                <?php

                if (
                    getcwd() !== %s
                    || array_slice($argv, 1) !== [
                        'analyse',
                        '--configuration=phpstan fixture.neon',
                        '--memory-limit=1G',
                        '--',
                        '-option-like.php',
                        'nested/example file.php',
                    ]
                ) {
                    fwrite(STDOUT, 'unexpected invocation');
                    exit(70);
                }

                fwrite(STDOUT, base64_decode(%s));
                exit(1);
                PHP,
            var_export($this->workspace, true),
            var_export(base64_encode($json), true),
        )));
        $plan = new PhpStanExternalFixturePlan(
            new ProjectRoot($this->workspace),
            ['nested/example file.php', '-option-like.php'],
            [
                $nestedFile => [new DiagnosticExpectation(
                    null,
                    3,
                    'function.notFound',
                    ['first' => 3, 'last' => 3],
                )],
                $optionLikeFile => [new DiagnosticExpectation(
                    null,
                    2,
                    'function.notFound',
                    ['first' => 2, 'last' => 2],
                )],
            ],
        );

        $result = (new PhpStanCommandVerifier())->verifyPlanOrThrow(
            $plan,
            PHP_BINARY,
            [
                $runner,
                'analyse',
                '--configuration=phpstan fixture.neon',
                '--memory-limit=1G',
            ],
        );

        self::assertSame(1, $result->commandResult->exitCode);
        self::assertTrue($result->verificationResult->isSuccessful());
        self::assertArrayHasKey($optionLikeFile, $result->verificationResult->matchesByFile);
        self::assertArrayHasKey($nestedFile, $result->verificationResult->matchesByFile);
    }

    public function testVerifyOrThrowPreservesDiagnosticMismatchEvidence(): void
    {
        $file = $this->nativePath('example.php');
        $json = self::phpStanJson([
            $file => [
                'errors' => 1,
                'messages' => [[
                    'message' => 'Actual diagnostic',
                    'line' => 7,
                    'ignorable' => true,
                    'identifier' => 'actual.identifier',
                ]],
            ],
        ]);

        try {
            (new PhpStanCommandVerifier())->verifyOrThrow(
                $this->workspace,
                PHP_BINARY,
                self::emit($json, 'analysis warning', 1),
                [$file => [new DiagnosticExpectation(
                    null,
                    7,
                    'expected.identifier',
                    ['first' => 7, 'last' => 7],
                )]],
            );
        } catch (PhpStanCommandVerificationFailedException $failure) {
            self::assertSame(
                'PHPStan command diagnostics did not match: 0 analyzer-wide errors and 1 mismatched file.',
                $failure->getMessage(),
            );
            self::assertInstanceOf(PhpStanCommandVerified::class, $failure->result);
            self::assertFalse($failure->result->verificationResult->isSuccessful());
            self::assertArrayHasKey($file, $failure->result->verificationResult->mismatchesByFile);
            self::assertSame('analysis warning', $failure->result->commandResult->stderr);
            self::assertNull($failure->getPrevious());

            return;
        }

        self::fail('A diagnostic mismatch must throw typed failure evidence.');
    }

    public function testVerifyOrThrowPreservesRejectedOutputAndItsCause(): void
    {
        try {
            (new PhpStanCommandVerifier())->verifyOrThrow(
                $this->workspace,
                PHP_BINARY,
                self::emit('not json', 'proxy warning', 2),
                [],
            );
        } catch (PhpStanCommandVerificationFailedException $failure) {
            self::assertStringStartsWith('PHPStan command output was rejected: ', $failure->getMessage());
            self::assertInstanceOf(PhpStanCommandOutputRejected::class, $failure->result);
            self::assertSame('not json', $failure->result->commandResult->stdout);
            self::assertSame('proxy warning', $failure->result->commandResult->stderr);
            self::assertSame($failure->result->cause, $failure->getPrevious());

            return;
        }

        self::fail('Rejected analyzer output must throw typed failure evidence.');
    }

    public function testVerifyOrThrowPreservesNonCompletionEvidence(): void
    {
        try {
            (new PhpStanCommandVerifier())->verifyOrThrow(
                $this->workspace,
                PHP_BINARY,
                ['-r', "fwrite(STDOUT, 'partial'); usleep(500000);"],
                [],
                0.05,
            );
        } catch (PhpStanCommandVerificationFailedException $failure) {
            self::assertSame(
                'PHPStan command did not complete: timed out after 0.05 seconds.',
                $failure->getMessage(),
            );
            self::assertInstanceOf(PhpStanCommandNotCompleted::class, $failure->result);
            self::assertSame(PhpStanCommandTermination::TimedOut, $failure->result->commandResult->termination);
            self::assertSame('partial', $failure->result->commandResult->stdout);
            self::assertSame(0.05, $failure->result->commandResult->timeoutSeconds);
            self::assertNull($failure->getPrevious());

            return;
        }

        self::fail('A command timeout must throw typed failure evidence.');
    }

    public function testVerifyOrThrowPreservesInfrastructureFailureEvidence(): void
    {
        $missingRoot = $this->nativePath('missing');

        try {
            (new PhpStanCommandVerifier())->verifyOrThrow(
                $missingRoot,
                PHP_BINARY,
                [],
                [],
            );
        } catch (PhpStanCommandVerificationFailedException $failure) {
            self::assertInstanceOf(PhpStanCommandNotCompleted::class, $failure->result);
            self::assertSame(
                PhpStanCommandTermination::InfrastructureFailed,
                $failure->result->commandResult->termination,
            );
            self::assertStringContainsString(
                str_replace('\\', '/', $missingRoot),
                $failure->result->commandResult->failureMessage ?? '',
            );
            self::assertSame(
                sprintf(
                    'PHPStan command did not complete: %s',
                    $failure->result->commandResult->failureMessage,
                ),
                $failure->getMessage(),
            );
            self::assertSame('', $failure->result->commandResult->stdout);
            self::assertSame('', $failure->result->commandResult->stderr);
            self::assertNull($failure->getPrevious());

            return;
        }

        self::fail('An infrastructure failure must throw typed non-completion evidence.');
    }

    public function testVerifyOrThrowPreservesSignalEvidence(): void
    {
        if (DIRECTORY_SEPARATOR === '\\' || !function_exists('posix_kill')) {
            self::markTestSkipped('Process-signal evidence requires a Unix-like platform with ext-posix.');
        }

        try {
            (new PhpStanCommandVerifier())->verifyOrThrow(
                $this->workspace,
                PHP_BINARY,
                [
                    '-r',
                    <<<'PHP'
                        fwrite(STDOUT, 'partial');
                        fflush(STDOUT);
                        fwrite(STDERR, 'signal warning');
                        fflush(STDERR);
                        posix_kill(getmypid(), 15);
                        usleep(500000);
                        PHP,
                ],
                [],
            );
        } catch (PhpStanCommandVerificationFailedException $failure) {
            self::assertSame('PHPStan command did not complete: terminated by signal 15.', $failure->getMessage());
            self::assertInstanceOf(PhpStanCommandNotCompleted::class, $failure->result);
            self::assertSame(PhpStanCommandTermination::Signaled, $failure->result->commandResult->termination);
            self::assertSame(15, $failure->result->commandResult->termSignal);
            self::assertSame('partial', $failure->result->commandResult->stdout);
            self::assertSame('signal warning', $failure->result->commandResult->stderr);
            self::assertNull($failure->getPrevious());

            return;
        }

        self::fail('A process signal must throw typed non-completion evidence.');
    }

    public function testVerifyOrThrowRejectsAnalyzerWideErrorsWithoutFileMismatches(): void
    {
        $json = self::phpStanJson([], ['Configuration could not be loaded.']);

        try {
            (new PhpStanCommandVerifier())->verifyOrThrow(
                projectRoot: $this->workspace,
                executable: PHP_BINARY,
                arguments: self::emit($json, 'configuration warning', 1),
                expectationsByFile: [],
            );
        } catch (PhpStanCommandVerificationFailedException $failure) {
            self::assertSame(
                'PHPStan command diagnostics did not match: 1 analyzer-wide error and 0 mismatched files.',
                $failure->getMessage(),
            );
            self::assertInstanceOf(PhpStanCommandVerified::class, $failure->result);
            self::assertSame(['Configuration could not be loaded.'], $failure->result->analyzerResult->globalErrors);
            self::assertSame(
                ['Configuration could not be loaded.'],
                $failure->result->verificationResult->globalErrors,
            );
            self::assertSame([], $failure->result->verificationResult->mismatchesByFile);
            self::assertSame($json, $failure->result->commandResult->stdout);
            self::assertSame('configuration warning', $failure->result->commandResult->stderr);
            self::assertSame(1, $failure->result->commandResult->exitCode);
            self::assertNull($failure->getPrevious());

            return;
        }

        self::fail('An analyzer-wide error must fail verification without a file mismatch.');
    }

    public function testVerifyOrThrowAcceptsNamedArgumentsAndACompletedNonzeroExit(): void
    {
        $json = self::phpStanJson([]);

        $result = (new PhpStanCommandVerifier())->verifyOrThrow(
            expectationsByFile: [],
            arguments: self::emit($json, 'non-fatal warning', 23),
            executable: PHP_BINARY,
            projectRoot: $this->workspace,
        );

        self::assertSame(PhpStanCommandTermination::Completed, $result->commandResult->termination);
        self::assertSame(23, $result->commandResult->exitCode);
        self::assertSame('non-fatal warning', $result->commandResult->stderr);
        self::assertTrue($result->verificationResult->isSuccessful());
    }

    public function testThrowingConveniencesMatchDataApiParameterContracts(): void
    {
        foreach ([
            ['verify', 'verifyOrThrow'],
            ['verifyPlan', 'verifyPlanOrThrow'],
        ] as [$dataMethod, $throwingMethod]) {
            $dataParameters = (new \ReflectionMethod(PhpStanCommandVerifier::class, $dataMethod))->getParameters();
            $throwingParameters = (new \ReflectionMethod(PhpStanCommandVerifier::class, $throwingMethod))->getParameters();

            self::assertSame(
                array_map(
                    static fn (\ReflectionParameter $parameter): array => [
                        'name' => $parameter->getName(),
                        'type' => (string) $parameter->getType(),
                        'hasDefault' => $parameter->isDefaultValueAvailable(),
                        'default' => $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
                    ],
                    $dataParameters,
                ),
                array_map(
                    static fn (\ReflectionParameter $parameter): array => [
                        'name' => $parameter->getName(),
                        'type' => (string) $parameter->getType(),
                        'hasDefault' => $parameter->isDefaultValueAvailable(),
                        'default' => $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
                    ],
                    $throwingParameters,
                ),
                sprintf('%s() must preserve %s() parameter names, types, and defaults.', $throwingMethod, $dataMethod),
            );
        }
    }

    public function testVerifyOrThrowRejectsMalformedExpectationsBeforeLaunchingTheCommand(): void
    {
        $sideEffect = $this->nativePath('must-not-exist');

        try {
            (new \ReflectionMethod(PhpStanCommandVerifier::class, 'verifyOrThrow'))->invoke(
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

        self::assertFileDoesNotExist($sideEffect, 'Malformed expectations launched the command.');
        self::fail('Malformed expectations must remain programmer input errors in the throwing API.');
    }

    public function testVerifyPlanRejectsSparseArgumentsBeforeLaunchingTheCommand(): void
    {
        $file = $this->nativePath('example.php');
        $sideEffect = $this->nativePath('must-not-exist');
        $runner = $this->nativePath('must-not-run.php');
        self::assertNotFalse(file_put_contents(
            $runner,
            sprintf("<?php\ntouch(%s);\n", var_export($sideEffect, true)),
        ));
        $plan = new PhpStanExternalFixturePlan(
            new ProjectRoot($this->workspace),
            ['example.php'],
            [$file => []],
        );

        try {
            (new \ReflectionMethod(PhpStanCommandVerifier::class, 'verifyPlan'))->invoke(
                new PhpStanCommandVerifier(),
                $plan,
                PHP_BINARY,
                [1 => $runner],
            );
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('PHPStan command arguments must form a list.', $exception->getMessage());
            self::assertFileDoesNotExist($sideEffect);

            return;
        }

        self::assertFileDoesNotExist($sideEffect, 'Malformed plan arguments launched the command.');
        self::fail('Malformed plan arguments must be rejected before command execution.');
    }

    public function testVerifyPlanRejectsItsOwnedDelimiterBeforeLaunchingTheCommand(): void
    {
        $file = $this->nativePath('example.php');
        $sideEffect = $this->nativePath('must-not-exist');
        $runner = $this->nativePath('must-not-run.php');
        self::assertNotFalse(file_put_contents(
            $runner,
            sprintf("<?php\ntouch(%s);\n", var_export($sideEffect, true)),
        ));
        $plan = new PhpStanExternalFixturePlan(
            new ProjectRoot($this->workspace),
            ['example.php'],
            [$file => []],
        );

        try {
            (new PhpStanCommandVerifier())->verifyPlan(
                $plan,
                PHP_BINARY,
                [$runner, '--'],
            );
        } catch (\InvalidArgumentException $exception) {
            self::assertSame(
                'PHPStan arguments before planned paths must not contain the owned -- delimiter.',
                $exception->getMessage(),
            );
            self::assertFileDoesNotExist($sideEffect);

            return;
        }

        self::assertFileDoesNotExist($sideEffect, 'A caller-supplied delimiter launched the command.');
        self::fail('The fixture-plan verifier must reject its owned delimiter in caller arguments.');
    }

    public function testVerifyPlanForwardsItsTimeoutAndRetainsTheDefault(): void
    {
        $method = new \ReflectionMethod(PhpStanCommandVerifier::class, 'verifyPlan');
        self::assertSame(60.0, $method->getParameters()[3]->getDefaultValue());
        $file = $this->nativePath('example.php');
        $plan = new PhpStanExternalFixturePlan(
            new ProjectRoot($this->workspace),
            ['example.php'],
            [$file => []],
        );

        $result = (new PhpStanCommandVerifier())->verifyPlan(
            $plan,
            PHP_BINARY,
            ['-r', "fwrite(STDOUT, 'partial'); usleep(500000);"],
            0.05,
        );

        self::assertInstanceOf(PhpStanCommandNotCompleted::class, $result);
        self::assertSame(PhpStanCommandTermination::TimedOut, $result->commandResult->termination);
        self::assertSame('partial', $result->commandResult->stdout);
        self::assertSame(0.05, $result->commandResult->timeoutSeconds);
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
        self::assertStringContainsString(
            str_replace('\\', '/', $missingRoot),
            $result->commandResult->failureMessage ?? '',
        );
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

    public function testCommandVerificationFailureRejectsSuccessfulEvidence(): void
    {
        $completed = new PhpStanCommandResult(
            PhpStanCommandTermination::Completed,
            '',
            '',
            0,
            exitCode: 0,
        );
        $verified = new PhpStanCommandVerified(
            $completed,
            new PhpStanJsonResult(0, 0, [], []),
            new PhpStanVerificationResult([], [], []),
        );

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Successful PHPStan command verification cannot be represented as a failure.');
        new PhpStanCommandVerificationFailedException($verified);
    }

    public function testCommandVerificationFailureRetainsTheExactFailedResult(): void
    {
        $completed = new PhpStanCommandResult(
            PhpStanCommandTermination::Completed,
            'analyzer output',
            'analyzer warning',
            42,
            exitCode: 1,
        );
        $verified = new PhpStanCommandVerified(
            $completed,
            new PhpStanJsonResult(1, 0, ['Analyzer-wide failure.'], []),
            new PhpStanVerificationResult(['Analyzer-wide failure.'], [], []),
        );

        $failure = new PhpStanCommandVerificationFailedException($verified);

        self::assertSame($verified, $failure->result);
        self::assertSame($completed, $failure->result->commandResult);
        self::assertSame(['Analyzer-wide failure.'], $failure->result->verificationResult->globalErrors);
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

    /** @return non-empty-string */
    private function nativePath(string $relativePath): string
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $this->workspace . '/' . $relativePath);
    }
}
