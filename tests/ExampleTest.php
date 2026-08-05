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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExampleTest extends TestCase
{
    public function testPreservesSourceMetadataAndNormalizesLanguage(): void
    {
        $document = new Document('docs/guide.md', "```php\r\necho 1;\r\n```\r\n");
        $source = "<?php\r\n\r\necho 1;\r\n";
        $example = new Example(
            id: 'example-a1b2c3-01',
            label: 'docs/guide.md PHP example 1',
            document: $document,
            startLine: 2,
            endLine: 4,
            language: ' PHP ',
            source: $source,
            ordinal: 1,
            explicitMarkerId: 'selected-example',
        );

        self::assertSame('example-a1b2c3-01', $example->id);
        self::assertSame('docs/guide.md PHP example 1', $example->label);
        self::assertSame($document, $example->document);
        self::assertSame(2, $example->startLine);
        self::assertSame(4, $example->endLine);
        self::assertSame('php', $example->language);
        self::assertSame($source, $example->source);
        self::assertSame(1, $example->ordinal);
        self::assertSame('selected-example', $example->explicitMarkerId);
    }

    public function testAllowsAnExampleWithoutAnExplicitMarker(): void
    {
        $example = $this->example();

        self::assertSame(1, $example->startLine);
        self::assertSame(1, $example->endLine);
        self::assertNull($example->explicitMarkerId);
    }

    #[DataProvider('invalidExampleProvider')]
    public function testRejectsInvalidMetadata(
        string $id,
        string $label,
        int $startLine,
        int $endLine,
        string $language,
        int $ordinal,
        ?string $explicitMarkerId,
        string $message,
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new Example(
            id: $id,
            label: $label,
            document: new Document('docs/guide.md', ''),
            startLine: $startLine,
            endLine: $endLine,
            language: $language,
            source: "echo 1;\n",
            ordinal: $ordinal,
            explicitMarkerId: $explicitMarkerId,
        );
    }

    /**
     * @return iterable<string, array{string, string, int, int, string, int, ?string, string}>
     */
    public static function invalidExampleProvider(): iterable
    {
        yield 'empty ID' => ['', 'Example', 3, 4, 'php', 1, null, 'Example ID must be a lowercase file-safe identifier.'];
        yield 'uppercase ID' => ['Example-01', 'Example', 3, 4, 'php', 1, null, 'Example ID must be a lowercase file-safe identifier.'];
        yield 'ID suffix' => ['example-01!', 'Example', 3, 4, 'php', 1, null, 'Example ID must be a lowercase file-safe identifier.'];
        yield 'empty label' => ['example-01', '  ', 3, 4, 'php', 1, null, 'Example label must not be empty.'];
        yield 'zero start line' => ['example-01', 'Example', 0, 4, 'php', 1, null, 'Example start line must be positive.'];
        yield 'reversed lines' => ['example-01', 'Example', 5, 4, 'php', 1, null, 'Example end line must not precede its start line.'];
        yield 'empty language' => ['example-01', 'Example', 3, 4, ' ', 1, null, 'Example language must be a nonempty language identifier.'];
        yield 'invalid language' => ['example-01', 'Example', 3, 4, 'php script', 1, null, 'Example language must be a nonempty language identifier.'];
        yield 'zero ordinal' => ['example-01', 'Example', 3, 4, 'php', 0, null, 'Example ordinal must be positive.'];
        yield 'invalid marker' => ['example-01', 'Example', 3, 4, 'php', 1, 'Selected Example', 'Explicit marker ID must use lowercase kebab-case.'];
        yield 'marker suffix' => ['example-01', 'Example', 3, 4, 'php', 1, 'selected-example!', 'Explicit marker ID must use lowercase kebab-case.'];
    }

    private function example(): Example
    {
        return new Example(
            id: 'example-guide-01',
            label: 'docs/guide.md PHP example 1',
            document: new Document('docs/guide.md', ''),
            startLine: 1,
            endLine: 1,
            language: 'php',
            source: "echo 1;\n",
            ordinal: 1,
        );
    }
}
