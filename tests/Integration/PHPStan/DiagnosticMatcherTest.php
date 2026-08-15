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
use jbboehr\Akashi\Integration\PHPStan\DiagnosticAssignment;
use jbboehr\Akashi\Integration\PHPStan\DiagnosticExpectation;
use jbboehr\Akashi\Integration\PHPStan\DiagnosticMatcher;
use jbboehr\Akashi\Integration\PHPStan\DiagnosticMismatchKind;
use jbboehr\Akashi\Integration\PHPStan\DiagnosticsMatched;
use jbboehr\Akashi\Integration\PHPStan\DiagnosticsMismatched;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DiagnosticMatcherTest extends TestCase
{
    public function testRepresentsExpectationsAndAnalyzerDiagnosticsWithoutAnalyzerTypes(): void
    {
        $expectation = new DiagnosticExpectation(
            'expected phrase',
            17,
            'argument.type',
            ['first' => 18, 'last' => 20],
        );
        $diagnostic = new AnalyzerDiagnostic('argument.type', 'primary message', 'helpful tip', 3, 17);

        self::assertSame('expected phrase', $expectation->text);
        self::assertSame('argument.type', $expectation->identifier);
        self::assertSame(['first' => 18, 'last' => 20], $expectation->sourceLineRange);
        self::assertSame(17, $expectation->sourceLine);
        self::assertSame('argument.type', $diagnostic->identifier);
        self::assertSame('primary message', $diagnostic->message);
        self::assertSame('helpful tip', $diagnostic->tip);
        self::assertSame(3, $diagnostic->analyzerLine);
        self::assertSame(17, $diagnostic->sourceLine);
        self::assertNull($diagnostic->ignorable);
        self::assertSame("primary message\nhelpful tip", $diagnostic->searchableText());
    }

    public function testRepresentsOptionalDiagnosticMetadata(): void
    {
        $expectation = new DiagnosticExpectation('message', 1);
        $diagnostic = new AnalyzerDiagnostic(null, 'message');
        $boundaryDiagnostic = new AnalyzerDiagnostic(null, 'message', null, 1, 1);

        self::assertSame(1, $expectation->sourceLine);
        self::assertNull($expectation->identifier);
        self::assertNull($expectation->sourceLineRange);
        self::assertNull($diagnostic->identifier);
        self::assertNull($diagnostic->tip);
        self::assertNull($diagnostic->analyzerLine);
        self::assertNull($diagnostic->sourceLine);
        self::assertSame('message', $diagnostic->searchableText());
        self::assertSame(1, $boundaryDiagnostic->analyzerLine);
        self::assertSame(1, $boundaryDiagnostic->sourceLine);
    }

    /** @param array<array-key, mixed>|null $lineRange */
    #[DataProvider('invalidExpectationProvider')]
    public function testRejectsInvalidExpectations(
        ?string $text,
        int $line,
        ?string $identifier,
        ?array $lineRange,
        string $message,
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new DiagnosticExpectation($text, $line, $identifier, $lineRange);
    }

    /** @return iterable<string, array{string|null, int, string|null, array<array-key, mixed>|null, string}> */
    public static function invalidExpectationProvider(): iterable
    {
        yield 'empty text' => [' ', 1, null, null, 'text must not be empty'];
        yield 'empty identifier' => [null, 1, ' ', null, 'identifier must not be empty'];
        yield 'no constraint' => [null, 1, null, null, 'must constrain text, an identifier, or both'];
        yield 'nonpositive line' => ['message', 0, null, null, 'source line must be positive'];
        yield 'missing line-range field' => [
            null,
            1,
            'argument.type',
            ['first' => 2],
            'line range must contain ordered positive first and last lines',
        ];
        yield 'extra line-range field' => [
            null,
            1,
            'argument.type',
            ['first' => 2, 'last' => 3, 'other' => 4],
            'line range must contain ordered positive first and last lines',
        ];
        yield 'noninteger line-range field' => [
            null,
            1,
            'argument.type',
            ['first' => '2', 'last' => 3],
            'line range must contain ordered positive first and last lines',
        ];
        yield 'nonpositive line-range start' => [
            null,
            1,
            'argument.type',
            ['first' => 0, 'last' => 3],
            'line range must contain ordered positive first and last lines',
        ];
        yield 'reversed line range' => [
            null,
            1,
            'argument.type',
            ['first' => 4, 'last' => 3],
            'line range must contain ordered positive first and last lines',
        ];
    }

    #[DataProvider('invalidDiagnosticProvider')]
    public function testRejectsInvalidDiagnostics(
        ?string $identifier,
        string $message,
        ?string $tip,
        ?int $analyzerLine,
        ?int $sourceLine,
        string $expectedMessage,
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        new AnalyzerDiagnostic($identifier, $message, $tip, $analyzerLine, $sourceLine);
    }

    /** @return iterable<string, array{?string, string, ?string, ?int, ?int, string}> */
    public static function invalidDiagnosticProvider(): iterable
    {
        yield 'empty identifier' => [' ', 'message', null, null, null, 'identifier must not be empty'];
        yield 'empty message' => [null, "\t", null, null, null, 'message must not be empty'];
        yield 'empty tip' => [null, 'message', "\t", null, null, 'tip must not be empty'];
        yield 'nonpositive analyzer line' => [null, 'message', null, 0, null, 'line must be positive'];
        yield 'nonpositive source line' => [null, 'message', null, null, -1, 'source line must be positive'];
    }

    public function testMatchesCleanExamplesWithoutFabricatingAssignments(): void
    {
        $result = (new DiagnosticMatcher())->match([], []);

        self::assertInstanceOf(DiagnosticsMatched::class, $result);
        self::assertSame([], $result->assignments);
    }

    public function testMatchesCaseSensitiveSubstringsInMessagesAndTips(): void
    {
        $messageExpectation = new DiagnosticExpectation('message phrase', 10);
        $tipExpectation = new DiagnosticExpectation('tip phrase', 11);
        $messageDiagnostic = new AnalyzerDiagnostic(null, 'A message phrase appears here.');
        $tipDiagnostic = new AnalyzerDiagnostic(null, 'Primary text.', 'A tip phrase appears here.');

        $result = (new DiagnosticMatcher())->match(
            [$messageExpectation, $tipExpectation],
            [$messageDiagnostic, $tipDiagnostic],
        );

        self::assertInstanceOf(DiagnosticsMatched::class, $result);
        self::assertCount(2, $result->assignments);
        self::assertSame($messageExpectation, $result->assignments[0]->expectation);
        self::assertSame($messageDiagnostic, $result->assignments[0]->diagnostic);
        self::assertSame($tipExpectation, $result->assignments[1]->expectation);
        self::assertSame($tipDiagnostic, $result->assignments[1]->diagnostic);
    }

    public function testMatchesExactIdentifiersWithOptionalTextConstraints(): void
    {
        $identifierOnly = new DiagnosticExpectation(null, 10, 'argument.type');
        $combined = new DiagnosticExpectation('specific problem', 11, 'method.notFound');
        $argumentDiagnostic = new AnalyzerDiagnostic('argument.type', 'Mutable wording.');
        $methodDiagnostic = new AnalyzerDiagnostic('method.notFound', 'A specific problem occurred.');

        $result = (new DiagnosticMatcher())->match(
            [$identifierOnly, $combined],
            [$methodDiagnostic, $argumentDiagnostic],
        );

        self::assertInstanceOf(DiagnosticsMatched::class, $result);
        self::assertSame($argumentDiagnostic, $result->assignments[0]->diagnostic);
        self::assertSame($methodDiagnostic, $result->assignments[1]->diagnostic);
    }

    #[DataProvider('identifierMismatchProvider')]
    public function testRejectsIdentifierOrCombinedTextMismatches(
        DiagnosticExpectation $expectation,
        AnalyzerDiagnostic $diagnostic,
    ): void {
        $result = (new DiagnosticMatcher())->match([$expectation], [$diagnostic]);

        self::assertInstanceOf(DiagnosticsMismatched::class, $result);
        self::assertSame(DiagnosticMismatchKind::Assignment, $result->kind);
    }

    /** @return iterable<string, array{DiagnosticExpectation, AnalyzerDiagnostic}> */
    public static function identifierMismatchProvider(): iterable
    {
        yield 'different identifier' => [
            new DiagnosticExpectation(null, 10, 'argument.type'),
            new AnalyzerDiagnostic('return.type', 'Same mutable message.'),
        ];
        yield 'missing identifier' => [
            new DiagnosticExpectation(null, 10, 'argument.type'),
            new AnalyzerDiagnostic(null, 'Same mutable message.'),
        ];
        yield 'identifier is case sensitive' => [
            new DiagnosticExpectation(null, 10, 'Argument.Type'),
            new AnalyzerDiagnostic('argument.type', 'Same mutable message.'),
        ];
        yield 'combined text mismatch' => [
            new DiagnosticExpectation('expected text', 10, 'argument.type'),
            new AnalyzerDiagnostic('argument.type', 'different text'),
        ];
        yield 'diagnostic before statement' => [
            new DiagnosticExpectation(null, 10, 'argument.type', ['first' => 20, 'last' => 22]),
            new AnalyzerDiagnostic('argument.type', 'message', analyzerLine: 19),
        ];
        yield 'diagnostic after statement' => [
            new DiagnosticExpectation(null, 10, 'argument.type', ['first' => 20, 'last' => 22]),
            new AnalyzerDiagnostic('argument.type', 'message', analyzerLine: 23),
        ];
        yield 'diagnostic without a line' => [
            new DiagnosticExpectation(null, 10, 'argument.type', ['first' => 20, 'last' => 22]),
            new AnalyzerDiagnostic('argument.type', 'message'),
        ];
    }

    public function testMatchesEitherBoundaryAndPrefersMaintainedSourceLines(): void
    {
        $first = new DiagnosticExpectation(null, 10, 'argument.type', ['first' => 20, 'last' => 22]);
        $last = new DiagnosticExpectation(null, 11, 'return.type', ['first' => 30, 'last' => 32]);

        $result = (new DiagnosticMatcher())->match(
            [$first, $last],
            [
                new AnalyzerDiagnostic('argument.type', 'first', analyzerLine: 999, sourceLine: 20),
                new AnalyzerDiagnostic('return.type', 'last', analyzerLine: 1, sourceLine: 32),
            ],
        );

        self::assertInstanceOf(DiagnosticsMatched::class, $result);
    }

    public function testRejectsACaseMismatchedPhrase(): void
    {
        $expectation = new DiagnosticExpectation('Expected Phrase', 10);
        $diagnostic = new AnalyzerDiagnostic(null, 'expected phrase');

        $result = (new DiagnosticMatcher())->match([$expectation], [$diagnostic]);

        self::assertInstanceOf(DiagnosticsMismatched::class, $result);
        self::assertSame(DiagnosticMismatchKind::Assignment, $result->kind);
    }

    public function testFindsACompleteOneToOneAssignmentInsteadOfMatchingGreedily(): void
    {
        $broadExpectation = new DiagnosticExpectation('problem', 10);
        $narrowExpectation = new DiagnosticExpectation('specific problem', 11);
        $specificDiagnostic = new AnalyzerDiagnostic(null, 'A specific problem occurred.');
        $generalDiagnostic = new AnalyzerDiagnostic(null, 'A general problem occurred.');

        $result = (new DiagnosticMatcher())->match(
            [$broadExpectation, $narrowExpectation],
            [$specificDiagnostic, $generalDiagnostic],
        );

        self::assertInstanceOf(DiagnosticsMatched::class, $result);
        self::assertSame($broadExpectation, $result->assignments[0]->expectation);
        self::assertSame($generalDiagnostic, $result->assignments[0]->diagnostic);
        self::assertSame($narrowExpectation, $result->assignments[1]->expectation);
        self::assertSame($specificDiagnostic, $result->assignments[1]->diagnostic);
    }

    public function testRetainsFailedSearchStateWhileTryingSiblingDiagnostics(): void
    {
        $lockedExpectation = new DiagnosticExpectation('locked', 10);
        $movableExpectation = new DiagnosticExpectation('movable', 11);
        $sharedExpectation = new DiagnosticExpectation('shared', 12);
        $lockedDiagnostic = new AnalyzerDiagnostic(null, 'locked shared');
        $sharedDiagnostic = new AnalyzerDiagnostic(null, 'movable shared');
        $fallbackDiagnostic = new AnalyzerDiagnostic(null, 'movable');

        $result = (new DiagnosticMatcher())->match(
            [$lockedExpectation, $movableExpectation, $sharedExpectation],
            [$lockedDiagnostic, $sharedDiagnostic, $fallbackDiagnostic],
        );

        self::assertInstanceOf(DiagnosticsMatched::class, $result);
        self::assertSame($lockedDiagnostic, $result->assignments[0]->diagnostic);
        self::assertSame($fallbackDiagnostic, $result->assignments[1]->diagnostic);
        self::assertSame($sharedDiagnostic, $result->assignments[2]->diagnostic);
    }

    /**
     * @param list<DiagnosticExpectation> $expectations
     * @param list<AnalyzerDiagnostic> $diagnostics
     */
    #[DataProvider('countMismatchProvider')]
    public function testReportsExactCountMismatches(array $expectations, array $diagnostics): void
    {
        $result = (new DiagnosticMatcher())->match($expectations, $diagnostics);

        self::assertInstanceOf(DiagnosticsMismatched::class, $result);
        self::assertSame(DiagnosticMismatchKind::Count, $result->kind);
        self::assertSame($expectations, $result->expectations);
        self::assertSame($diagnostics, $result->diagnostics);
    }

    /**
     * @return iterable<string, array{list<DiagnosticExpectation>, list<AnalyzerDiagnostic>}>
     */
    public static function countMismatchProvider(): iterable
    {
        $expectation = new DiagnosticExpectation('problem', 10);
        $diagnostic = new AnalyzerDiagnostic(null, 'problem');

        yield 'missing diagnostic' => [[$expectation], []];
        yield 'unexpected diagnostic in clean example' => [[], [$diagnostic]];
        yield 'surplus diagnostic' => [[$expectation], [$diagnostic, $diagnostic]];
    }

    public function testReportsAnAssignmentMismatchWhenEqualCountsCannotBePaired(): void
    {
        $expectations = [
            new DiagnosticExpectation('alpha', 10),
            new DiagnosticExpectation('beta', 11),
        ];
        $diagnostics = [
            new AnalyzerDiagnostic(null, 'alpha one'),
            new AnalyzerDiagnostic(null, 'alpha two'),
        ];

        $result = (new DiagnosticMatcher())->match($expectations, $diagnostics);

        self::assertInstanceOf(DiagnosticsMismatched::class, $result);
        self::assertSame(DiagnosticMismatchKind::Assignment, $result->kind);
        self::assertSame($expectations, $result->expectations);
        self::assertSame($diagnostics, $result->diagnostics);
    }

    public function testResultValuesRejectMalformedCollectionsAndContradictoryKinds(): void
    {
        $expectation = new DiagnosticExpectation('problem', 10);
        $diagnostic = new AnalyzerDiagnostic(null, 'problem');

        self::assertInvalidResult(
            static fn (): object => self::construct(
                DiagnosticsMatched::class,
                [[1 => new DiagnosticAssignment($expectation, $diagnostic)]],
            ),
            'assignments must form a list',
        );
        self::assertInvalidResult(
            static fn (): object => self::construct(DiagnosticsMatched::class, [[$expectation]]),
            'assignments must contain only assignment values',
        );
        self::assertInvalidResult(
            static fn (): DiagnosticsMismatched => new DiagnosticsMismatched(
                DiagnosticMismatchKind::Count,
                [$expectation],
                [$diagnostic],
            ),
            'count mismatch requires unequal',
        );
        self::assertInvalidResult(
            static fn (): DiagnosticsMismatched => new DiagnosticsMismatched(
                DiagnosticMismatchKind::Assignment,
                [$expectation],
                [],
            ),
            'assignment mismatch requires equal',
        );
        self::assertInvalidResult(
            static fn (): object => self::construct(
                DiagnosticsMismatched::class,
                [DiagnosticMismatchKind::Count, [1 => $expectation], []],
            ),
            'expectations must form a list',
        );
        self::assertInvalidResult(
            static fn (): object => self::construct(
                DiagnosticsMismatched::class,
                [DiagnosticMismatchKind::Count, [$diagnostic], []],
            ),
            'expectations must contain only expectation values',
        );
        self::assertInvalidResult(
            static fn (): object => self::construct(
                DiagnosticsMismatched::class,
                [DiagnosticMismatchKind::Count, [], [1 => $diagnostic]],
            ),
            'diagnostics must form a list',
        );
        self::assertInvalidResult(
            static fn (): object => self::construct(
                DiagnosticsMismatched::class,
                [DiagnosticMismatchKind::Count, [], [$expectation]],
            ),
            'diagnostics must contain only diagnostic values',
        );
    }

    public function testMatcherRejectsMalformedInputCollections(): void
    {
        self::assertInvalidResult(
            static fn () => self::invokeMatcher([1 => new DiagnosticExpectation('problem', 10)], []),
            'expectations must form a list',
        );
        self::assertInvalidResult(
            static fn () => self::invokeMatcher(
                [new AnalyzerDiagnostic(null, 'problem')],
                [new AnalyzerDiagnostic(null, 'problem')],
            ),
            'expectations must contain only expectation values',
        );
        self::assertInvalidResult(
            static fn () => self::invokeMatcher([], [1 => new AnalyzerDiagnostic(null, 'problem')]),
            'diagnostics must form a list',
        );
        self::assertInvalidResult(
            static fn () => self::invokeMatcher(
                [new DiagnosticExpectation('problem', 10)],
                [new DiagnosticExpectation('problem', 10)],
            ),
            'diagnostics must contain only diagnostic values',
        );
    }

    /** @param callable(): mixed $operation */
    private static function assertInvalidResult(callable $operation, string $message): void
    {
        try {
            $operation();
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString($message, $exception->getMessage());

            return;
        }

        self::fail('Expected an invalid result value to be rejected.');
    }

    /**
     * @param class-string $class
     * @param list<mixed> $arguments
     */
    private static function construct(string $class, array $arguments): object
    {
        return (new \ReflectionClass($class))->newInstanceArgs($arguments);
    }

    /**
     * @param array<int, mixed> $expectations
     * @param array<int, mixed> $diagnostics
     */
    private static function invokeMatcher(array $expectations, array $diagnostics): mixed
    {
        return (new \ReflectionMethod(DiagnosticMatcher::class, 'match'))->invoke(
            new DiagnosticMatcher(),
            $expectations,
            $diagnostics,
        );
    }
}
