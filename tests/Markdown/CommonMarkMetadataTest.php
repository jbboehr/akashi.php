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

namespace jbboehr\Akashi\Tests\Markdown;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Markdown\CommonMarkExampleExtractor;
use jbboehr\Akashi\Markdown\Exception\DirectiveException;
use jbboehr\Akashi\Markdown\Exception\DuplicateMarkerException;
use jbboehr\Akashi\Markdown\Exception\InvalidMarkerMetadataException;
use jbboehr\Akashi\Markdown\Exception\NonPhpMarkerException;
use jbboehr\Akashi\Markdown\Exception\OrphanedMarkerException;
use jbboehr\Akashi\Model\Directive;
use jbboehr\Akashi\Model\InvalidMarkerException;
use jbboehr\Akashi\Model\MarkerName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CommonMarkMetadataTest extends TestCase
{
    public function testAssociatesMarkersAndDirectivesInEitherOrderAcrossWhitespace(): void
    {
        $examples = $this->extract(<<<'MARKDOWN'
<!-- yumemi-example: first-example -->

<!-- akashi: skip -->
<!-- akashi: separate-process -->
```PHP extra
<?php
echo 'first';
```

<!-- akashi: separate-process -->
<!-- akashi: skip -->
<!-- yumemi-example: second-example -->
~~~php
echo 'second';
~~~
MARKDOWN);

        self::assertCount(2, $examples);
        self::assertSame('first-example', $examples[0]->explicitMarkerId?->value);
        self::assertSame('second-example', $examples[1]->explicitMarkerId?->value);
        self::assertTrue($examples[0]->directives->contains(Directive::Skip));
        self::assertTrue($examples[1]->directives->contains(Directive::Skip));
        self::assertTrue($examples[0]->directives->contains(Directive::SeparateProcess));
        self::assertTrue($examples[1]->directives->contains(Directive::SeparateProcess));
        self::assertSame(1, $examples[0]->codeOrigin()->metadata->markerLine);
        self::assertSame(4, $examples[0]->codeOrigin()->metadata->separateProcessDirectiveLine);
        self::assertSame(3, $examples[0]->codeOrigin()->metadata->skipDirectiveLine);
        self::assertSame(12, $examples[1]->codeOrigin()->metadata->markerLine);
        self::assertSame(10, $examples[1]->codeOrigin()->metadata->separateProcessDirectiveLine);
        self::assertSame(11, $examples[1]->codeOrigin()->metadata->skipDirectiveLine);
        self::assertSame("<?php\necho 'first';\n", $examples[0]->code->source);
    }

    public function testRecognizesOnlyTheConfiguredMarkerName(): void
    {
        $document = new Document('docs/metadata.md', <<<'MARKDOWN'
<!-- selected-example: selected -->
```php
echo 'selected';
```

<!-- yumemi-example: ignored -->
```php
echo 'ignored';
```
MARKDOWN);
        $extractor = new CommonMarkExampleExtractor(new MarkerName('selected-example'));
        $examples = $extractor->extract($document);

        self::assertCount(2, $examples);
        self::assertSame('selected', $examples[0]->explicitMarkerId?->value);
        self::assertNull($examples[1]->explicitMarkerId);
    }

    public function testAssociatesMetadataWithinACommonMarkContainer(): void
    {
        $examples = $this->extract(<<<'MARKDOWN'
> <!-- yumemi-example: quoted-example -->
>
> <!-- akashi: separate-process -->
>
> ```php
> echo 'quoted';
> ```
MARKDOWN);

        self::assertCount(1, $examples);
        self::assertSame('quoted-example', $examples[0]->explicitMarkerId?->value);
        self::assertTrue($examples[0]->directives->contains(Directive::SeparateProcess));
        self::assertSame(1, $examples[0]->codeOrigin()->metadata->markerLine);
        self::assertSame(3, $examples[0]->codeOrigin()->metadata->separateProcessDirectiveLine);
    }

    public function testAssociatesATypedExpectedExceptionWithItsSourceLine(): void
    {
        $examples = $this->extract(<<<'MARKDOWN'
<!-- yumemi-example: expected-failure -->
<!-- akashi: expect-exception \Domain\DocumentException -->

```php
throw new \Domain\DocumentException();
```
MARKDOWN);

        self::assertCount(1, $examples);
        self::assertSame('Domain\DocumentException', $examples[0]->expectedException?->className);
        self::assertSame(2, $examples[0]->codeOrigin()->metadata->expectedExceptionDirectiveLine);
    }

    #[DataProvider('inlineExpectedExceptionProvider')]
    public function testAssociatesAnInlineExpectedException(
        string $markdown,
        int $expectedLine,
        string $expectedSource,
    ): void {
        $examples = $this->extract($markdown);

        self::assertCount(1, $examples);
        self::assertSame('Domain\\DocumentException', $examples[0]->expectedException?->className);
        self::assertSame($expectedLine, $examples[0]->codeOrigin()->metadata->expectedExceptionDirectiveLine);
        self::assertSame($expectedSource, $examples[0]->code->source);
    }

    /** @return iterable<string, array{string, positive-int, string}> */
    public static function inlineExpectedExceptionProvider(): iterable
    {
        yield 'first code line' => [
            "```php\n// akashi: expect-exception \\Domain\\DocumentException\nthrow new \\Domain\\DocumentException();\n```\n",
            2,
            "// akashi: expect-exception \\Domain\\DocumentException\n"
                . "throw new \\Domain\\DocumentException();\n",
        ];
        yield 'after opening tag and blank line' => [
            "```php\n<?PHP\n\n// akashi: expect-exception Domain\\DocumentException\n"
                . "throw new \\Domain\\DocumentException();\n```\n",
            4,
            "<?PHP\n\n// akashi: expect-exception Domain\\DocumentException\n"
                . "throw new \\Domain\\DocumentException();\n",
        ];
        yield 'after setup code' => [
            "```php\n\$input = 'invalid';\n// akashi: expect-exception Domain\\DocumentException\n"
                . "throw new \\Domain\\DocumentException(\$input);\n```\n",
            3,
            "\$input = 'invalid';\n// akashi: expect-exception Domain\\DocumentException\n"
                . "throw new \\Domain\\DocumentException(\$input);\n",
        ];
        yield 'after an ordinary comment' => [
            "```php\n// Explain why this fails.\n// akashi: expect-exception Domain\\DocumentException\n"
                . "throw new \\Domain\\DocumentException();\n```\n",
            3,
            "// Explain why this fails.\n// akashi: expect-exception Domain\\DocumentException\n"
                . "throw new \\Domain\\DocumentException();\n",
        ];
    }

    public function testDoesNotTreatDirectiveTextInsideAHeredocAsMetadata(): void
    {
        $examples = $this->extract(<<<'MARKDOWN'
```php
$text = <<<'TEXT'
// akashi: expect-exception RuntimeException
TEXT;
echo $text;
```
MARKDOWN);

        self::assertCount(1, $examples);
        self::assertNull($examples[0]->expectedException);
        self::assertNull($examples[0]->codeOrigin()->metadata->expectedExceptionDirectiveLine);
    }

    public function testAssociatesInlineRuntimeDirectivesAnywhereInTheCode(): void
    {
        $examples = $this->extract(<<<'MARKDOWN'
```php
$prepared = true;
// akashi: skip
echo 'not run'; // akashi: separate-process
```
MARKDOWN);

        self::assertCount(1, $examples);
        self::assertTrue($examples[0]->directives->contains(Directive::Skip));
        self::assertTrue($examples[0]->directives->contains(Directive::SeparateProcess));
        self::assertSame(3, $examples[0]->codeOrigin()->metadata->skipDirectiveLine);
        self::assertSame(4, $examples[0]->codeOrigin()->metadata->separateProcessDirectiveLine);
    }

    public function testRejectsAnInvalidMarkerIdWithItsSourceLocation(): void
    {
        try {
            $this->extract("<!-- yumemi-example: Invalid_ID -->\n```php\necho 1;\n```\n");
            self::fail('The invalid authored marker was accepted.');
        } catch (InvalidMarkerMetadataException $exception) {
            self::assertSame(
                'Invalid yumemi-example marker at docs/metadata.md:1: Marker ID must use lowercase kebab-case.',
                $exception->getMessage(),
            );
            self::assertInstanceOf(InvalidMarkerException::class, $exception->getPrevious());
        }
    }

    public function testRejectsADuplicateMarkerIdWithBothSourceLocations(): void
    {
        $this->expectException(DuplicateMarkerException::class);
        $this->expectExceptionMessage(
            'Duplicate marker ID repeated at docs/metadata.md:6; first declared at docs/metadata.md:1.',
        );

        $this->extract(<<<'MARKDOWN'
<!-- yumemi-example: repeated -->
```php
echo 1;
```

<!-- yumemi-example: repeated -->
```php
echo 2;
```
MARKDOWN);
    }

    public function testRejectsMultipleMarkersAssociatedWithOneFence(): void
    {
        $this->expectException(DuplicateMarkerException::class);
        $this->expectExceptionMessage(
            'PHP fence at docs/metadata.md:3 has multiple markers: first at line 1 and second at line 2.',
        );

        $this->extract(<<<'MARKDOWN'
<!-- yumemi-example: first -->
<!-- yumemi-example: second -->
```php
echo 1;
```
MARKDOWN);
    }

    public function testRejectsAMarkerSeparatedFromTheFenceByProse(): void
    {
        $this->expectException(OrphanedMarkerException::class);
        $this->expectExceptionMessage(
            'Marker selected at docs/metadata.md:1 is not followed by a fenced code block.',
        );

        $this->extract(<<<'MARKDOWN'
<!-- yumemi-example: selected -->

Intervening prose.

```php
echo 1;
```
MARKDOWN);
    }

    public function testRejectsAMarkerSeparatedByUnrecognizedMetadata(): void
    {
        $this->expectException(OrphanedMarkerException::class);
        $this->expectExceptionMessage(
            'Marker selected at docs/metadata.md:1 is not followed by a fenced code block.',
        );

        $this->extract(<<<'MARKDOWN'
<!-- yumemi-example: selected -->
<!-- unrelated: metadata -->
```php
echo 1;
```
MARKDOWN);
    }

    public function testRejectsAMarkerFollowedByANonPhpFence(): void
    {
        $this->expectException(NonPhpMarkerException::class);
        $this->expectExceptionMessage(
            'Marker selected at docs/metadata.md:1 is followed by a javascript fence, not a PHP fence.',
        );

        $this->extract("<!-- yumemi-example: selected -->\n```javascript\necho 1;\n```\n");
    }

    #[DataProvider('invalidDirectiveProvider')]
    public function testRejectsInvalidDirectiveMetadata(string $markdown, string $message): void
    {
        $this->expectException(DirectiveException::class);
        $this->expectExceptionMessage($message);

        (new CommonMarkExampleExtractor())->extract(new Document('docs/directives.md', $markdown));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidDirectiveProvider(): iterable
    {
        yield 'unknown' => [
            "<!-- akashi: elsewhere -->\n```php\necho 1;\n```\n",
            'Unknown Akashi directive "elsewhere" at docs/directives.md:1.',
        ];
        yield 'duplicate' => [
            "<!-- akashi: separate-process -->\n<!-- akashi: separate-process -->\n```php\necho 1;\n```\n",
            'Duplicate Akashi directive separate-process at docs/directives.md:2; '
                . 'first declared at docs/directives.md:1.',
        ];
        yield 'duplicate skip' => [
            "<!-- akashi: skip -->\n<!-- akashi: skip -->\n```php\necho 1;\n```\n",
            'Duplicate Akashi directive skip at docs/directives.md:2; first declared at docs/directives.md:1.',
        ];
        yield 'missing expected exception class' => [
            "<!-- akashi: expect-exception -->\n```php\necho 1;\n```\n",
            'Invalid Akashi expect-exception directive at docs/directives.md:1: '
                . 'Expected exception class must be a syntactically valid global PHP class name.',
        ];
        yield 'invalid expected exception class' => [
            "<!-- akashi: expect-exception RuntimeException::class -->\n```php\necho 1;\n```\n",
            'Invalid Akashi expect-exception directive at docs/directives.md:1: '
                . 'Expected exception class must be a syntactically valid global PHP class name.',
        ];
        yield 'duplicate expected exception' => [
            "<!-- akashi: expect-exception RuntimeException -->\n"
                . "<!-- akashi: expect-exception LogicException -->\n```php\necho 1;\n```\n",
            'Duplicate Akashi directive expect-exception at docs/directives.md:2; '
                . 'first declared at docs/directives.md:1.',
        ];
        yield 'missing inline expected exception class' => [
            "```php\n// akashi: expect-exception\necho 1;\n```\n",
            'Invalid inline Akashi expect-exception directive at docs/directives.md:2: '
                . 'Expected exception class must be a syntactically valid global PHP class name.',
        ];
        yield 'invalid inline expected exception class' => [
            "```php\n// akashi: expect-exception RuntimeException::class\necho 1;\n```\n",
            'Invalid inline Akashi expect-exception directive at docs/directives.md:2: '
                . 'Expected exception class must be a syntactically valid global PHP class name.',
        ];
        yield 'duplicate inline expected exception' => [
            "```php\n// akashi: expect-exception RuntimeException\n"
                . "// akashi: expect-exception LogicException\nthrow new RuntimeException();\n```\n",
            'Duplicate inline Akashi directive expect-exception at docs/directives.md:3; '
                . 'first declared at docs/directives.md:2.',
        ];
        yield 'unknown inline directive' => [
            "```php\n// akashi: elsewhere\necho 1;\n```\n",
            'Unknown inline Akashi directive "elsewhere" at docs/directives.md:2.',
        ];
        yield 'duplicate inline runtime directive' => [
            "```php\n// akashi: skip\necho 1; // akashi: skip\n```\n",
            'Duplicate inline Akashi directive skip at docs/directives.md:3; '
                . 'first declared at docs/directives.md:2.',
        ];
        yield 'external and inline runtime directive' => [
            "<!-- akashi: separate-process -->\n```php\n// akashi: separate-process\nexit(0);\n```\n",
            'Duplicate Akashi directive separate-process at docs/directives.md:3; '
                . 'first declared at docs/directives.md:1.',
        ];
        yield 'external and inline expected exception' => [
            "<!-- akashi: expect-exception RuntimeException -->\n```php\n"
                . "// akashi: expect-exception RuntimeException\nthrow new RuntimeException();\n```\n",
            'Duplicate Akashi directive expect-exception at docs/directives.md:3; '
                . 'first declared at docs/directives.md:1.',
        ];
        yield 'orphaned' => [
            "<!-- akashi: skip -->\n\nIntervening prose.\n",
            'Akashi directive skip at docs/directives.md:1 is not followed by a fenced code block.',
        ];
        yield 'non-PHP fence' => [
            "<!-- akashi: skip -->\n```shell\necho 1\n```\n",
            'Akashi directive skip at docs/directives.md:1 is followed by a shell fence, not a PHP fence.',
        ];
        yield 'orphaned expected exception' => [
            "<!-- akashi: expect-exception RuntimeException -->\n\nIntervening prose.\n",
            'Akashi directive expect-exception RuntimeException at docs/directives.md:1 is not followed by a '
                . 'fenced code block.',
        ];
        yield 'expected exception on non-PHP fence' => [
            "<!-- akashi: expect-exception RuntimeException -->\n```shell\necho 1\n```\n",
            'Akashi directive expect-exception RuntimeException at docs/directives.md:1 is followed by a shell '
                . 'fence, not a PHP fence.',
        ];
    }

    /**
     * @return list<\jbboehr\Akashi\Example>
     */
    private function extract(string $markdown): array
    {
        return (new CommonMarkExampleExtractor('yumemi-example'))->extract(
            new Document('docs/metadata.md', $markdown),
        );
    }
}
