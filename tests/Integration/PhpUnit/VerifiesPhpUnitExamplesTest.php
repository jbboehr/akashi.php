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
use jbboehr\Akashi\Integration\PhpUnit\VerifiesPhpUnitExamples;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use PHPUnit\Framework\TestCase;

final class VerifiesPhpUnitExamplesTest extends TestCase
{
    use VerifiesPhpUnitExamples;

    public function testProvidesOneNamedDataSetPerCorpusExample(): void
    {
        self::assertSame([
            'First trait example',
            'Second trait example',
        ], array_keys(iterator_to_array(self::akashiExampleDataProvider())));
    }

    public function testRuntimeConfigurationHookRemainsProtectedForConsumerOverrides(): void
    {
        $method = new \ReflectionMethod(VerifiesPhpUnitExamples::class, 'akashiRuntimeConfiguration');

        self::assertTrue($method->isProtected());
    }

    protected static function akashiExampleCorpus(): ExampleCorpus
    {
        return new ExampleCorpus(
            self::example('trait-example-01', 'First trait example', 1),
            self::example('trait-example-02', 'Second trait example', 2),
        );
    }

    /** @param positive-int $ordinal */
    private static function example(string $id, string $label, int $ordinal): Example
    {
        $code = 'assert(true);';

        return new Example(
            id: new ExampleId($id),
            label: $label,
            document: new Document('docs/trait.md', $code),
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
