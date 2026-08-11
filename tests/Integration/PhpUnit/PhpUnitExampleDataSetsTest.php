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
            ['2048', [$second]],
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
