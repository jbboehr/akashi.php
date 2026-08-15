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

namespace jbboehr\Akashi\Benchmarks;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Example;
use jbboehr\Akashi\Markdown\CommonMarkExampleExtractor;
use jbboehr\Akashi\Transform\ExecutionScope;
use jbboehr\Akashi\Transform\InProcessPreparedExample;
use jbboehr\Akashi\Transform\InProcessTransformer;
use PhpBench\Attributes as Bench;

#[Bench\Iterations(5)]
#[Bench\Warmup(2)]
#[Bench\Groups(['transform'])]
final class TransformationBench
{
    private InProcessTransformer $transformer;

    private Example $simpleExample;

    private Example $declarationHeavyExample;

    public function setUp(): void
    {
        $this->transformer = new InProcessTransformer();
        $this->simpleExample = self::example(<<<'PHP'
$result = strtoupper('akashi');
assert($result === 'AKASHI', 'the example must be transformed');
PHP);

        $source = "declare(strict_types=1);\n";
        for ($index = 1; $index <= 20; ++$index) {
            $source .= sprintf(
                "final class LocalValue%d { public function value(): int { return %d; } }\n",
                $index,
                $index,
            );
            $source .= sprintf("function local_value_%d(): int { return %d; }\n", $index, $index);
        }
        $source .= "\$value = local_value_20();\nassert(\$value === 20);";
        $this->declarationHeavyExample = self::example($source);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(50)]
    public function benchTransformSimpleExample(): InProcessPreparedExample
    {
        return $this->transformer->transform(
            $this->simpleExample,
            new ExecutionScope('Akashi\\Benchmark\\Simple'),
        );
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(5)]
    public function benchTransformDeclarationHeavyExample(): InProcessPreparedExample
    {
        return $this->transformer->transform(
            $this->declarationHeavyExample,
            new ExecutionScope('Akashi\\Benchmark\\Declarations'),
        );
    }

    private static function example(string $source): Example
    {
        $document = new Document(
            'docs/transform-benchmark.md',
            "```php\n" . $source . "\n```\n",
        );
        $examples = (new CommonMarkExampleExtractor())->extract($document);
        if (count($examples) !== 1) {
            throw new \LogicException('The transformation benchmark fixture must contain one example.');
        }

        return $examples[0];
    }
}
