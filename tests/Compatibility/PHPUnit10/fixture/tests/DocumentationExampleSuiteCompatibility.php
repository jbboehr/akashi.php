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

namespace Akashi\PHPUnit10Compatibility;

use jbboehr\Akashi\Execution\RuntimeConfiguration;
use jbboehr\Akashi\Integration\PhpUnit\PhpUnitExampleSuite;
use jbboehr\Akashi\Integration\PhpUnit\VerifiesPhpUnitExampleSuite;
use jbboehr\Akashi\Source\MarkdownSource;
use PHPUnit\Framework\TestCase;

final class DocumentationExampleSuiteCompatibility extends TestCase
{
    use VerifiesPhpUnitExampleSuite;

    private static int $suiteHookCalls = 0;

    public function testSuiteHookRanOnceDuringDataProviderGeneration(): void
    {
        self::assertSame(1, self::$suiteHookCalls);
    }

    protected static function akashiExampleSuite(): PhpUnitExampleSuite
    {
        ++self::$suiteHookCalls;
        $projectRoot = dirname(__DIR__);

        return new PhpUnitExampleSuite(
            MarkdownSource::forProject($projectRoot)
                ->includeFile('docs/examples.md')
                ->load(),
            RuntimeConfiguration::forProject($projectRoot),
        );
    }
}
