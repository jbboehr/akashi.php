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

namespace jbboehr\Akashi\Tests;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Example;
use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\CorpusExampleId;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\NamedExampleId;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use PHPUnit\Framework\TestCase;

final class ExampleCorpusTest extends TestCase
{
    public function testPreservesDeterministicOrderAndSupportsIteration(): void
    {
        $first = $this->example('example-a-01', 'docs/a.md', 1);
        $second = $this->example('example-a-02', 'docs/a.md', 2);
        $third = $this->example('example-b-01', 'docs/b.md', 1, 'selected-example');
        $corpus = new ExampleCorpus(first: $first, second: $second, third: $third);

        self::assertCount(3, $corpus);
        self::assertSame([$first, $second, $third], iterator_to_array($corpus));
    }

    public function testRejectsAnEmptyCorpus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Example corpus must not be empty.');

        new ExampleCorpus();
    }

    public function testRejectsDuplicateCorpusExampleIds(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate corpus example ID example-a-01.');

        new ExampleCorpus(
            $this->example('example-a-01', 'docs/a.md', 1),
            $this->example('example-a-01', 'docs/a.md', 2),
        );
    }

    public function testRejectsDuplicateExplicitNamedExampleIds(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate named example ID selected-example.');

        new ExampleCorpus(
            $this->example('example-a-01', 'docs/a.md', 1, 'selected-example'),
            $this->example('example-a-02', 'docs/a.md', 2, 'selected-example'),
        );
    }

    public function testRejectsDocumentPathOrderViolations(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Examples must be ordered by canonical source path, source line, and corpus example ID.');

        new ExampleCorpus(
            $this->example('example-b-01', 'docs/b.md', 1),
            $this->example('example-a-01', 'docs/a.md', 1),
        );
    }

    public function testRejectsSourceLineOrderViolationsWithinADocument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Examples must be ordered by canonical source path, source line, and corpus example ID.');

        new ExampleCorpus(
            $this->example('example-a-02', 'docs/a.md', 2),
            $this->example('example-a-01', 'docs/a.md', 1),
        );
    }

    public function testAllowsEqualOrdinalsWhenStableIdsOrderTheSameSourceLine(): void
    {
        self::assertCount(2, new ExampleCorpus(
            $this->example('example-a-01', 'docs/a.md', 1),
            $this->example('example-a-02', 'docs/a.md', 1),
        ));
    }

    public function testRejectsStableIdOrderViolationsOnTheSameSourceLine(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Examples must be ordered by canonical source path, source line, and corpus example ID.');

        new ExampleCorpus(
            $this->example('example-a-02', 'docs/a.md', 1),
            $this->example('example-a-01', 'docs/a.md', 1),
        );
    }

    /**
     * @param positive-int $ordinal
     */
    private function example(string $id, string $path, int $ordinal, ?string $namedId = null): Example
    {
        return Example::fromInline(
            corpusId: new CorpusExampleId($id),
            label: sprintf('%s PHP example %d', $path, $ordinal),
            document: new Document($path, ''),
            location: new SourceLocation(
                $ordinal * 2 - 1,
                $ordinal * 2,
                null,
                $ordinal * 2,
                new SourceSpan(0, 1),
                new SourceSpan(1, 1),
            ),
            language: new Language('php'),
            code: new ExampleCode("echo 1;\n"),
            fence: new FenceMetadata('php', '`', 3, 0),
            ordinal: $ordinal,
            namedId: $namedId === null ? null : new NamedExampleId($namedId),
        );
    }
}
