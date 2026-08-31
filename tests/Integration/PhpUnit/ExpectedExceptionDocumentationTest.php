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

use jbboehr\Akashi\Integration\PhpUnit\PhpUnitRuntime;
use jbboehr\Akashi\Source\MarkedExampleSelector;
use jbboehr\Akashi\Source\MarkdownSource;
use PHPUnit\Framework\TestCase;

final class ExpectedExceptionDocumentationTest extends TestCase
{
    public function testThePublishedExpectedExceptionExampleRunsThroughAkashi(): void
    {
        $projectRoot = dirname(__DIR__, 3);
        $corpus = MarkdownSource::forProject($projectRoot)
            ->withFile('docs/pages/using/phpunit.md')
            ->load();
        $example = (new MarkedExampleSelector())->select($corpus, 'expected-domain-exception');

        PhpUnitRuntime::assertExample($example);
    }

    public function testThePublishedCompileOnlyExampleRunsThroughAkashi(): void
    {
        $projectRoot = dirname(__DIR__, 3);
        $corpus = MarkdownSource::forProject($projectRoot)
            ->withFile('docs/pages/reference/directives.md')
            ->load();
        $example = (new MarkedExampleSelector())->select($corpus, 'compile-only-runtime');

        PhpUnitRuntime::assertExample($example);
    }
}
