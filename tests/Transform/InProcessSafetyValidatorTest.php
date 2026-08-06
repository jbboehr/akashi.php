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
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use jbboehr\Akashi\Transform\Exception\UnsupportedExampleException;
use jbboehr\Akashi\Transform\ExecutionScope;
use jbboehr\Akashi\Transform\InProcessTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InProcessSafetyValidatorTest extends TestCase
{
    #[DataProvider('unsupportedExampleProvider')]
    public function testRejectsUnsafeOrUnsupportedSource(string $source, string $reason, int $line): void
    {
        try {
            $this->transform($source);
            self::fail('Unsafe or unsupported source was unexpectedly transformed.');
        } catch (UnsupportedExampleException $exception) {
            self::assertStringContainsString(
                sprintf('example-safety-01 at docs/safety.md:%d', $line),
                $exception->getMessage(),
            );
            self::assertStringContainsString($reason, $exception->getMessage());
            self::assertStringContainsString('Use <!-- akashi: separate-process -->.', $exception->getMessage());
        }
    }

    /**
     * @return iterable<string, array{string, string, int}>
     */
    public static function unsupportedExampleProvider(): iterable
    {
        yield 'exit' => ['exit(1);', 'exit and die would terminate', 20];
        yield 'authored namespace' => ['namespace Example; echo 1;', 'authored namespace declarations', 20];
        yield 'halt compiler' => ['__halt_compiler();', '__halt_compiler() can terminate parsing', 20];
        yield 'global statement' => ['global $value;', 'global statements would leak variables', 20];
        yield 'block declare' => ['declare(ticks=1) { echo 1; }', 'block-form declare statements', 20];
        yield 'closing tag' => ['<?php echo 1; ?>', 'closing tags, additional PHP segments', 20];
        yield 'inline HTML' => ['<?php echo 1; ?>outside', 'closing tags, additional PHP segments', 20];
        foreach (['GLOBALS', '_COOKIE', '_ENV', '_FILES', '_GET', '_POST', '_REQUEST', '_SERVER', '_SESSION'] as $name) {
            yield 'write ' . $name => [
                sprintf("\$%s['value'] = 1;", $name),
                'writes through $GLOBALS or a superglobal',
                20,
            ];
        }

        yield 'superglobal assignment by reference' => [
            '$_GET =& $value;',
            'writes through $GLOBALS or a superglobal',
            20,
        ];
        yield 'reference alias from superglobal' => [
            '$reference =& $_GET;',
            'writes through $GLOBALS or a superglobal',
            20,
        ];
        yield 'reference alias from GLOBALS element' => [
            "\$reference =& \$GLOBALS['value'];",
            'writes through $GLOBALS or a superglobal',
            20,
        ];
        yield 'superglobal assignment operation' => [
            "\$_GET['value'] += 1;",
            'writes through $GLOBALS or a superglobal',
            20,
        ];
        yield 'superglobal pre-increment' => [
            "++\$_GET['value'];",
            'writes through $GLOBALS or a superglobal',
            20,
        ];
        yield 'superglobal post-increment' => [
            "\$_GET['value']++;",
            'writes through $GLOBALS or a superglobal',
            20,
        ];
        yield 'superglobal pre-decrement' => [
            "--\$_GET['value'];",
            'writes through $GLOBALS or a superglobal',
            20,
        ];
        yield 'superglobal post-decrement' => [
            "\$_GET['value']--;",
            'writes through $GLOBALS or a superglobal',
            20,
        ];
        yield 'superglobal unset' => [
            "unset(\$_SERVER['value']);",
            'writes through $GLOBALS or a superglobal',
            20,
        ];
        yield 'superglobal foreach value' => [
            'foreach ([1] as $_GET) {}',
            'writes through $GLOBALS or a superglobal',
            20,
        ];
        yield 'superglobal foreach key' => [
            'foreach ([1] as $_GET => $value) {}',
            'writes through $GLOBALS or a superglobal',
            20,
        ];
        yield 'by-reference iteration over superglobal' => [
            'foreach ($_GET as &$value) {}',
            'writes through $GLOBALS or a superglobal',
            20,
        ];
        yield 'superglobal destructuring' => [
            '[$_GET, $value] = [[], 1];',
            'writes through $GLOBALS or a superglobal',
            20,
        ];
        yield 'superglobal object property' => [
            "\$_GET['object']->value = 1;",
            'writes through $GLOBALS or a superglobal',
            20,
        ];

        foreach ([
            'class_alias',
            'date_default_timezone_set',
            'define',
            'ini_restore',
            'ini_set',
            'ob_end_clean',
            'ob_end_flush',
            'ob_get_clean',
            'ob_get_flush',
            'putenv',
            'register_shutdown_function',
            'restore_error_handler',
            'restore_exception_handler',
            'set_error_handler',
            'set_exception_handler',
            'setlocale',
            'spl_autoload_register',
            'spl_autoload_unregister',
        ] as $function) {
            yield 'persistent function ' . $function => [
                sprintf('%s();', $function),
                sprintf('global function %s() can mutate persistent process state', $function),
                20,
            ];
        }

        yield 'aliased error handler' => [
            "use function set_error_handler as install;\ninstall(static fn(): bool => true);",
            'global function set_error_handler() can mutate persistent process state',
            21,
        ];
        yield 'uppercase persistent function' => [
            'INI_SET();',
            'global function ini_set() can mutate persistent process state',
            20,
        ];
        yield 'persistent function after a dynamic call' => [
            "\$callable();\nini_set();",
            'global function ini_set() can mutate persistent process state',
            21,
        ];
        yield 'persistent function after a local call' => [
            "function localFunction(): void {}\nlocalFunction();\nini_set();",
            'global function ini_set() can mutate persistent process state',
            22,
        ];
        yield 'persistent function after nonliteral reflection' => [
            "class_exists(\$name);\nini_set();",
            'global function ini_set() can mutate persistent process state',
            21,
        ];

        foreach ([
            '__CLASS__' => 'Scalar_MagicConst_Class',
            '__DIR__' => 'Scalar_MagicConst_Dir',
            '__FILE__' => 'Scalar_MagicConst_File',
            '__FUNCTION__' => 'Scalar_MagicConst_Function',
            '__LINE__' => 'Scalar_MagicConst_Line',
            '__METHOD__' => 'Scalar_MagicConst_Method',
            '__TRAIT__' => 'Scalar_MagicConst_Trait',
        ] as $constant => $type) {
            yield 'relocation-sensitive ' . $constant => [
                sprintf('echo %s;', $constant),
                sprintf('%s changes meaning', $type),
                20,
            ];
        }

        foreach ([
            'class' => ["class LocalThing {}", "class_exists('LocalThing');"],
            'enum' => ["enum LocalThing {}", "enum_exists('LocalThing');"],
            'interface' => ["interface LocalThing {}", "interface_exists('LocalThing');"],
            'trait' => ["trait LocalThing {}", "trait_exists('LocalThing');"],
            'function' => ["function localThing(): void {}", "function_exists('localThing');"],
            'constant lookup' => ["const LOCAL_THING = 1;", "constant('LOCAL_THING');"],
            'defined check' => ["const LOCAL_THING = 1;", "defined('LOCAL_THING');"],
        ] as $kind => [$declaration, $reflection]) {
            $literal = str_contains($declaration, 'LOCAL_THING') ? 'LOCAL_THING' : 'LocalThing';
            if ($kind === 'function') {
                $literal = 'localThing';
            }

            yield 'string reflection of local ' . $kind => [
                $declaration . "\n" . $reflection,
                sprintf('string-based reflection of local declaration %s is ambiguous', $literal),
                21,
            ];
        }

        foreach ([
            'class' => ["class LocalThing {}", "class_exists(autoload: true, class: 'LocalThing');", 'LocalThing'],
            'enum' => ["enum LocalThing {}", "enum_exists(autoload: true, enum: 'LocalThing');", 'LocalThing'],
            'interface' => [
                "interface LocalThing {}",
                "interface_exists(autoload: true, interface: 'LocalThing');",
                'LocalThing',
            ],
            'trait' => ["trait LocalThing {}", "trait_exists(autoload: true, trait: 'LocalThing');", 'LocalThing'],
            'function' => [
                "function localThing(): void {}",
                "function_exists(function: 'localThing');",
                'localThing',
            ],
            'constant lookup' => [
                "const LOCAL_THING = 1;",
                "constant(name: 'LOCAL_THING');",
                'LOCAL_THING',
            ],
            'defined check' => [
                "const LOCAL_THING = 1;",
                "defined(constant_name: 'LOCAL_THING');",
                'LOCAL_THING',
            ],
        ] as $kind => [$declaration, $reflection, $literal]) {
            yield 'named string reflection of local ' . $kind => [
                $declaration . "\n" . $reflection,
                sprintf('string-based reflection of local declaration %s is ambiguous', $literal),
                21,
            ];
        }
        yield 'duplicate local class' => [
            "if (true) { class LocalThing {} }\nif (false) { class LocalThing {} }",
            'duplicate local class-like declaration localthing',
            21,
        ];
        yield 'duplicate local function' => [
            "if (true) { function localFunction(): void {} }\nif (false) { function localFunction(): void {} }",
            'duplicate local function declaration localfunction',
            21,
        ];
        yield 'duplicate local constant' => [
            "const LOCAL_VALUE = 1;\nconst LOCAL_VALUE = 2;",
            'duplicate local constant declaration LOCAL_VALUE',
            21,
        ];
        yield 'qualified string reflection of local class' => [
            "class LocalThing {}\nclass_exists('\\\\LocalThing');",
            'string-based reflection of local declaration LocalThing is ambiguous',
            21,
        ];
    }

    public function testAllowsSuperglobalReadsAndExternalReflection(): void
    {
        $prepared = $this->transform(<<<'PHP'
echo $_GET['value'] ?? '';
foreach ($_GET as $value) {}
class_exists('DateTimeImmutable');
class_exists(autoload: true, class: 'DateTimeImmutable');
class_exists(...);
PHP);

        self::assertStringContainsString("echo \$_GET['value'] ?? '';", $prepared->code->source);
        self::assertStringContainsString('foreach ($_GET as $value) {}', $prepared->code->source);
        self::assertStringContainsString("\\class_exists('DateTimeImmutable');", $prepared->code->source);
        self::assertStringContainsString(
            "\\class_exists(autoload: \\true, class: 'DateTimeImmutable');",
            $prepared->code->source,
        );
        self::assertStringContainsString('\\class_exists(...);', $prepared->code->source);
    }

    public function testAllowsReturnAtExampleTopLevel(): void
    {
        $prepared = $this->transform('return 42;');

        self::assertStringEndsWith("return 42;", $prepared->code->source);
    }

    public function testAllowsAnonymousDeclarationsDynamicCallsAndOrdinaryWrites(): void
    {
        $prepared = $this->transform(<<<'PHP'
$object = new class {};
$callable = 'strlen';
$length = $callable('safe');
$source = 1;
$reference =& $source;
foreach ([1] as $key => $value) {}
[$first, , $third] = [1, 2, 3];
PHP);

        self::assertStringContainsString('$object = new class {};', $prepared->code->source);
        self::assertStringContainsString('$callable = \'strlen\';', $prepared->code->source);
        self::assertStringContainsString('$length = $callable(\'safe\');', $prepared->code->source);
        self::assertStringContainsString('$reference =& $source;', $prepared->code->source);
        self::assertStringContainsString('foreach ([1] as $key => $value) {}', $prepared->code->source);
        self::assertStringContainsString('[$first, , $third] = [1, 2, 3];', $prepared->code->source);
    }

    private function transform(string $source): \jbboehr\Akashi\Transform\PreparedExample
    {
        return (new InProcessTransformer())->transform(
            $this->example($source),
            new ExecutionScope('Akashi\\Generated\\Example_safety_0123456789abcdef'),
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

        $firstCodeLine = 20;
        $lastCodeLine = $sourceLength === 0 ? null : $firstCodeLine + $lineCount - 1;
        $closingFenceLine = $lastCodeLine === null ? $firstCodeLine : $lastCodeLine + 1;

        return new Example(
            id: new ExampleId('example-safety-01'),
            label: 'Safety fixture',
            document: new Document('docs/safety.md', $source),
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
