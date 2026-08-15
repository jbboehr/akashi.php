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

namespace jbboehr\Akashi\Tests\Model;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Model\CodeOrigin;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\InlineExampleSource;
use jbboehr\Akashi\Model\MetadataLocation;
use jbboehr\Akashi\Model\PhpDocTagName;
use jbboehr\Akashi\Model\ReferenceLocation;
use jbboehr\Akashi\Model\ReferencedExampleSource;
use jbboehr\Akashi\Model\RegionName;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExampleSourceTest extends TestCase
{
    public function testBuildsAnInlineSourceFromOneFenceLocation(): void
    {
        $document = new Document('docs/example.md', "```php\necho 1;\n```\n");
        $metadata = new MetadataLocation(skipDirectiveLine: 2, compileOnlyDirectiveLine: 2);
        $location = new SourceLocation(
            1,
            2,
            2,
            3,
            new SourceSpan(0, 19),
            new SourceSpan(7, 15),
            $metadata,
        );
        $source = InlineExampleSource::fromFence($document, $location, new FenceMetadata('php', '`', 3, 0));

        self::assertSame($document, $source->origin->document);
        self::assertSame(2, $source->origin->firstCodeLine);
        self::assertSame($location->codeSpan, $source->origin->codeSpan);
        self::assertSame($metadata, $source->origin->metadata);
    }

    public function testRejectsAnInlineSourceWhoseOriginDisagreesWithItsFence(): void
    {
        $document = new Document('docs/example.md', "```php\necho 1;\n```\n");
        $location = new SourceLocation(1, 2, 2, 3, new SourceSpan(0, 19), new SourceSpan(7, 15));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Inline source origin must match its fenced source location.');

        new InlineExampleSource(
            new CodeOrigin($document, 3, 3, new SourceSpan(15, 19)),
            $location,
            new FenceMetadata('php', '`', 3, 0),
        );
    }

    public function testAcceptsValueEqualInlineMetadataObjects(): void
    {
        $document = new Document('docs/example.md', "```php\necho 1;\n```\n");
        $location = new SourceLocation(
            1,
            2,
            2,
            3,
            new SourceSpan(0, 19),
            new SourceSpan(7, 15),
            new MetadataLocation(skipDirectiveLine: 2, compileOnlyDirectiveLine: 2),
        );

        $source = new InlineExampleSource(
            new CodeOrigin(
                $document,
                2,
                2,
                new SourceSpan(7, 15),
                new MetadataLocation(skipDirectiveLine: 2, compileOnlyDirectiveLine: 2),
            ),
            $location,
            new FenceMetadata('php', '`', 3, 0),
        );

        self::assertNotSame($source->origin->metadata, $source->location->metadata);
        self::assertSame(2, $source->origin->metadata->skipDirectiveLine);
        self::assertSame(2, $source->origin->metadata->compileOnlyDirectiveLine);
    }

    #[DataProvider('invalidOriginProvider')]
    public function testRejectsInvalidCodeOrigins(int $first, ?int $last, SourceSpan $span, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new CodeOrigin(new Document('examples/example.php', "<?php\n"), $first, $last, $span);
    }

    /** @return iterable<string, array{int, ?int, SourceSpan, string}> */
    public static function invalidOriginProvider(): iterable
    {
        yield 'nonpositive first line' => [0, null, new SourceSpan(0, 0), 'First code line must be positive.'];
        yield 'reversed lines' => [2, 1, new SourceSpan(0, 1), 'Last code line must not precede'];
        yield 'nonempty empty-source span' => [1, null, new SourceSpan(0, 1), 'empty code origin must have an empty'];
    }

    public function testReferencedSourcesRequireOrderedUniquePresentationLocations(): void
    {
        $canonical = new Document('examples/example.php', "<?php\necho 1;\n");
        $origin = new CodeOrigin($canonical, 1, 2, new SourceSpan(0, 14));
        $docA = new Document('src/A.php', "<?php\n/** @akashi-example examples/example.php */\n");
        $docB = new Document('src/B.php', "<?php\n/** @akashi-example examples/example.php */\n");
        $tag = new PhpDocTagName('akashi-example');
        $first = new ReferenceLocation($docA, $tag, 2, new SourceSpan(6, 50));
        $second = new ReferenceLocation($docB, $tag, 2, new SourceSpan(6, 50));

        $source = new ReferencedExampleSource($origin, new RegionName('basic'), [$first, $second]);
        self::assertSame([$first, $second], $source->references);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be unique and ordered');
        new ReferencedExampleSource($origin, null, [$second, $first]);
    }

    public function testReferencedSourcesRequireNonemptyCanonicalCode(): void
    {
        $document = new Document('src/Example.php', "<?php\n/** @akashi-example examples/example.php */\n");
        $reference = new ReferenceLocation(
            $document,
            new PhpDocTagName('akashi-example'),
            2,
            new SourceSpan(6, 50),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Referenced source origin must contain PHP code.');

        new ReferencedExampleSource(
            new CodeOrigin(new Document('examples/example.php', ''), 1, null, new SourceSpan(0, 0)),
            null,
            [$reference],
        );
    }

    public function testReferencedSourceDirectivesMustLieWithinCanonicalCode(): void
    {
        $document = new Document('src/Example.php', "<?php\n/** @akashi-example examples/example.php */\n");
        $reference = new ReferenceLocation(
            $document,
            new PhpDocTagName('akashi-example'),
            2,
            new SourceSpan(6, 50),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Referenced source directives must lie within the canonical PHP code.');

        new ReferencedExampleSource(
            new CodeOrigin(
                new Document('examples/example.php', "<?php\necho 1;\n"),
                2,
                2,
                new SourceSpan(6, 14),
                new MetadataLocation(skipDirectiveLine: 1),
            ),
            null,
            [$reference],
        );
    }

    public function testReferenceLocationsMustCoverTheirExactAuthoredLine(): void
    {
        $document = new Document('src/Example.php', "<?php\n/** @akashi-example examples/example.php */\n");

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Reference span must cover exactly its authored source line.');

        new ReferenceLocation(
            $document,
            new PhpDocTagName('akashi-example'),
            2,
            new SourceSpan(6, 53),
        );
    }

    #[DataProvider('invalidNameProvider')]
    public function testRejectsInvalidReferenceAndRegionNames(string $kind, string $value, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $kind === 'tag' ? new PhpDocTagName($value) : new RegionName($value);
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function invalidNameProvider(): iterable
    {
        yield 'tag with at-sign' => ['tag', '@akashi-example', 'without a leading at-sign'];
        yield 'uppercase tag' => ['tag', 'Akashi-example', 'lowercase kebab-case'];
        yield 'uppercase region' => ['region', 'Basic', 'Region name must be lowercase kebab-case.'];
        yield 'empty region' => ['region', '', 'Region name must be lowercase kebab-case.'];
    }
}
