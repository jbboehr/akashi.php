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
use jbboehr\Akashi\Integration\PhpUnit\NativeAssertion;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use jbboehr\Akashi\Transform\Exception\UnsupportedExampleException;
use jbboehr\Akashi\Transform\ExecutionScope;
use jbboehr\Akashi\Transform\InProcessTransformer;
use PhpParser\ErrorHandler\Throwing;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NativeAssertionRewriteTest extends TestCase
{
    private const NAMESPACE = 'Akashi\\Generated\\Assertion_fixture_0123456789abcdef';

    public function testRewritesAnAssertionWithoutChangingItsLinesOrAuthoredExpression(): void
    {
        $source = <<<'PHP'
$value = "x";
assert(
    strlen($value) === 1,
    'expected one byte',
);
PHP;
        $expected = <<<'PHP'
<?php
namespace Akashi\Generated\Assertion_fixture_0123456789abcdef;
$value = "x";
\jbboehr\Akashi\Integration\PhpUnit\NativeAssertion::evaluate(
    \strlen($value) === 1,
    'expected one byte', expression: "strlen(\$value) === 1", sourcePath: "docs/assertions.md", sourceLine: 12,
);
PHP;

        $prepared = $this->transform($source);

        self::assertSame($expected, $prepared->code->source);
        self::assertSame(7, $prepared->sourceMap->generatedLineCount());
        self::assertSame(12, $prepared->sourceMap->sourceLineFor(5));
        self::assertParses($prepared->code->source);
    }

    public function testSuppliesANullDescriptionForTheOneArgumentForm(): void
    {
        $prepared = $this->transform('assert($value === 1);');

        self::assertStringContainsString(
            '\\' . NativeAssertion::class
                . '::evaluate($value === 1, description: null, expression: "\\$value === 1", '
                . 'sourcePath: "docs/assertions.md", sourceLine: 10)',
            $prepared->code->source,
        );
        self::assertParses($prepared->code->source);
    }

    public function testPreservesNamedArgumentEvaluationOrder(): void
    {
        $prepared = $this->transform(
            'assert(description: description(), assertion: condition());',
        );
        $source = $prepared->code->source;
        $description = strpos($source, 'description: \\description()');
        $assertion = strpos($source, 'assertion: \\condition()');
        self::assertIsInt($description);
        self::assertIsInt($assertion);

        self::assertLessThan($assertion, $description);
        self::assertStringContainsString('expression: "condition()"', $source);
        self::assertParses($source);
    }

    public function testRewritesNestedAndCaseInsensitiveNativeAssertions(): void
    {
        $prepared = $this->transform('ASSERT(assert(true));');

        self::assertSame(2, substr_count($prepared->code->source, NativeAssertion::class . '::evaluate'));
        self::assertParses($prepared->code->source);
    }

    public function testDoesNotRewriteAnImportedNonNativeAssertionFunction(): void
    {
        $prepared = $this->transform("use function Vendor\\assert;\nassert(true);");

        self::assertStringNotContainsString(NativeAssertion::class, $prepared->code->source);
        self::assertStringContainsString('\\Vendor\\assert(true);', $prepared->code->source);
        self::assertParses($prepared->code->source);
    }

    public function testContinuesPastDynamicAndImportedCallsToRewriteALaterNativeAssertion(): void
    {
        $source = <<<'PHP'
use function Vendor\assert as vendor_assert;
$callable = 'strlen';
$callable('value');
vendor_assert(true);
assert(true);
PHP;
        $prepared = $this->transform($source);

        self::assertSame(1, substr_count($prepared->code->source, NativeAssertion::class . '::evaluate'));
        self::assertStringContainsString('$callable(\'value\');', $prepared->code->source);
        self::assertStringContainsString('\\Vendor\\assert(true);', $prepared->code->source);
        self::assertParses($prepared->code->source);
    }

    public function testMapsAnAssertionOnTheFinalGeneratedLine(): void
    {
        $prepared = $this->transform("echo 1;\nassert(true);");

        self::assertStringContainsString('sourceLine: 11', $prepared->code->source);
        self::assertParses($prepared->code->source);
    }

    public function testEscapesExpressionMetadataWithoutAddingGeneratedLines(): void
    {
        $source = <<<'PHP'
assert(
    $value === "line\n{$other}"
);
PHP;
        $prepared = $this->transform($source);

        self::assertStringContainsString(
            'expression: "\\$value === \\"line\\\\n{\\$other}\\""',
            $prepared->code->source,
        );
        self::assertSame(5, $prepared->sourceMap->generatedLineCount());
        self::assertParses($prepared->code->source);
    }

    #[DataProvider('expressionByteProvider')]
    public function testPreservesEveryExpressionByteInMetadata(string $expression): void
    {
        $prepared = $this->transform('assert(' . $expression . ');');

        self::assertSame($expression, $this->rewrittenExpression($prepared->code->source));
        self::assertParses($prepared->code->source);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function expressionByteProvider(): iterable
    {
        yield 'line feed' => ["true\n    && false"];
        yield 'carriage return and line feed' => ["true\r\n    && false"];
        yield 'horizontal tab' => ["true\t&& false"];
        yield 'form feed in a string' => ["\"left\fright\" === \$value"];
        yield 'delete byte in a string' => ["\"left\x7Fright\" === \$value"];
    }

    #[DataProvider('invalidAssertionProvider')]
    public function testRejectsInvalidNativeAssertionForms(string $source, string $reason): void
    {
        $this->expectException(UnsupportedExampleException::class);
        $this->expectExceptionMessage('example-assertion-01 at docs/assertions.md:11');
        $this->expectExceptionMessage($reason);

        $this->transform("echo 1;\n" . $source);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidAssertionProvider(): iterable
    {
        yield 'missing assertion' => ['assert();', 'requires an assertion argument'];
        yield 'description only' => ["assert(description: 'failure');", 'requires an assertion argument'];
        yield 'too many' => ['assert(true, null, false);', 'accepts at most two arguments'];
        yield 'unknown named argument' => ['assert(value: true);', 'has no argument named value'];
        yield 'duplicate assertion' => ['assert(true, assertion: false);', 'received assertion more than once'];
        yield 'unpacked argument' => ['assert(...[true]);', 'does not support argument unpacking'];
        yield 'first-class callable' => ['assert(...);', 'first-class callable syntax is not valid'];
    }

    private function transform(string $source): \jbboehr\Akashi\Transform\PreparedExample
    {
        return (new InProcessTransformer())->transform(
            $this->example($source),
            new ExecutionScope(self::NAMESPACE),
        );
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

        return new Example(
            id: new ExampleId('example-assertion-01'),
            label: 'Native assertion fixture',
            document: new Document('docs/assertions.md', $source),
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

    private static function assertParses(string $source): void
    {
        $parser = (new ParserFactory())->createForHostVersion();
        $statements = $parser->parse($source, new Throwing());

        self::assertNotNull($statements);
    }

    private function rewrittenExpression(string $source): string
    {
        $parser = (new ParserFactory())->createForHostVersion();
        $statements = $parser->parse($source, new Throwing());
        self::assertNotNull($statements);
        $call = (new NodeFinder())->findFirst($statements, static function (Node $node): bool {
            return $node instanceof StaticCall
                && $node->class instanceof Name
                && $node->class->toString() === NativeAssertion::class;
        });
        self::assertInstanceOf(StaticCall::class, $call);

        foreach ($call->args as $argument) {
            if (
                $argument instanceof Arg
                && $argument->name?->toString() === 'expression'
                && $argument->value instanceof String_
            ) {
                return $argument->value->value;
            }
        }

        self::fail('The rewritten assertion has no expression metadata.');
    }
}
