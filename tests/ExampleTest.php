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

namespace jbboehr\Akashi\Tests;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Example;
use jbboehr\Akashi\Model\Directive;
use jbboehr\Akashi\Model\DirectiveSet;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\MarkerId;
use jbboehr\Akashi\Model\SourceLocation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExampleTest extends TestCase
{
    public function testPreservesSourceMetadataAndNormalizesLanguage(): void
    {
        $document = new Document('docs/guide.md', "```php\r\necho 1;\r\n```\r\n");
        $source = "<?php\r\n\r\necho 1;\r\n";
        $id = new ExampleId('example-a1b2c3-01');
        $location = new SourceLocation(1, 2, 2, 3);
        $language = new Language(' PHP ');
        $code = new ExampleCode($source);
        $markerId = new MarkerId('selected-example');
        $directives = new DirectiveSet(Directive::SeparateProcess);
        $example = new Example(
            id: $id,
            label: 'docs/guide.md PHP example 1',
            document: $document,
            location: $location,
            language: $language,
            code: $code,
            ordinal: 1,
            explicitMarkerId: $markerId,
            directives: $directives,
        );

        self::assertSame($id, $example->id);
        self::assertSame('docs/guide.md PHP example 1', $example->label);
        self::assertSame($document, $example->document);
        self::assertSame($location, $example->location);
        self::assertSame($language, $example->language);
        self::assertSame('php', $example->language->value);
        self::assertSame($code, $example->code);
        self::assertSame($source, $example->code->source);
        self::assertSame(1, $example->ordinal);
        self::assertSame($markerId, $example->explicitMarkerId);
        self::assertSame($directives, $example->directives);
        self::assertTrue($example->directives->contains(Directive::SeparateProcess));
    }

    public function testAllowsAnExampleWithoutAnExplicitMarker(): void
    {
        $example = $this->example();

        self::assertNull($example->explicitMarkerId);
        self::assertFalse($example->directives->contains(Directive::SeparateProcess));
    }

    #[DataProvider('invalidExampleProvider')]
    public function testRejectsInvalidMetadata(string $label, int $ordinal, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new Example(
            id: new ExampleId('example-01'),
            label: $label,
            document: new Document('docs/guide.md', ''),
            location: new SourceLocation(1, 2, null, 2),
            language: new Language('php'),
            code: new ExampleCode("echo 1;\n"),
            ordinal: $ordinal,
        );
    }

    /**
     * @return iterable<string, array{string, int, string}>
     */
    public static function invalidExampleProvider(): iterable
    {
        yield 'empty label' => ['  ', 1, 'Example label must not be empty.'];
        yield 'zero ordinal' => ['Example', 0, 'Example ordinal must be positive.'];
    }

    private function example(): Example
    {
        return new Example(
            id: new ExampleId('example-guide-01'),
            label: 'docs/guide.md PHP example 1',
            document: new Document('docs/guide.md', ''),
            location: new SourceLocation(1, 2, null, 2),
            language: new Language('php'),
            code: new ExampleCode("echo 1;\n"),
            ordinal: 1,
        );
    }
}
