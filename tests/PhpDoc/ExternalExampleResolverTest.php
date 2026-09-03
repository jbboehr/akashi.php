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

namespace jbboehr\Akashi\Tests\PhpDoc;

use jbboehr\Akashi\Model\ProjectPath;
use jbboehr\Akashi\Model\ProjectRoot;
use jbboehr\Akashi\Model\RegionName;
use jbboehr\Akashi\PhpDoc\ExternalExampleResolver;
use jbboehr\Akashi\Source\Exception\InvalidExampleReferenceException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExternalExampleResolverTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        $projectRoot = tempnam(sys_get_temp_dir(), 'akashi-setup-source-');
        self::assertNotFalse($projectRoot);
        self::assertTrue(unlink($projectRoot));
        self::assertTrue(mkdir($projectRoot, 0o700));

        $this->projectRoot = $projectRoot;
    }

    protected function tearDown(): void
    {
        $files = glob($this->projectRoot . '/*');
        self::assertIsArray($files);
        foreach ($files as $file) {
            self::assertTrue(unlink($file));
        }

        self::assertTrue(rmdir($this->projectRoot));
    }

    public function testSetupModeUsesTheWholeFile(): void
    {
        $source = '<?php $input = 1;';
        $this->write('setup.php', $source);

        $resolved = $this->resolve('setup.php');

        self::assertSame(1, $resolved['firstLine']);
        self::assertSame(1, $resolved['lastLine']);
        self::assertSame($source, $resolved['code']);
        self::assertSame($source, $resolved['document']->lines->slice($resolved['span']));
        self::assertNull($resolved['region']);
    }

    public function testSetupModeTreatsAnonymousMarkerCommentsAsOrdinaryWholeFileSource(): void
    {
        $source = <<<'PHP'
<?php
$outside = 'ignored';
// akashi-setup-start
$input = 1;
$unit = 'meter';
// akashi-setup-end
$after = 'ignored';
PHP;
        $this->write('setup.php', $source);

        $resolved = $this->resolve('setup.php');

        self::assertSame(1, $resolved['firstLine']);
        self::assertSame(7, $resolved['lastLine']);
        self::assertSame($source, $resolved['code']);
        self::assertNull($resolved['region']);
    }

    public function testPathOnlySetupDoesNotInterpretNamedRegionMarkers(): void
    {
        $source = <<<'PHP'
<?php
// akashi-region: selected
$insideLexicalRegion = true;
$stillInTheWholeFile = true;
PHP;
        $this->write('setup.php', $source);

        $resolved = $this->resolve('setup.php');

        self::assertSame(1, $resolved['firstLine']);
        self::assertSame(4, $resolved['lastLine']);
        self::assertSame($source, $resolved['code']);
        self::assertNull($resolved['region']);
    }

    public function testSetupModeSelectsANamedRegion(): void
    {
        $this->write('setup.php', <<<'PHP'
<?php
// akashi-region: setup
$named = true;
// akashi-region-end: setup
PHP);

        $resolved = $this->resolve('setup.php', new RegionName('setup'));

        self::assertSame(3, $resolved['firstLine']);
        self::assertSame(3, $resolved['lastLine']);
        self::assertSame("\$named = true;\n", $resolved['code']);
        self::assertSame($resolved['code'], $resolved['document']->lines->slice($resolved['span']));
        self::assertSame('setup', $resolved['region']?->value);
    }

    public function testSetupModeMaySelectARegionAfterAnotherRegion(): void
    {
        $this->write('setup.php', <<<'PHP'
<?php
// akashi-region: first
$first = true;
// akashi-region-end: first
// akashi-region: selected
$selected = true;
// akashi-region-end: selected
PHP);

        $resolved = $this->resolve('setup.php', new RegionName('selected'));

        self::assertSame(6, $resolved['firstLine']);
        self::assertSame(6, $resolved['lastLine']);
        self::assertSame("\$selected = true;\n", $resolved['code']);
        self::assertSame('selected', $resolved['region']?->value);
    }

    public function testSetupModeIgnoresNopsAndTopLevelStatementsBeforeANamedRegion(): void
    {
        $this->write('setup.php', <<<'PHP'
<?php
// An unrelated empty statement.
;
$outside = false;
// akashi-region: selected
$selected = true;
// akashi-region-end: selected
PHP);

        $resolved = $this->resolve('setup.php', new RegionName('selected'));

        self::assertSame(6, $resolved['firstLine']);
        self::assertSame(6, $resolved['lastLine']);
        self::assertSame("\$selected = true;\n", $resolved['code']);
    }

    public function testSetupModeSelectsACompleteUnbracketedNamespace(): void
    {
        $this->write('setup.php', <<<'PHP'
<?php
// akashi-region: selected
namespace Selected;
$selected = true;
// akashi-region-end: selected
namespace Outside;
$outside = true;
PHP);

        $resolved = $this->resolve('setup.php', new RegionName('selected'));

        self::assertSame(3, $resolved['firstLine']);
        self::assertSame(4, $resolved['lastLine']);
        self::assertSame("namespace Selected;\n\$selected = true;\n", $resolved['code']);
        self::assertSame('selected', $resolved['region']?->value);
    }

    public function testOrdinaryNamedRegionResolutionMaySelectContextDependentCode(): void
    {
        $this->write('setup.php', <<<'PHP'
<?php
final class Fixture
{
    // akashi-region: selected
    private int $value = 1;
    // akashi-region-end: selected
}
PHP);

        $resolved = (new ExternalExampleResolver())->resolveSource(
            new ProjectRoot($this->projectRoot),
            new ProjectPath('setup.php'),
            new RegionName('selected'),
        );

        self::assertSame(5, $resolved['firstLine']);
        self::assertSame(5, $resolved['lastLine']);
        self::assertSame("    private int \$value = 1;\n", $resolved['code']);
    }

    public function testSetupModeParsesSyntaxOutsideTheSelectedSlice(): void
    {
        $this->write('setup.php', <<<'PHP'
<?php
if (
// akashi-region: selected
$input = 1;
// akashi-region-end: selected
PHP);

        $this->expectException(InvalidExampleReferenceException::class);
        $this->expectExceptionMessage('Unable to parse setup file setup.php:');

        $this->resolve('setup.php', new RegionName('selected'));
    }

    #[DataProvider('contextualNamedSetupProvider')]
    public function testSetupModeRejectsContextDependentNamedRegions(string $source): void
    {
        $this->write('setup.php', $source);

        $this->expectException(InvalidExampleReferenceException::class);
        $this->expectExceptionMessage('must contain complete top-level PHP statements');

        $this->resolve('setup.php', new RegionName('selected'));
    }

    /** @return iterable<string, array{string}> */
    public static function contextualNamedSetupProvider(): iterable
    {
        yield 'class body' => [<<<'PHP'
<?php
final class Fixture
{
    // akashi-region: selected
    private int $value = 1;
    // akashi-region-end: selected
}
PHP];
        yield 'function body' => [<<<'PHP'
<?php
function fixture(): void
{
    // akashi-region: selected
    $value = 1;
    // akashi-region-end: selected
}
PHP];
        yield 'namespace block' => [<<<'PHP'
<?php
namespace Fixture {
    // akashi-region: selected
    $value = 1;
    // akashi-region-end: selected
}
PHP];
        yield 'cuts an attributed declaration' => [<<<'PHP'
<?php
#[Fixture]
// akashi-region: selected
final class Example {}
// akashi-region-end: selected
PHP];
        yield 'contains a complete statement and cuts another' => [<<<'PHP'
<?php
// akashi-region: selected
$complete = true;
$partial = (
// akashi-region-end: selected
    false
);
PHP];
        yield 'comment-only region' => [<<<'PHP'
<?php
// akashi-region: selected
// This comment is not a PHP statement.
// akashi-region-end: selected
PHP];
    }

    public function testSetupModeRejectsInvalidWholeFilePhp(): void
    {
        $this->write('setup.php', "<?php\nif (\n");

        $this->expectException(InvalidExampleReferenceException::class);
        $this->expectExceptionMessage('Unable to parse setup file setup.php:');

        $this->resolve('setup.php');
    }

    public function testSetupModeRejectsPhpRejectedOnlyByTheLibraryParser(): void
    {
        $source = "<?php\n\$value = 1;\nnamespace Fixture;\n";
        self::assertNotEmpty(token_get_all($source, TOKEN_PARSE));
        $this->write('setup.php', $source);

        $this->expectException(InvalidExampleReferenceException::class);
        $this->expectExceptionMessage('Unable to parse setup file setup.php:');

        $this->resolve('setup.php');
    }

    public function testSetupModeRejectsAWhitespaceOnlyWholeFile(): void
    {
        $this->write('setup.php', " \t\n");

        $this->expectException(InvalidExampleReferenceException::class);
        $this->expectExceptionMessage('Referenced example file contains no PHP source: setup.php.');

        $this->resolve('setup.php');
    }

    public function testSetupModeWrapsNativeSyntaxErrorsAtTheSourceBoundary(): void
    {
        if (PHP_VERSION_ID >= 80300) {
            self::markTestSkipped('Typed class constants are valid on PHP 8.3 and later.');
        }

        $this->write('setup.php', "<?php\nfinal class Fixture { public const string VALUE = 'value'; }\n");

        try {
            $this->resolve('setup.php');
            self::fail('Expected the native PHP grammar to reject the setup source.');
        } catch (InvalidExampleReferenceException $exception) {
            self::assertStringContainsString('Unable to parse setup file setup.php:', $exception->getMessage());
            self::assertInstanceOf(\ParseError::class, $exception->getPrevious());
        }
    }

    /**
     * @return array{
     *     document: \jbboehr\Akashi\Document,
     *     identity: string,
     *     region: RegionName|null,
     *     firstLine: positive-int,
     *     lastLine: positive-int,
     *     span: \jbboehr\Akashi\Model\SourceSpan,
     *     code: string
     * }
     */
    private function resolve(string $path, ?RegionName $region = null, bool $forSetup = true): array
    {
        return (new ExternalExampleResolver())->resolveSource(
            new ProjectRoot($this->projectRoot),
            new ProjectPath($path),
            $region,
            $forSetup,
        );
    }

    private function write(string $path, string $contents): void
    {
        self::assertNotFalse(file_put_contents($this->projectRoot . '/' . $path, $contents));
    }
}
