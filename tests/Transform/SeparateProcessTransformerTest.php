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
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use jbboehr\Akashi\Transform\Exception\PhpParseException;
use jbboehr\Akashi\Transform\SeparateProcessPreparedExample;
use jbboehr\Akashi\Transform\SeparateProcessTransformer;
use PHPUnit\Framework\TestCase;

final class SeparateProcessTransformerTest extends TestCase
{
    public function testAddsOnlyASourceMappedOpeningTagToTaglessCode(): void
    {
        $prepared = $this->transform("echo 'first';\necho 'second';");

        self::assertSame("<?php\necho 'first';\necho 'second';", $prepared->code->source);
        self::assertSame(ExecutionMode::SeparateProcess, $prepared->executionMode);
        self::assertNull($prepared->sourceMap->sourceLineFor(1));
        self::assertSame(10, $prepared->sourceMap->sourceLineFor(2));
        self::assertSame(11, $prepared->sourceMap->sourceLineFor(3));
        self::assertFalse((new \ReflectionClass(SeparateProcessPreparedExample::class))->hasProperty('scope'));
    }

    public function testPreservesAnAuthoredOpeningTagAndStrictTypesPlacement(): void
    {
        $source = "<?PHP declare(strict_types=1);\necho 'strict';";
        $prepared = $this->transform($source);

        self::assertSame($source, $prepared->code->source);
        self::assertSame(10, $prepared->sourceMap->sourceLineFor(1));
        self::assertSame(11, $prepared->sourceMap->sourceLineFor(2));
    }

    public function testPreservesNativeAssertionsForChildStartupConfiguration(): void
    {
        $source = "assert(false, 'child failure');";
        $prepared = $this->transform($source);

        self::assertSame("<?php\n" . $source, $prepared->code->source);
        self::assertStringContainsString("assert(false, 'child failure');", $prepared->code->source);
        self::assertStringNotContainsString('NativeAssertion::evaluate', $prepared->code->source);
    }

    public function testAllowsRelocationSensitiveAndAuthoredNamespaceSource(): void
    {
        $source = "namespace Documented;\necho __DIR__;";
        $prepared = $this->transform($source);

        self::assertSame("<?php\n" . $source, $prepared->code->source);
    }

    public function testPreservesClosingTagsAndInlineHtmlForANormalPhpFile(): void
    {
        $source = "<?php echo 'php'; ?>\nplain text";
        $prepared = $this->transform($source);

        self::assertSame($source, $prepared->code->source);
        self::assertSame(10, $prepared->sourceMap->sourceLineFor(1));
        self::assertSame(11, $prepared->sourceMap->sourceLineFor(2));
    }

    public function testReportsParseFailuresAtTheMaintainedLine(): void
    {
        $this->expectException(PhpParseException::class);
        $this->expectExceptionMessage('example-separate-transform-01 at docs/separate.md:11');

        $this->transform("echo 'valid';\nif (");
    }

    private function transform(string $source): \jbboehr\Akashi\Transform\SeparateProcessPreparedExample
    {
        return (new SeparateProcessTransformer())->transform($this->example($source));
    }

    private function example(string $source): Example
    {
        $sourceLength = strlen($source);
        $lineBreaks = preg_match_all('/\r\n|\r|\n/', $source);
        self::assertNotFalse($lineBreaks);
        $lineCount = $lineBreaks + 1;
        if ($sourceLength > 0 && preg_match('/(?:\r\n|\r|\n)\z/', $source) === 1) {
            --$lineCount;
        }

        $firstCodeLine = 10;
        $lastCodeLine = $sourceLength === 0 ? null : $firstCodeLine + $lineCount - 1;
        $closingFenceLine = $lastCodeLine === null ? $firstCodeLine : $lastCodeLine + 1;

        return Example::fromInline(
            id: new ExampleId('example-separate-transform-01'),
            label: 'Separate-process transform fixture',
            document: new Document('docs/separate.md', $source),
            location: new SourceLocation(
                $firstCodeLine - 1,
                $firstCodeLine,
                $lastCodeLine,
                $closingFenceLine,
                new SourceSpan(0, max(1, $sourceLength)),
                new SourceSpan(0, $sourceLength),
            ),
            language: new Language('php'),
            code: new ExampleCode($source),
            fence: new FenceMetadata('php', '`', 3, 0),
            ordinal: 1,
        );
    }
}
