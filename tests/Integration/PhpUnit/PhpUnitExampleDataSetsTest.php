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
use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Integration\PhpUnit\PhpUnitExampleDataSets;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class PhpUnitExampleDataSetsTest extends TestCase
{
    public function testYieldsOneNamedDataSetPerExampleInCorpusOrder(): void
    {
        $first = $this->example('example-data-set-01', 'First example', 1);
        $second = $this->example('example-data-set-02', '2048', 2);
        $observed = [];

        foreach (PhpUnitExampleDataSets::fromCorpus(new ExampleCorpus($first, $second)) as $label => $arguments) {
            $observed[] = [$label, $arguments];
        }

        self::assertSame([
            ['First example', [$first]],
            ['~2048', [$second]],
        ], $observed);
    }

    public function testRejectsDuplicateLabelsBeforeYieldingADataSet(): void
    {
        $dataSets = PhpUnitExampleDataSets::fromCorpus(new ExampleCorpus(
            $this->example('example-data-set-01', 'Repeated example', 1),
            $this->example('example-data-set-02', 'Repeated example', 2),
        ));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Duplicate PHPUnit data-set label Repeated example for examples example-data-set-01 and example-data-set-02.',
        );

        $dataSets->rewind();
    }

    public function testIntegerFormLabelsRemainNamedAndCannotCollideWithTheirEncodedForm(): void
    {
        $maximumInteger = (string) PHP_INT_MAX;
        $overflowInteger = $maximumInteger . '0';
        $dataSets = iterator_to_array(PhpUnitExampleDataSets::fromCorpus(new ExampleCorpus(
            $this->example('example-data-set-01', '0', 1),
            $this->example('example-data-set-02', '~0', 2),
            $this->example('example-data-set-03', '-1', 3),
            $this->example('example-data-set-04', '01', 4),
            $this->example('example-data-set-05', $maximumInteger, 5),
            $this->example('example-data-set-06', '~' . $maximumInteger, 6),
            $this->example('example-data-set-07', $overflowInteger, 7),
        )));

        self::assertSame([
            '~0',
            '~~0',
            '~-1',
            '01',
            '~' . $maximumInteger,
            '~~' . $maximumInteger,
            $overflowInteger,
        ], array_keys($dataSets));
    }

    public function testPhpUnitDiscoversAndFiltersIntegerFormAndTildePrefixedLabelsByTheirEscapedNames(): void
    {
        $fixture = 'tests/Fixtures/PhpUnit/EscapedIntegerFormDataSetLabels.php';
        $list = self::phpUnitReport(['--list-tests', $fixture]);

        self::assertStringContainsString('::testExample"~2048"', $list);
        self::assertStringContainsString('::testExample"~~2048"', $list);
        self::assertStringNotContainsString('::testExample#2048', $list);

        foreach (['~2048', '~~2048'] as $label) {
            $report = self::phpUnitReport([
                '--testdox',
                '--filter',
                'testExample@' . $label,
                $fixture,
            ]);

            self::assertStringContainsString(sprintf('with data set "%s"', $label), $report);
            self::assertStringContainsString('OK (1 test, 1 assertion)', $report);
        }
    }

    /** @param list<string> $arguments */
    private static function phpUnitReport(array $arguments): string
    {
        $projectRoot = dirname(__DIR__, 3);
        $process = new Process([
            PHP_BINARY,
            $projectRoot . '/vendor/bin/phpunit',
            '--no-configuration',
            '--bootstrap',
            $projectRoot . '/vendor/autoload.php',
            '--colors=never',
            '--do-not-cache-result',
            ...$arguments,
        ], $projectRoot);

        $process->run();

        $report = $process->getOutput() . $process->getErrorOutput();
        self::assertSame(0, $process->getExitCode(), $report);

        return $report;
    }

    /** @param positive-int $ordinal */
    private function example(string $id, string $label, int $ordinal): Example
    {
        return Example::fromInline(
            id: new ExampleId($id),
            label: $label,
            document: new Document('docs/data-sets.md', 'echo true;'),
            location: new SourceLocation(
                9,
                10,
                10,
                11,
                new SourceSpan(0, 10),
                new SourceSpan(0, 10),
            ),
            language: new Language('php'),
            code: new ExampleCode('echo true;'),
            fence: new FenceMetadata('php', '`', 3, 0),
            ordinal: $ordinal,
        );
    }
}
