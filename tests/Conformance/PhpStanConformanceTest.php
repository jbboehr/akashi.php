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

use jbboehr\Akashi\Integration\PHPStan\PhpStanExampleConfiguration;
use jbboehr\Akashi\Integration\PHPStan\VerifiesPhpStanExamples;
use jbboehr\Akashi\Source\MarkdownSource;
use PhpParser\Node;
use PhpParser\Node\Stmt\Echo_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;

/** @implements Rule<Echo_> */
final class ConformanceEchoRule implements Rule
{
    public function getNodeType(): string
    {
        return Echo_::class;
    }

    /**
     * @param Echo_ $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        return [RuleErrorBuilder::message('echo statements are forbidden by the Akashi conformance rule')
            ->identifier('akashi.conformanceEcho')
            ->build()];
    }
}

/** @extends RuleTestCase<ConformanceEchoRule> */
final class PhpStanConformanceTest extends RuleTestCase
{
    use VerifiesPhpStanExamples;

    public function testVerifiesACommonMarkCorpusThroughThePublicPhpStanAdapter(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $corpus = MarkdownSource::forProject($projectRoot)
            ->includeFile('tests/Fixtures/Conformance/phpstan.md')
            ->load();

        $this->assertPhpStanExamples(
            $corpus,
            PhpStanExampleConfiguration::forTokens($projectRoot, '@akashi-phpstan-example'),
        );
    }

    protected function getRule(): ConformanceEchoRule
    {
        return new ConformanceEchoRule();
    }
}
