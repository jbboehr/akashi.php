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

namespace jbboehr\Akashi\Benchmarks;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Example;
use jbboehr\Akashi\Integration\PHPStan\AnalyzerDiagnostic;
use jbboehr\Akashi\Integration\PHPStan\DiagnosticExpectation;
use jbboehr\Akashi\Integration\PHPStan\DiagnosticMatcher;
use jbboehr\Akashi\Integration\PHPStan\DiagnosticMatchResult;
use jbboehr\Akashi\Integration\PHPStan\ExpectationParser;
use jbboehr\Akashi\Integration\PHPStan\PhpStanJsonDecoder;
use jbboehr\Akashi\Integration\PHPStan\PhpStanJsonResult;
use jbboehr\Akashi\Markdown\CommonMarkExampleExtractor;
use PhpBench\Attributes as Bench;

#[Bench\Iterations(5)]
#[Bench\Warmup(2)]
#[Bench\Groups(['phpstan'])]
final class PhpStanVerificationBench
{
    private ExpectationParser $expectationParser;

    private DiagnosticMatcher $matcher;

    private PhpStanJsonDecoder $jsonDecoder;

    private Example $expectationExample;

    /** @var list<DiagnosticExpectation> */
    private array $exactExpectations;

    /** @var list<AnalyzerDiagnostic> */
    private array $exactDiagnostics;

    /** @var list<DiagnosticExpectation> */
    private array $denseExpectations;

    /** @var list<AnalyzerDiagnostic> */
    private array $denseDiagnostics;

    private string $json;

    public function setUp(): void
    {
        $this->expectationParser = new ExpectationParser();
        $this->matcher = new DiagnosticMatcher();
        $this->jsonDecoder = new PhpStanJsonDecoder();
        $this->expectationExample = self::expectationExample(40);
        [$this->exactExpectations, $this->exactDiagnostics] = self::exactDiagnostics(40);
        [$this->denseExpectations, $this->denseDiagnostics] = self::denseDiagnostics(12);
        $this->json = self::jsonResult(40);
    }

    /** @return list<DiagnosticExpectation> */
    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(10)]
    public function benchParseExpectations(): array
    {
        return $this->expectationParser->parse($this->expectationExample);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(50)]
    public function benchMatchExactDiagnostics(): DiagnosticMatchResult
    {
        return $this->matcher->match($this->exactExpectations, $this->exactDiagnostics);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(20)]
    public function benchMatchDenseDiagnostics(): DiagnosticMatchResult
    {
        return $this->matcher->match($this->denseExpectations, $this->denseDiagnostics);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(20)]
    public function benchDecodePhpStanJson(): PhpStanJsonResult
    {
        return $this->jsonDecoder->decode($this->json);
    }

    private static function expectationExample(int $expectationCount): Example
    {
        $source = '';
        for ($index = 1; $index <= $expectationCount; ++$index) {
            $source .= sprintf(
                "// @akashi-phpstan-error fixture.problem.%d: invalid call %d\ninvalid_call_%d();\n",
                $index,
                $index,
                $index,
            );
        }
        $document = new Document('docs/phpstan-benchmark.md', "```php\n" . $source . "```\n");
        $examples = (new CommonMarkExampleExtractor())->extract($document);
        if (count($examples) !== 1) {
            throw new \LogicException('The PHPStan benchmark fixture must contain one example.');
        }

        return $examples[0];
    }

    /**
     * @return array{list<DiagnosticExpectation>, list<AnalyzerDiagnostic>}
     */
    private static function exactDiagnostics(int $count): array
    {
        $expectations = [];
        $diagnostics = [];
        for ($index = 1; $index <= $count; ++$index) {
            $identifier = sprintf('fixture.problem.%d', $index);
            $line = $index * 2;
            $expectations[] = new DiagnosticExpectation(
                sprintf('invalid call %d', $index),
                $line - 1,
                $identifier,
                ['first' => $line, 'last' => $line],
            );
            $diagnostics[] = new AnalyzerDiagnostic(
                $identifier,
                sprintf('Detected invalid call %d.', $index),
                analyzerLine: $line,
                sourceLine: $line,
            );
        }

        return [$expectations, array_reverse($diagnostics)];
    }

    /**
     * @return array{list<DiagnosticExpectation>, list<AnalyzerDiagnostic>}
     */
    private static function denseDiagnostics(int $count): array
    {
        $expectations = [];
        $diagnostics = [];
        for ($index = 1; $index <= $count; ++$index) {
            $expectations[] = new DiagnosticExpectation('shared diagnostic', $index);
            $diagnostics[] = new AnalyzerDiagnostic(null, sprintf('shared diagnostic %d.', $index));
        }

        return [$expectations, $diagnostics];
    }

    private static function jsonResult(int $diagnosticCount): string
    {
        $messages = [];
        for ($index = 1; $index <= $diagnosticCount; ++$index) {
            $messages[] = [
                'message' => sprintf('Detected invalid call %d.', $index),
                'line' => $index,
                'ignorable' => true,
                'identifier' => sprintf('fixture.problem.%d', $index),
            ];
        }

        return json_encode([
            'totals' => ['errors' => 0, 'file_errors' => $diagnosticCount],
            'files' => [
                '/project/fixture.php' => [
                    'errors' => $diagnosticCount,
                    'messages' => $messages,
                ],
            ],
            'errors' => [],
        ], JSON_THROW_ON_ERROR);
    }
}
