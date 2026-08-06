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

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Example;
use jbboehr\Akashi\Integration\PhpUnit\PhpUnitRuntime;
use jbboehr\Akashi\Model\Directive;
use jbboehr\Akashi\Model\DirectiveSet;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\MetadataLocation;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use jbboehr\Akashi\Transform\Exception\UnsupportedExampleException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

final class PhpUnitRuntimeTest extends TestCase
{
    public function testExecutesAnExampleAndRecordsOneCompletionAssertion(): void
    {
        $before = Assert::getCount();

        PhpUnitRuntime::assertExample($this->example("echo 'captured';"));

        $after = Assert::getCount();
        self::assertSame($before + 1, $after);
    }

    public function testIsolatesDuplicateDeclarationsAcrossFacadeCalls(): void
    {
        $before = Assert::getCount();

        PhpUnitRuntime::assertExample($this->example(
            "function shared_runtime_name(): string { return 'first'; }\nassert(shared_runtime_name() === 'first');",
            'example-runtime-01',
        ));
        PhpUnitRuntime::assertExample($this->example(
            "function shared_runtime_name(): string { return 'second'; }\nassert(shared_runtime_name() === 'second');",
            'example-runtime-02',
        ));

        $after = Assert::getCount();
        self::assertSame($before + 4, $after);
    }

    public function testReportsARewrittenAssertionAtTheMaintainedSourceLine(): void
    {
        try {
            PhpUnitRuntime::assertExample($this->example("echo 'before';\nassert(false, 'runtime failure');"));
        } catch (ExpectationFailedException $failure) {
            self::assertStringContainsString('Documentation example example-runtime-01 failed', $failure->getMessage());
            self::assertStringContainsString('Location: docs/runtime.md:11', $failure->getMessage());
            self::assertStringContainsString('runtime failure', $failure->getMessage());
            self::assertStringContainsString("Captured stdout:\n    before", $failure->getMessage());
            self::assertInstanceOf(ExpectationFailedException::class, $failure->getPrevious());

            return;
        }

        self::fail('A failing documentation assertion must fail the PHPUnit data set.');
    }

    /**
     * @param positive-int|null $directiveLine
     * @param positive-int $expectedLine
     */
    #[DataProvider('separateProcessLocationProvider')]
    public function testRejectsASeparateProcessDirectiveBeforeExecutingTheExample(
        ?int $directiveLine,
        int $expectedLine,
    ): void {
        $example = $this->example(
            "throw new LogicException('must not execute');",
            directives: new DirectiveSet(Directive::SeparateProcess),
            directiveLine: $directiveLine,
        );

        $this->expectException(UnsupportedExampleException::class);
        $this->expectExceptionMessage(sprintf(
            'Example example-runtime-01 at docs/runtime.md:%d requests separate-process execution, '
            . 'but that backend is not implemented.',
            $expectedLine,
        ));

        PhpUnitRuntime::assertExample($example);
    }

    /** @return iterable<string, array{positive-int|null, positive-int}> */
    public static function separateProcessLocationProvider(): iterable
    {
        yield 'directive location' => [8, 8];
        yield 'example-start fallback' => [null, 10];
    }

    /**
     * @param non-empty-string $source
     * @param positive-int|null $directiveLine
     */
    private function example(
        string $source,
        string $id = 'example-runtime-01',
        DirectiveSet $directives = new DirectiveSet(),
        ?int $directiveLine = null,
    ): Example {
        $lineBreaks = preg_match_all('/\r\n|\r|\n/', $source);
        if ($lineBreaks === false) {
            throw new \LogicException('Unable to count fixture source lines.');
        }

        $lineCount = $lineBreaks + 1;
        if (preg_match('/(?:\r\n|\r|\n)\z/', $source) === 1) {
            --$lineCount;
        }

        $sourceLength = strlen($source);
        $firstCodeLine = 10;
        $lastCodeLine = $firstCodeLine + $lineCount - 1;

        return new Example(
            id: new ExampleId($id),
            label: 'PHPUnit runtime fixture',
            document: new Document('docs/runtime.md', $source),
            location: new SourceLocation(
                $firstCodeLine - 1,
                $firstCodeLine,
                $lastCodeLine,
                $lastCodeLine + 1,
                new SourceSpan(0, $sourceLength),
                new SourceSpan(0, $sourceLength),
                new MetadataLocation(separateProcessDirectiveLine: $directiveLine),
            ),
            language: new Language('php'),
            code: new ExampleCode($source),
            fence: new FenceMetadata('php', '`', 3, 0),
            ordinal: 1,
            directives: $directives,
        );
    }
}
