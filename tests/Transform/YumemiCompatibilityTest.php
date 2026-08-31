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

namespace jbboehr\Akashi\Tests\Transform;

use jbboehr\Akashi\Source\MarkdownSource;
use jbboehr\Akashi\Transform\Exception\UnsupportedExampleException;
use jbboehr\Akashi\Transform\ExecutionScope;
use jbboehr\Akashi\Transform\InProcessTransformer;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

final class YumemiCompatibilityTest extends TestCase
{
    public function testTransformsYumemiDerivedFixturesAndRecordsNamespaceExceptions(): void
    {
        $root = __DIR__ . '/../Fixtures/Compatibility/Yumemi';

        $corpus = MarkdownSource::forProject($root)
            ->withFile('README.md')
            ->withDirectory('docs/pages')
            ->load();
        $transformer = new InProcessTransformer();
        $parser = (new ParserFactory())->createForHostVersion();
        $transformed = 0;
        $unsupported = [];

        foreach ($corpus as $example) {
            try {
                $scope = new ExecutionScope(
                    'Akashi\\Compatibility\\Example_' . str_replace(['-', '.'], '_', $example->corpusId->value),
                );
                $prepared = $transformer->transform($example, $scope);
                $errors = new Collecting();
                $statements = $parser->parse($prepared->code->source, $errors);

                self::assertNotNull($statements, $example->label);
                self::assertFalse($errors->hasErrors(), $example->label);
                ++$transformed;
            } catch (UnsupportedExampleException $exception) {
                $unsupported[$example->label] = $exception->getMessage();
            }
        }

        self::assertCount(3, $corpus);
        self::assertSame(1, $transformed);
        self::assertSame([
            'docs/pages/recipes.md PHP example 1',
            'docs/pages/reference/phpstan.md PHP example 1',
        ], array_keys($unsupported));

        foreach ($unsupported as $message) {
            self::assertStringContainsString('authored namespace declarations are not supported in-process', $message);
            self::assertStringContainsString('Add // akashi: separate-process to the example code', $message);
            self::assertStringContainsString(
                'use <!-- akashi: separate-process --> before a documentation fence.',
                $message,
            );
        }
    }
}
