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

namespace jbboehr\Akashi\Tests\Metadata;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Metadata\ExampleMetadataParser;
use jbboehr\Akashi\Markdown\Exception\DirectiveException;
use jbboehr\Akashi\Markdown\Exception\DuplicateMarkerException;
use jbboehr\Akashi\Model\Directive;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExampleMetadataParserTest extends TestCase
{
    public function testParsesAndResolvesCombinedCanonicalMetadata(): void
    {
        $document = new Document('docs/example.md', "```php\nthrow new RuntimeException();\n```\n");
        $parser = new ExampleMetadataParser();
        $clauses = $parser->parse(
            $document,
            'example=conversion-basic, separate-process, expect-exception=RuntimeException, '
                . 'expect-exception-message="invalid, \\"quoted\\" input", expect-exception-code=73',
            4,
        );
        $metadata = $parser->resolve($document, $clauses);

        self::assertSame('conversion-basic', $metadata->markerId?->value);
        self::assertTrue($metadata->directives->contains(Directive::SeparateProcess));
        self::assertSame('RuntimeException', $metadata->expectedException?->className);
        self::assertSame('invalid, "quoted" input', $metadata->expectedException->message);
        self::assertSame(73, $metadata->expectedException->code);
        self::assertSame(4, $metadata->location->markerLine);
    }

    public function testPreservesLegacyMessagePayloadsContainingCanonicalPunctuation(): void
    {
        $document = new Document('docs/example.md', "```php\nthrow new RuntimeException();\n```\n");
        $parser = new ExampleMetadataParser();
        $clauses = [
            ...$parser->parse($document, 'expect-exception RuntimeException', 1),
            ...$parser->parse($document, 'expect-exception-message expected=value, still legacy', 2),
        ];
        $metadata = $parser->resolve($document, $clauses);

        self::assertSame('expected=value, still legacy', $metadata->expectedException?->message);
    }

    public function testParsesSpacedCanonicalExceptionAssignmentsBeforeLegacyForms(): void
    {
        $document = new Document('docs/example.md', "```php\nthrow new RuntimeException();\n```\n");
        $parser = new ExampleMetadataParser();
        $clauses = [
            ...$parser->parse($document, 'expect-exception = RuntimeException, skip', 1),
            ...$parser->parse($document, 'expect-exception-message = "hello world"', 2),
            ...$parser->parse($document, 'expect-exception-code = 73', 3),
        ];
        $metadata = $parser->resolve($document, $clauses);

        self::assertSame('RuntimeException', $metadata->expectedException?->className);
        self::assertSame('hello world', $metadata->expectedException->message);
        self::assertSame(73, $metadata->expectedException->code);
        self::assertTrue($metadata->directives->contains(Directive::Skip));
    }

    public function testKeepsACommaAfterAnEscapedQuoteInsideTheQuotedValue(): void
    {
        $document = new Document('docs/example.md', "```php\nthrow new RuntimeException();\n```\n");
        $parser = new ExampleMetadataParser();
        $payload = <<<'METADATA'
expect-exception=RuntimeException, expect-exception-message="before \", after", skip
METADATA;
        $metadata = $parser->resolve(
            $document,
            $parser->parse($document, $payload, 4),
        );

        self::assertSame('before ", after', $metadata->expectedException?->message);
        self::assertTrue($metadata->directives->contains(Directive::Skip));
    }

    public function testRejectsEveryDuplicatePropertyIncludingFlagsAndMarkers(): void
    {
        $document = new Document('docs/example.md', "```php\necho 1;\n```\n");
        $parser = new ExampleMetadataParser();

        try {
            $parser->resolve($document, $parser->parse($document, 'skip, skip', 1));
            self::fail('Duplicate flag was accepted.');
        } catch (DirectiveException $exception) {
            self::assertStringContainsString('Duplicate Akashi metadata property skip', $exception->getMessage());
        }

        $this->expectException(DuplicateMarkerException::class);
        $parser->resolve($document, [
            ...$parser->parse($document, 'example=first', 1),
            ...$parser->parse($document, 'example=second', 2),
        ]);
    }

    #[DataProvider('invalidCanonicalMetadataProvider')]
    public function testRejectsMalformedCanonicalMetadata(string $payload, string $message): void
    {
        $document = new Document('docs/example.md', "```php\necho 1;\n```\n");

        $this->expectException(DirectiveException::class);
        $this->expectExceptionMessage($message);

        (new ExampleMetadataParser())->parse($document, $payload, 7);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidCanonicalMetadataProvider(): iterable
    {
        yield 'empty' => ['', 'Metadata clauses must not be empty.'];
        yield 'empty middle clause' => ['skip,,compile-only', 'Metadata clauses must not be empty.'];
        yield 'trailing comma' => ['skip,', 'Metadata clauses must not be empty.'];
        yield 'leading comma' => [',skip', 'Metadata clauses must not be empty.'];
        yield 'missing keyed value' => ['example', 'Property example requires =VALUE.'];
        yield 'empty keyed value' => ['example=', 'Metadata values must not be empty.'];
        yield 'empty quoted value' => ['example=""', 'Metadata values must not be empty.'];
        yield 'value on flag' => ['skip=true', 'Flag skip does not accept a value.'];
        yield 'uppercase property' => ['Skip', 'Expected a lowercase kebab-case property name.'];
        yield 'unquoted whitespace' => [
            'example=two words',
            'Unquoted metadata values must be single tokens',
        ];
        yield 'single-quoted value' => [
            "example='value'",
            'Unquoted metadata values must be single tokens',
        ];
        yield 'unquoted equals sign' => [
            'example=one=two',
            'Unquoted metadata values must be single tokens',
        ];
        yield 'unknown property' => ['elsewhere=value', 'Unknown property "elsewhere".'];
        yield 'unterminated quote' => ['example="value', 'Quoted metadata value is not terminated.'];
        yield 'invalid JSON escape' => ['example="\\x"', 'Quoted metadata value is invalid.'];
        yield 'trailing text after quoted value' => ['example="value"suffix', 'Quoted metadata value is invalid.'];
    }

}
