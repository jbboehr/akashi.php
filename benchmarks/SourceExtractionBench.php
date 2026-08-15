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
use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Markdown\CommonMarkExampleExtractor;
use jbboehr\Akashi\PhpDoc\PhpDocExampleExtractor;
use PhpBench\Attributes as Bench;

#[Bench\Iterations(5)]
#[Bench\Warmup(2)]
#[Bench\Groups(['source'])]
final class SourceExtractionBench
{
    private CommonMarkExampleExtractor $markdownExtractor;

    private PhpDocExampleExtractor $phpDocExtractor;

    private Document $smallMarkdown;

    private Document $largeMarkdown;

    private Document $phpDoc;

    /** @var non-empty-list<Example> */
    private array $largeExamples;

    public function setUp(): void
    {
        $this->markdownExtractor = new CommonMarkExampleExtractor();
        $this->phpDocExtractor = new PhpDocExampleExtractor();
        $this->smallMarkdown = self::markdownDocument(5);
        $this->largeMarkdown = self::markdownDocument(100);
        $this->phpDoc = self::phpDocDocument(40);

        $largeExamples = $this->markdownExtractor->extract($this->largeMarkdown);
        if ($largeExamples === []) {
            throw new \LogicException('The large benchmark document must contain examples.');
        }
        $this->largeExamples = $largeExamples;
    }

    /** @return list<Example> */
    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(50)]
    public function benchExtractSmallMarkdownDocument(): array
    {
        return $this->markdownExtractor->extract($this->smallMarkdown);
    }

    /** @return list<Example> */
    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(5)]
    public function benchExtractLargeMarkdownDocument(): array
    {
        return $this->markdownExtractor->extract($this->largeMarkdown);
    }

    /** @return list<Example> */
    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(10)]
    public function benchExtractPhpDocDocument(): array
    {
        return $this->phpDocExtractor->extract($this->phpDoc);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(50)]
    public function benchConstructLargeCorpus(): ExampleCorpus
    {
        return new ExampleCorpus(...$this->largeExamples);
    }

    private static function markdownDocument(int $exampleCount): Document
    {
        $contents = "# Benchmark fixture\n\n";
        for ($index = 1; $index <= $exampleCount; ++$index) {
            $contents .= sprintf(
                "<!-- akashi: example=benchmark-%03d -->\n```php\n\$value = %d;\nassert(\$value === %d);\n```\n\n",
                $index,
                $index,
                $index,
            );
        }

        return new Document('docs/benchmark.md', $contents);
    }

    private static function phpDocDocument(int $exampleCount): Document
    {
        $contents = "<?php\n\nfinal class BenchmarkFixture\n{\n";
        for ($index = 1; $index <= $exampleCount; ++$index) {
            $contents .= sprintf(
                <<<'PHP'
    /**
     * ```php
     * $value = %d;
     * assert($value === %d);
     * ```
     */
    public function example%d(): void {}

PHP,
                $index,
                $index,
                $index,
            );
        }
        $contents .= "}\n";

        return new Document('src/BenchmarkFixture.php', $contents);
    }
}
