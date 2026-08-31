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

namespace jbboehr\Akashi\Tests\Conformance;

use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Execution\RuntimeConfiguration;
use jbboehr\Akashi\Integration\PhpUnit\PhpUnitRuntime;
use jbboehr\Akashi\Integration\PhpUnit\VerifiesPhpUnitExamples;
use jbboehr\Akashi\Source\MarkdownSource;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

final class RuntimeConformanceTest extends TestCase
{
    use VerifiesPhpUnitExamples;

    public function testFailureReportsTheMaintainedMarkdownLocation(): void
    {
        $corpus = MarkdownSource::forProject(self::projectRoot())
            ->withFile('tests/Fixtures/Conformance/failure.md')
            ->load();

        try {
            PhpUnitRuntime::assertExample(iterator_to_array($corpus, false)[0]);
        } catch (ExpectationFailedException $failure) {
            self::assertStringContainsString(
                'Location: tests/Fixtures/Conformance/failure.md:5',
                $failure->getMessage(),
            );
            self::assertStringContainsString('conformance failure', $failure->getMessage());

            return;
        }

        self::fail('The deliberately failing conformance example must fail through the public PHPUnit facade.');
    }

    public function testExecutesTheReducedYumemiFixtureThroughThePublicFacade(): void
    {
        $fixtureRoot = self::projectRoot() . '/tests/Fixtures/Compatibility/Yumemi';
        $corpus = MarkdownSource::forProject($fixtureRoot)
            ->withFile('README.md')
            ->load();

        PhpUnitRuntime::assertExample(iterator_to_array($corpus, false)[0]);
    }

    protected static function akashiExampleCorpus(): ExampleCorpus
    {
        return MarkdownSource::forProject(self::projectRoot())
            ->withFile('tests/Fixtures/Conformance/runtime.md')
            ->load();
    }

    protected static function akashiRuntimeConfiguration(): RuntimeConfiguration
    {
        return RuntimeConfiguration::forProject(self::projectRoot());
    }

    private static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
