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
use jbboehr\Akashi\Execution\RuntimeConfiguration;
use jbboehr\Akashi\Integration\PhpUnit\VerifiesPhpUnitExamples;
use jbboehr\Akashi\Source\MarkdownSource;
// akashi-region: sync-check
use PHPUnit\Framework\TestCase;

// akashi-region-end: sync-check

final class DocumentationExamplesCompatibility extends TestCase
{
    use VerifiesPhpUnitExamples;

    public function testRewritesASynchronizedPresentationInMemory(): void
    {
        $projectRoot = dirname(__DIR__);
        $path = $projectRoot . '/docs/sync-stale.md';
        $contents = file_get_contents($path);
        self::assertNotFalse($contents);
        $checker = \jbboehr\Akashi\Synchronization\SynchronizationChecker::forProject($projectRoot);

        $rewritten = $checker->rewrite(new \jbboehr\Akashi\Document('docs/sync-stale.md', $contents));

        self::assertStringContainsString('use PHPUnit\\Framework\\TestCase;', $rewritten->contents);
        self::assertSame([], $checker->check($rewritten));
        self::assertSame($contents, file_get_contents($path));
    }

    protected static function akashiExampleCorpus(): ExampleCorpus
    {
        return MarkdownSource::forProject(dirname(__DIR__))
            ->includeFile('docs/examples.md')
            ->load();
    }

    protected static function akashiRuntimeConfiguration(): RuntimeConfiguration
    {
        return RuntimeConfiguration::forProject(dirname(__DIR__));
    }
}
