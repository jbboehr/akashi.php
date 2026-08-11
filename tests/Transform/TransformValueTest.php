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

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Example;
use jbboehr\Akashi\Execution\ExecutionMode;
use jbboehr\Akashi\Model\DocumentPath;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use jbboehr\Akashi\Transform\ExecutionScope;
use jbboehr\Akashi\Transform\ExecutionScopeFactory;
use jbboehr\Akashi\Transform\InProcessPreparedExample;
use jbboehr\Akashi\Transform\PreparedCode;
use jbboehr\Akashi\Transform\PreparedSource;
use jbboehr\Akashi\Transform\ParsedPhp;
use jbboehr\Akashi\Transform\SourceMap;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

final class TransformValueTest extends TestCase
{
    public function testRepresentsPreparedCodeAndMappedLines(): void
    {
        $code = new PreparedCode("<?php\r\necho 1;\r\n");
        $map = new SourceMap(new DocumentPath('docs/example.md'), [null, 8, 8]);

        self::assertSame("<?php\r\necho 1;\r\n", $code->source);
        self::assertSame(3, $code->generatedLineCount());
        self::assertSame(3, $map->generatedLineCount());
        self::assertNull($map->sourceLineFor(1));
        self::assertSame(8, $map->sourceLineFor(2));
        self::assertSame('docs/example.md', $map->sourcePath->value);
    }

    #[DataProvider('invalidPreparedCodeProvider')]
    public function testRejectsSourceWithoutAStandardOpeningTag(string $source): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Prepared PHP source must begin with a standard opening tag.');

        new PreparedCode($source);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidPreparedCodeProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'tag omitted' => ['echo 1;'];
        yield 'short echo tag' => ['<?= 1 ?>'];
    }

    public function testRejectsAnEmptySourceMap(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A source map must contain at least one generated line.');

        new SourceMap(new DocumentPath('docs/example.md'), []);
    }

    public function testRejectsANonpositiveMappedLine(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Mapped source lines must be positive.');

        new SourceMap(new DocumentPath('docs/example.md'), [0]);
    }

    public function testRejectsMappedLinesThatAreNotAList(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Mapped source lines must form a list.');

        new SourceMap(new DocumentPath('docs/example.md'), [1 => 2]);
    }

    #[DataProvider('outOfBoundsLineProvider')]
    public function testRejectsAnOutOfBoundsGeneratedLine(int $line): void
    {
        $map = new SourceMap(new DocumentPath('docs/example.md'), [1]);

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage(sprintf('Generated line %d is outside the source map.', $line));

        $map->sourceLineFor($line);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function outOfBoundsLineProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'past end' => [2];
    }

    public function testRejectsMismatchedPreparedCodeAndMap(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Prepared code and source map must contain the same number of lines.');

        new InProcessPreparedExample(
            $this->example(),
            new PreparedCode("<?php\necho 1;\n"),
            new SourceMap(new DocumentPath('docs/example.md'), [1]),
            new ExecutionScope('Akashi\\Generated\\Example_test_0123456789abcdef'),
        );
    }

    public function testRejectsMismatchedPreparedSourceAndMap(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Prepared code and source map must contain the same number of lines.');

        new PreparedSource(
            new PreparedCode("<?php\necho 1;\n"),
            new SourceMap(new DocumentPath('docs/example.md'), [1]),
        );
    }

    public function testRejectsMismatchedParsedSourceAndMap(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parsed source and source map must contain the same number of lines.');

        new ParsedPhp(
            "<?php\n",
            [],
            [],
            new SourceMap(new DocumentPath('docs/example.md'), [null]),
        );
    }

    public function testExecutionModesHaveStableValues(): void
    {
        $values = array_map(
            static fn (\ReflectionEnumBackedCase $case): int|string => $case->getBackingValue(),
            (new \ReflectionEnum(ExecutionMode::class))->getCases(),
        );

        self::assertSame(['in-process', 'separate-process'], $values);
    }

    public function testExecutionScopeFactoryIsSeedableAndCollisionResistant(): void
    {
        $seed = hash('sha256', 'akashi transform test', true);
        $firstFactory = new ExecutionScopeFactory(new Randomizer(new Xoshiro256StarStar($seed)));
        $secondFactory = new ExecutionScopeFactory(new Randomizer(new Xoshiro256StarStar($seed)));
        $exampleId = new ExampleId('example-docs-guide-01');

        $first = $firstFactory->create($exampleId);
        $repeat = $secondFactory->create($exampleId);
        $next = $firstFactory->create($exampleId);

        self::assertSame($first->namespace, $repeat->namespace);
        self::assertNotSame($first->namespace, $next->namespace);
        self::assertMatchesRegularExpression(
            '/\Ajbboehr\\\\Akashi\\\\Generated\\\\Example_example_docs_guide_01_[a-f0-9]{32}\z/',
            $first->namespace,
        );
    }

    #[DataProvider('invalidScopeProvider')]
    public function testRejectsInvalidExecutionNamespaces(string $namespace): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Execution namespace must be a valid fully qualified PHP name.');

        new ExecutionScope($namespace);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidScopeProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'leading slash' => ['\\Akashi\\Generated'];
        yield 'invalid segment' => ['Akashi\\not-valid'];
        yield 'empty segment' => ['Akashi\\\\Generated'];
    }

    private function example(): Example
    {
        return Example::fromInline(
            id: new ExampleId('example-value-01'),
            label: 'Value fixture',
            document: new Document('docs/example.md', "echo 1;\n"),
            location: new SourceLocation(1, 2, 2, 3, new SourceSpan(0, 8), new SourceSpan(0, 8)),
            language: new Language('php'),
            code: new ExampleCode("echo 1;\n"),
            fence: new FenceMetadata('php', '`', 3, 0),
            ordinal: 1,
        );
    }
}
