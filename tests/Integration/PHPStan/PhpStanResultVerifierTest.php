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

use jbboehr\Akashi\Integration\PHPStan\AnalyzerDiagnostic;
use jbboehr\Akashi\Integration\PHPStan\DiagnosticExpectation;
use jbboehr\Akashi\Integration\PHPStan\DiagnosticMismatchKind;
use jbboehr\Akashi\Integration\PHPStan\DiagnosticsMatched;
use jbboehr\Akashi\Integration\PHPStan\DiagnosticsMismatched;
use jbboehr\Akashi\Integration\PHPStan\PhpStanJsonResult;
use jbboehr\Akashi\Integration\PHPStan\PhpStanResultVerifier;
use jbboehr\Akashi\Integration\PHPStan\PhpStanVerificationResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhpStanResultVerifierTest extends TestCase
{
    public function testAcceptsCleanOutputWithoutExpectations(): void
    {
        $result = (new PhpStanResultVerifier())->verify(self::analyzerResult(), []);

        self::assertTrue($result->isSuccessful());
        self::assertSame([], $result->globalErrors);
        self::assertSame([], $result->matchesByFile);
        self::assertSame([], $result->mismatchesByFile);
    }

    public function testMatchesFilesInDeterministicPathOrder(): void
    {
        $aDiagnostic = new AnalyzerDiagnostic('a.problem', 'A problem.', analyzerLine: 3, ignorable: true);
        $zDiagnostic = new AnalyzerDiagnostic(null, 'Z problem.', tip: 'Read the guide.', analyzerLine: 8);
        $result = (new PhpStanResultVerifier())->verify(
            self::analyzerResult([
                '/project/z.php' => [$zDiagnostic],
                '/project/a.php' => [$aDiagnostic],
            ]),
            [
                '/project/z.php' => [new DiagnosticExpectation('Read the guide.', 20)],
                '/project/a.php' => [new DiagnosticExpectation('A problem.', 10)],
            ],
        );

        self::assertTrue($result->isSuccessful());
        self::assertSame(['/project/a.php', '/project/z.php'], array_keys($result->matchesByFile));
        self::assertSame($aDiagnostic, $result->matchesByFile['/project/a.php']->assignments[0]->diagnostic);
        self::assertSame($zDiagnostic, $result->matchesByFile['/project/z.php']->assignments[0]->diagnostic);
    }

    public function testGlobalErrorsFailWithoutDiscardingFileMatches(): void
    {
        $diagnostic = new AnalyzerDiagnostic(null, 'Expected problem.');
        $result = (new PhpStanResultVerifier())->verify(
            self::analyzerResult(['/project/a.php' => [$diagnostic]], ['Extension failed.']),
            ['/project/a.php' => [new DiagnosticExpectation('Expected problem.', 5)]],
        );

        self::assertFalse($result->isSuccessful());
        self::assertSame(['Extension failed.'], $result->globalErrors);
        self::assertArrayHasKey('/project/a.php', $result->matchesByFile);
        self::assertSame([], $result->mismatchesByFile);
    }

    public function testUnexpectedDiagnosticFileBecomesACountMismatch(): void
    {
        $diagnostic = new AnalyzerDiagnostic(null, 'Unexpected problem.');
        $result = (new PhpStanResultVerifier())->verify(
            self::analyzerResult(['/project/unexpected.php' => [$diagnostic]]),
            [],
        );

        self::assertFalse($result->isSuccessful());
        $mismatch = $result->mismatchesByFile['/project/unexpected.php'];
        self::assertSame(DiagnosticMismatchKind::Count, $mismatch->kind);
        self::assertSame([], $mismatch->expectations);
        self::assertSame([$diagnostic], $mismatch->diagnostics);
    }

    public function testMissingDiagnosticFileBecomesACountMismatch(): void
    {
        $expectation = new DiagnosticExpectation('Missing problem.', 9);
        $result = (new PhpStanResultVerifier())->verify(
            self::analyzerResult(),
            ['/project/missing.php' => [$expectation]],
        );

        self::assertFalse($result->isSuccessful());
        $mismatch = $result->mismatchesByFile['/project/missing.php'];
        self::assertSame(DiagnosticMismatchKind::Count, $mismatch->kind);
        self::assertSame([$expectation], $mismatch->expectations);
        self::assertSame([], $mismatch->diagnostics);
    }

    public function testAssignmentMismatchDoesNotPreventOtherFileMatches(): void
    {
        $result = (new PhpStanResultVerifier())->verify(
            self::analyzerResult([
                '/project/matched.php' => [new AnalyzerDiagnostic(null, 'Matched problem.')],
                '/project/mismatched.php' => [new AnalyzerDiagnostic(null, 'Actual problem.')],
            ]),
            [
                '/project/matched.php' => [new DiagnosticExpectation('Matched problem.', 2)],
                '/project/mismatched.php' => [new DiagnosticExpectation('Expected problem.', 4)],
            ],
        );

        self::assertFalse($result->isSuccessful());
        self::assertArrayHasKey('/project/matched.php', $result->matchesByFile);
        self::assertSame(
            DiagnosticMismatchKind::Assignment,
            $result->mismatchesByFile['/project/mismatched.php']->kind,
        );
    }

    public function testExplicitEmptyExpectationsMatchAnOmittedAnalyzerFile(): void
    {
        $result = (new PhpStanResultVerifier())->verify(
            self::analyzerResult(),
            ['/project/clean.php' => []],
        );

        self::assertTrue($result->isSuccessful());
        self::assertArrayHasKey('/project/clean.php', $result->matchesByFile);
        self::assertSame([], $result->matchesByFile['/project/clean.php']->assignments);
    }

    public function testVerificationResultSortsEachFilePartition(): void
    {
        $matched = new DiagnosticsMatched([]);
        $mismatched = new DiagnosticsMismatched(
            DiagnosticMismatchKind::Count,
            [],
            [new AnalyzerDiagnostic(null, 'x')],
        );

        $result = new PhpStanVerificationResult(
            [],
            ['z.php' => $matched, 'a.php' => $matched],
            ['y.php' => $mismatched, 'b.php' => $mismatched],
        );

        self::assertSame(['a.php', 'z.php'], array_keys($result->matchesByFile));
        self::assertSame(['b.php', 'y.php'], array_keys($result->mismatchesByFile));
    }

    /** @param array<array-key, mixed> $expectationsByFile */
    #[DataProvider('invalidExpectationMapProvider')]
    public function testRejectsInvalidExpectationMaps(
        array $expectationsByFile,
        string $message,
        ?string $previousMessage,
    ): void {
        try {
            (new \ReflectionMethod(PhpStanResultVerifier::class, 'verify'))->invoke(
                new PhpStanResultVerifier(),
                self::analyzerResult(),
                $expectationsByFile,
            );
        } catch (\InvalidArgumentException $exception) {
            self::assertSame($message, $exception->getMessage());
            self::assertSame($previousMessage, $exception->getPrevious()?->getMessage());

            return;
        }

        self::fail('Invalid PHPStan expectation maps must be rejected.');
    }

    /** @return iterable<string, array{array<array-key, mixed>, string, non-empty-string|null}> */
    public static function invalidExpectationMapProvider(): iterable
    {
        yield 'integer path' => [[0 => []], 'Every PHPStan expectation file path must be a nonempty string.', null];
        yield 'blank path' => [[' ' => []], 'Every PHPStan expectation file path must be a nonempty string.', null];
        yield 'non-array expectations' => [
            ['a.php' => 'x'],
            'PHPStan expectations for a.php must form a list.',
            null,
        ];
        yield 'non-list expectations' => [
            ['a.php' => ['item' => new DiagnosticExpectation('x', 1)]],
            'PHPStan expectations for a.php must form a list.',
            null,
        ];
        yield 'non-expectation member' => [
            ['a.php' => ['x']],
            'Invalid PHPStan expectations for a.php: Diagnostic expectations must contain only expectation values.',
            'Diagnostic expectations must contain only expectation values.',
        ];
    }

    /**
     * @param array<mixed> $globalErrors
     * @param array<array-key, mixed> $matchesByFile
     * @param array<array-key, mixed> $mismatchesByFile
     */
    #[DataProvider('invalidVerificationResultProvider')]
    public function testVerificationResultRejectsMalformedEvidence(
        array $globalErrors,
        array $matchesByFile,
        array $mismatchesByFile,
        string $message,
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new PhpStanVerificationResult($globalErrors, $matchesByFile, $mismatchesByFile);
    }

    /**
     * @return iterable<string, array{array<mixed>, array<array-key, mixed>, array<array-key, mixed>, string}>
     */
    public static function invalidVerificationResultProvider(): iterable
    {
        $matched = new DiagnosticsMatched([]);
        $mismatched = new DiagnosticsMismatched(
            DiagnosticMismatchKind::Count,
            [],
            [new AnalyzerDiagnostic(null, 'x')],
        );

        yield 'global errors not a list' => [['key' => 'x'], [], [], 'must form a list'];
        yield 'non-string global error' => [[1], [], [], 'must be a nonempty string'];
        yield 'blank global error' => [[' '], [], [], 'must be a nonempty string'];
        yield 'integer match path' => [[], [0 => $matched], [], 'path must be a nonempty string'];
        yield 'blank match path' => [[], [' ' => $matched], [], 'path must be a nonempty string'];
        yield 'wrong match type' => [[], ['a.php' => $mismatched], [], 'must contain match results'];
        yield 'integer mismatch path' => [[], [], [0 => $mismatched], 'path must be a nonempty string'];
        yield 'blank mismatch path' => [[], [], [' ' => $mismatched], 'path must be a nonempty string'];
        yield 'wrong mismatch type' => [[], [], ['a.php' => $matched], 'must contain mismatch results'];
        yield 'overlapping path' => [
            [],
            ['a.php' => $matched],
            ['a.php' => $mismatched],
            'cannot be both matched and mismatched',
        ];
    }

    /**
     * @param array<non-empty-string, list<AnalyzerDiagnostic>> $diagnosticsByFile
     * @param list<non-empty-string> $globalErrors
     */
    private static function analyzerResult(array $diagnosticsByFile = [], array $globalErrors = []): PhpStanJsonResult
    {
        return new PhpStanJsonResult(
            count($globalErrors),
            array_sum(array_map(count(...), $diagnosticsByFile)),
            $globalErrors,
            $diagnosticsByFile,
        );
    }
}
