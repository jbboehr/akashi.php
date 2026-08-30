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

namespace jbboehr\Akashi\Tests\Fixtures\PhpUnit;

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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EscapedIntegerFormDataSetLabels extends TestCase
{
    #[DataProvider('examples')]
    public function testExample(Example $example): void
    {
        self::assertContains($example->label, ['2048', '~2048']);
    }

    /** @return \Generator<string, array{Example}, mixed, void> */
    public static function examples(): \Generator
    {
        yield from PhpUnitExampleDataSets::fromCorpus(new ExampleCorpus(
            self::example('escaped-data-set-label-01', '2048', 1),
            self::example('escaped-data-set-label-02', '~2048', 2),
        ));
    }

    /** @param positive-int $ordinal */
    private static function example(string $id, string $label, int $ordinal): Example
    {
        $code = 'assert(true);';

        return Example::fromInline(
            id: new ExampleId($id),
            label: $label,
            document: new Document('docs/escaped-data-set-labels.md', $code),
            location: new SourceLocation(
                9,
                10,
                10,
                11,
                new SourceSpan(0, strlen($code)),
                new SourceSpan(0, strlen($code)),
            ),
            language: new Language('php'),
            code: new ExampleCode($code),
            fence: new FenceMetadata('php', '`', 3, 0),
            ordinal: $ordinal,
        );
    }
}
