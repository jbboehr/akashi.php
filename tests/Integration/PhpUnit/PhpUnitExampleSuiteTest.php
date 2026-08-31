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
use jbboehr\Akashi\Execution\RuntimeConfiguration;
use jbboehr\Akashi\Integration\PhpUnit\PhpUnitExampleSuite;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\CorpusExampleId;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use PHPUnit\Framework\TestCase;

final class PhpUnitExampleSuiteTest extends TestCase
{
    public function testKeepsOneCorpusAndItsRuntimeConfigurationTogether(): void
    {
        $corpus = new ExampleCorpus($this->example());
        $runtimeConfiguration = RuntimeConfiguration::forProject(dirname(__DIR__, 3));

        $suite = new PhpUnitExampleSuite(
            corpus: $corpus,
            runtimeConfiguration: $runtimeConfiguration,
        );

        self::assertSame($corpus, $suite->corpus);
        self::assertSame($runtimeConfiguration, $suite->runtimeConfiguration);
    }

    public function testUsesInProcessDefaultsWhenRuntimeConfigurationIsOmitted(): void
    {
        $suite = new PhpUnitExampleSuite(new ExampleCorpus($this->example()));

        self::assertNull($suite->runtimeConfiguration);
    }

    public function testExposedSuiteStateIsImmutable(): void
    {
        self::assertTrue((new \ReflectionProperty(PhpUnitExampleSuite::class, 'corpus'))->isReadOnly());
        self::assertTrue((new \ReflectionProperty(PhpUnitExampleSuite::class, 'runtimeConfiguration'))->isReadOnly());
    }

    private function example(): Example
    {
        $code = 'assert(true);';

        return Example::fromInline(
            corpusId: new CorpusExampleId('phpunit-suite-01'),
            label: 'PHPUnit suite example',
            document: new Document('docs/phpunit-suite.md', $code),
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
            ordinal: 1,
        );
    }
}
