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
use jbboehr\Akashi\Transform\ExecutionScope;
use jbboehr\Akashi\Transform\InProcessTransformer;
use PhpParser\ErrorHandler\Throwing;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InProcessTransformerTest extends TestCase
{
    private const NAMESPACE = 'Akashi\\Generated\\Example_fixture_0123456789abcdef';

    public function testIsolatesDeclarationsAndPreservesGlobalResolution(): void
    {
        $source = <<<'PHP'
use DateTimeImmutable as Clock;
use function strlen as length;

class LocalThing {}
function localFunction(): void {}
const LOCAL_VALUE = 1;

new Clock();
new LocalThing();
localFunction();
length('x');
echo LOCAL_VALUE;
echo __NAMESPACE__;
PHP;
        $expected = <<<'PHP'
<?php
namespace Akashi\Generated\Example_fixture_0123456789abcdef;
use DateTimeImmutable as Clock;
use function strlen as length;

class LocalThing {}
function localFunction(): void {}
const LOCAL_VALUE = 1;

new \DateTimeImmutable();
new \Akashi\Generated\Example_fixture_0123456789abcdef\LocalThing();
\Akashi\Generated\Example_fixture_0123456789abcdef\localFunction();
\strlen('x');
echo \Akashi\Generated\Example_fixture_0123456789abcdef\LOCAL_VALUE;
echo '';
PHP;

        $prepared = $this->transform($source);

        self::assertSame($expected, $prepared->code->source);
        self::assertSame(ExecutionMode::InProcess, $prepared->executionMode);
        self::assertSame(self::NAMESPACE, $prepared->scope->namespace);
        self::assertNull($prepared->sourceMap->sourceLineFor(1));
        self::assertNull($prepared->sourceMap->sourceLineFor(2));
        self::assertSame(10, $prepared->sourceMap->sourceLineFor(3));
        self::assertSame(22, $prepared->sourceMap->sourceLineFor(15));
        self::assertParses($prepared->code->source);
    }

    public function testSplitsAnAuthoredOpeningTagWithoutLosingItsLocation(): void
    {
        $prepared = $this->transform('<?php echo __NAMESPACE__, DateTimeImmutable::class;');

        self::assertSame(
            "<?php \nnamespace " . self::NAMESPACE . ";\necho '', \\DateTimeImmutable::class;",
            $prepared->code->source,
        );
        self::assertSame(10, $prepared->sourceMap->sourceLineFor(1));
        self::assertNull($prepared->sourceMap->sourceLineFor(2));
        self::assertSame(10, $prepared->sourceMap->sourceLineFor(3));
        self::assertParses($prepared->code->source);
    }

    #[DataProvider('caseInsensitiveOpeningTagProvider')]
    public function testAcceptsCaseInsensitiveOpeningTags(string $openingTag): void
    {
        $prepared = $this->transform($openingTag . ' echo 1;');

        self::assertSame(
            $openingTag . " \nnamespace " . self::NAMESPACE . ";\necho 1;",
            $prepared->code->source,
        );
        self::assertSame(10, $prepared->sourceMap->sourceLineFor(1));
        self::assertNull($prepared->sourceMap->sourceLineFor(2));
        self::assertSame(10, $prepared->sourceMap->sourceLineFor(3));
        self::assertParses($prepared->code->source);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function caseInsensitiveOpeningTagProvider(): iterable
    {
        yield 'uppercase' => ['<?PHP'];
        yield 'mixed case' => ['<?Php'];
    }

    public function testPlacesTheNamespaceAfterLeadingDeclareStatements(): void
    {
        $source = "<?php\r\ndeclare(strict_types=1);\r\necho strlen('x');\r\n";
        $prepared = $this->transform($source);

        self::assertSame(
            "<?php\r\ndeclare(strict_types=1);\r\nnamespace " . self::NAMESPACE . ";\necho \\strlen('x');\r\n",
            $prepared->code->source,
        );
        self::assertSame(10, $prepared->sourceMap->sourceLineFor(1));
        self::assertSame(11, $prepared->sourceMap->sourceLineFor(2));
        self::assertNull($prepared->sourceMap->sourceLineFor(3));
        self::assertSame(12, $prepared->sourceMap->sourceLineFor(4));
        self::assertSame(12, $prepared->sourceMap->sourceLineFor(5));
        self::assertParses($prepared->code->source);
    }

    public function testPreservesSpecialClassNamesAndRewritesLocalParents(): void
    {
        $source = <<<'PHP'
class ParentType {}
class ChildType extends ParentType
{
    public static function make(): self
    {
        return new self();
    }
}
return ChildType::make();
PHP;
        $prepared = $this->transform($source);

        self::assertStringContainsString(
            'class ChildType extends \\' . self::NAMESPACE . '\\ParentType',
            $prepared->code->source,
        );
        self::assertStringContainsString('public static function make(): self', $prepared->code->source);
        self::assertStringContainsString('return new self();', $prepared->code->source);
        self::assertStringContainsString('return \\' . self::NAMESPACE . '\\ChildType::make();', $prepared->code->source);
        self::assertParses($prepared->code->source);
    }

    public function testKeepsClassFunctionAndConstantNameResolutionDistinct(): void
    {
        $source = <<<'PHP'
class SharedName {}
function SharedName(): void {}
const SharedName = 1;

new SharedName();
SharedName();
echo SharedName;
PHP;
        $prepared = $this->transform($source);

        self::assertStringContainsString('new \\' . self::NAMESPACE . '\\SharedName();', $prepared->code->source);
        self::assertStringContainsString('\\' . self::NAMESPACE . '\\SharedName();', $prepared->code->source);
        self::assertStringContainsString('echo \\' . self::NAMESPACE . '\\SharedName;', $prepared->code->source);
        self::assertParses($prepared->code->source);
    }

    public function testPreservesPhpLanguageConstants(): void
    {
        $prepared = $this->transform('return [TRUE, False, NuLl];');

        self::assertStringContainsString('return [TRUE, False, NuLl];', $prepared->code->source);
        self::assertParses($prepared->code->source);
    }

    public function testResolvesRelativeNamesFromTheAuthoredGlobalNamespace(): void
    {
        $source = <<<'PHP'
class LocalThing {}
new namespace\LocalThing();
new namespace\ExternalThing();
PHP;
        $prepared = $this->transform($source);

        self::assertStringContainsString(
            'new \\' . self::NAMESPACE . '\\LocalThing();',
            $prepared->code->source,
        );
        self::assertStringContainsString('new \\ExternalThing();', $prepared->code->source);
        self::assertParses($prepared->code->source);
    }

    public function testSupportsEveryNamedClassLikeDeclaration(): void
    {
        $source = <<<'PHP'
interface LocalInterface {}
trait LocalTrait {}
enum LocalEnum { case Value; }
class LocalClass implements LocalInterface { use LocalTrait; }
new LocalClass();
LocalEnum::Value;
PHP;
        $prepared = $this->transform($source);

        self::assertStringContainsString('implements \\' . self::NAMESPACE . '\\LocalInterface', $prepared->code->source);
        self::assertStringContainsString('use \\' . self::NAMESPACE . '\\LocalTrait;', $prepared->code->source);
        self::assertStringContainsString('new \\' . self::NAMESPACE . '\\LocalClass();', $prepared->code->source);
        self::assertStringContainsString('\\' . self::NAMESPACE . '\\LocalEnum::Value;', $prepared->code->source);
        self::assertParses($prepared->code->source);
    }

    public function testPlacesTheNamespaceAfterAnInlineDeclareStatement(): void
    {
        $prepared = $this->transform("<?php declare(strict_types=1); echo strlen('x');");

        self::assertSame(
            "<?php declare(strict_types=1);\nnamespace " . self::NAMESPACE . ";\n echo \\strlen('x');",
            $prepared->code->source,
        );
        self::assertSame(10, $prepared->sourceMap->sourceLineFor(1));
        self::assertNull($prepared->sourceMap->sourceLineFor(2));
        self::assertSame(10, $prepared->sourceMap->sourceLineFor(3));
        self::assertParses($prepared->code->source);
    }

    public function testPlacesTheNamespaceAfterADeclareOnALaterSourceLine(): void
    {
        $prepared = $this->transform("<?php\ndeclare(ticks=1); echo strlen('x');");

        self::assertSame(
            "<?php\ndeclare(ticks=1);\nnamespace " . self::NAMESPACE . ";\n echo \\strlen('x');",
            $prepared->code->source,
        );
        self::assertSame(10, $prepared->sourceMap->sourceLineFor(1));
        self::assertSame(11, $prepared->sourceMap->sourceLineFor(2));
        self::assertNull($prepared->sourceMap->sourceLineFor(3));
        self::assertSame(11, $prepared->sourceMap->sourceLineFor(4));
        self::assertParses($prepared->code->source);
    }

    public function testConsumesHorizontalWhitespaceBeforeTheOpeningTagNewline(): void
    {
        $prepared = $this->transform("<?php  \necho A();");

        self::assertSame(
            "<?php  \nnamespace " . self::NAMESPACE . ";\necho \\A();",
            $prepared->code->source,
        );
        self::assertParses($prepared->code->source);
    }

    public function testDoesNotMoveTheNamespacePastANonleadingDeclare(): void
    {
        $prepared = $this->transform("<?php\necho 1;\ndeclare(ticks=1);\necho 2;");

        self::assertSame(
            "<?php\nnamespace " . self::NAMESPACE . ";\necho 1;\ndeclare(ticks=1);\necho 2;",
            $prepared->code->source,
        );
        self::assertParses($prepared->code->source);
    }

    public function testTransformsAnEmptyExampleWithoutInventingASourceLocation(): void
    {
        $prepared = $this->transform('');

        self::assertSame("<?php\nnamespace " . self::NAMESPACE . ";\n", $prepared->code->source);
        self::assertNull($prepared->sourceMap->sourceLineFor(1));
        self::assertNull($prepared->sourceMap->sourceLineFor(2));
        self::assertNull($prepared->sourceMap->sourceLineFor(3));
        self::assertParses($prepared->code->source);
    }

    public function testCreatesANewSecureScopeForEachDefaultTransformation(): void
    {
        $transformer = new InProcessTransformer();
        $example = $this->example('echo 1;');

        $first = $transformer->transform($example);
        $second = $transformer->transform($example);

        self::assertNotSame($first->scope->namespace, $second->scope->namespace);
        self::assertStringStartsWith(
            'jbboehr\\Akashi\\Generated\\Example_example_fixture_01_',
            $first->scope->namespace,
        );
    }

    public function testReportsParseFailuresAtTheMaintainedMarkdownLine(): void
    {
        $this->expectException(PhpParseException::class);
        $this->expectExceptionMessage('example-fixture-01 at docs/example.md:10');

        $this->transform('if (');
    }

    public function testReportsNameResolutionFailuresAtTheMaintainedMarkdownLine(): void
    {
        $this->expectException(PhpParseException::class);
        $this->expectExceptionMessage('example-fixture-01 at docs/example.md:11');

        $this->transform("use Alpha as Duplicate;\nuse Beta as Duplicate;\n");
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
            id: new ExampleId('example-fixture-01'),
            label: 'Transform fixture',
            document: new Document('docs/example.md', $source),
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
}
