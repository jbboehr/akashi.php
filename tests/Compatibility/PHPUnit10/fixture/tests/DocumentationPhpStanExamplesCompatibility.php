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

use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Integration\PHPStan\PhpStanExampleConfiguration;
use jbboehr\Akashi\Integration\PHPStan\VerifiesPhpStanExamples;
use jbboehr\Akashi\Source\MarkdownSource;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<DocumentationAssertRule> */
final class DocumentationPhpStanExamplesCompatibility extends RuleTestCase
{
    use VerifiesPhpStanExamples;

    public function testDocumentationExamplesWithPhpStan(): void
    {
        $this->assertPhpStanExamples(
            self::corpus(),
            PhpStanExampleConfiguration::forTokens(dirname(__DIR__), '//!'),
        );
    }

    protected function getRule(): Rule
    {
        return new DocumentationAssertRule();
    }

    private static function corpus(): ExampleCorpus
    {
        return MarkdownSource::forProject(dirname(__DIR__))
            ->includeFile('docs/examples.md')
            ->load();
    }
}
