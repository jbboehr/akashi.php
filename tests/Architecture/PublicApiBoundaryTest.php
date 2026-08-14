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

namespace jbboehr\Akashi\Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class PublicApiBoundaryTest extends TestCase
{
    /** @var array<string, list<class-string>> */
    private const PUBLIC_TYPE_CATEGORIES = [
        'entry points' => [
            \jbboehr\Akashi\Execution\ExecutionMode::class,
            \jbboehr\Akashi\Execution\RuntimeConfiguration::class,
            \jbboehr\Akashi\Formatting\FormattingChecker::class,
            \jbboehr\Akashi\Formatting\FormattingRewriter::class,
            \jbboehr\Akashi\Formatting\PhpCsFixerConfiguration::class,
            \jbboehr\Akashi\Integration\PHPStan\DiagnosticMatcher::class,
            \jbboehr\Akashi\Integration\PHPStan\ExpectationParser::class,
            \jbboehr\Akashi\Integration\PHPStan\PhpStanCommandRunner::class,
            \jbboehr\Akashi\Integration\PHPStan\PhpStanExampleConfiguration::class,
            \jbboehr\Akashi\Integration\PHPStan\PhpStanJsonDecoder::class,
            \jbboehr\Akashi\Integration\PHPStan\PhpStanResultVerifier::class,
            \jbboehr\Akashi\Integration\PHPStan\VerifiesPhpStanExamples::class,
            \jbboehr\Akashi\Integration\PhpUnit\PhpUnitExampleDataSets::class,
            \jbboehr\Akashi\Integration\PhpUnit\PhpUnitRuntime::class,
            \jbboehr\Akashi\Integration\PhpUnit\VerifiesPhpUnitExamples::class,
            \jbboehr\Akashi\Source\DocumentationSource::class,
            \jbboehr\Akashi\Source\MarkdownSource::class,
            \jbboehr\Akashi\Source\MarkedExampleSelector::class,
            \jbboehr\Akashi\Synchronization\SynchronizationChecker::class,
            \jbboehr\Akashi\Synchronization\SynchronizationWriter::class,
        ],
        'canonical model' => [
            \jbboehr\Akashi\Document::class,
            \jbboehr\Akashi\Example::class,
            \jbboehr\Akashi\ExampleCorpus::class,
            \jbboehr\Akashi\Model\AbsoluteFilePath::class,
            \jbboehr\Akashi\Model\CodeOrigin::class,
            \jbboehr\Akashi\Model\Directive::class,
            \jbboehr\Akashi\Model\DirectiveSet::class,
            \jbboehr\Akashi\Model\DocumentPath::class,
            \jbboehr\Akashi\Model\ExampleCode::class,
            \jbboehr\Akashi\Model\ExampleId::class,
            \jbboehr\Akashi\Model\ExpectedException::class,
            \jbboehr\Akashi\Model\FenceCharacter::class,
            \jbboehr\Akashi\Model\FenceMetadata::class,
            \jbboehr\Akashi\Model\Language::class,
            \jbboehr\Akashi\Model\LineIndex::class,
            \jbboehr\Akashi\Model\InlineExampleSource::class,
            \jbboehr\Akashi\Model\MarkerId::class,
            \jbboehr\Akashi\Model\MarkerName::class,
            \jbboehr\Akashi\Model\MetadataLocation::class,
            \jbboehr\Akashi\Model\PhpDocTagName::class,
            \jbboehr\Akashi\Model\ProjectPath::class,
            \jbboehr\Akashi\Model\ProjectRoot::class,
            \jbboehr\Akashi\Model\ReferenceLocation::class,
            \jbboehr\Akashi\Model\ReferencedExampleSource::class,
            \jbboehr\Akashi\Model\RegionName::class,
            \jbboehr\Akashi\Model\SourceLocation::class,
            \jbboehr\Akashi\Model\SourceSpan::class,
        ],
        'PHPStan diagnostic model' => [
            \jbboehr\Akashi\Integration\PHPStan\AnalyzerDiagnostic::class,
            \jbboehr\Akashi\Integration\PHPStan\DiagnosticAssignment::class,
            \jbboehr\Akashi\Integration\PHPStan\DiagnosticExpectation::class,
            \jbboehr\Akashi\Integration\PHPStan\DiagnosticMatchResult::class,
            \jbboehr\Akashi\Integration\PHPStan\DiagnosticMismatchKind::class,
            \jbboehr\Akashi\Integration\PHPStan\DiagnosticsMatched::class,
            \jbboehr\Akashi\Integration\PHPStan\DiagnosticsMismatched::class,
            \jbboehr\Akashi\Integration\PHPStan\PhpStanJsonResult::class,
            \jbboehr\Akashi\Integration\PHPStan\PhpStanVerificationResult::class,
        ],
        'PHPStan command model' => [
            \jbboehr\Akashi\Integration\PHPStan\PhpStanCommandResult::class,
            \jbboehr\Akashi\Integration\PHPStan\PhpStanCommandTermination::class,
        ],
        'synchronization model' => [
            \jbboehr\Akashi\Synchronization\SynchronizationMismatch::class,
            \jbboehr\Akashi\Synchronization\SynchronizationRegion::class,
        ],
        'formatting model' => [
            \jbboehr\Akashi\Formatting\FormattingMismatch::class,
        ],
        'exceptions' => [
            \jbboehr\Akashi\Execution\Exception\ExecutionException::class,
            \jbboehr\Akashi\Execution\Exception\ExecutionInfrastructureException::class,
            \jbboehr\Akashi\Execution\Exception\RuntimeConfigurationException::class,
            \jbboehr\Akashi\Formatting\Exception\FormattingCleanupException::class,
            \jbboehr\Akashi\Formatting\Exception\FormattingConfigurationException::class,
            \jbboehr\Akashi\Formatting\Exception\FormattingException::class,
            \jbboehr\Akashi\Formatting\Exception\FormattingExecutionException::class,
            \jbboehr\Akashi\Formatting\Exception\FormattingOutputException::class,
            \jbboehr\Akashi\Formatting\Exception\FormattingRewriteException::class,
            \jbboehr\Akashi\Formatting\Exception\UnsupportedFormattingExampleException::class,
            \jbboehr\Akashi\Integration\PHPStan\Exception\ExpectationParseException::class,
            \jbboehr\Akashi\Integration\PHPStan\Exception\NoRelevantExamplesException::class,
            \jbboehr\Akashi\Integration\PHPStan\Exception\PhpStanConfigurationException::class,
            \jbboehr\Akashi\Integration\PHPStan\Exception\PhpStanException::class,
            \jbboehr\Akashi\Integration\PHPStan\Exception\PhpStanJsonDecodeException::class,
            \jbboehr\Akashi\Integration\PHPStan\Exception\PhpStanVerificationException::class,
            \jbboehr\Akashi\Markdown\Exception\DirectiveException::class,
            \jbboehr\Akashi\Markdown\Exception\DuplicateMarkerException::class,
            \jbboehr\Akashi\Markdown\Exception\InvalidMarkerMetadataException::class,
            \jbboehr\Akashi\Markdown\Exception\NonPhpMarkerException::class,
            \jbboehr\Akashi\Markdown\Exception\OrphanedMarkerException::class,
            \jbboehr\Akashi\Model\InvalidMarkerException::class,
            \jbboehr\Akashi\Source\Exception\DuplicateDocumentException::class,
            \jbboehr\Akashi\Source\Exception\InvalidExampleReferenceException::class,
            \jbboehr\Akashi\Source\Exception\MarkerNotFoundException::class,
            \jbboehr\Akashi\Source\Exception\NoDocumentsFoundException::class,
            \jbboehr\Akashi\Source\Exception\NoExamplesFoundException::class,
            \jbboehr\Akashi\Source\Exception\ProjectRootNotFoundException::class,
            \jbboehr\Akashi\Source\Exception\SourceException::class,
            \jbboehr\Akashi\Source\Exception\SourcePathNotFoundException::class,
            \jbboehr\Akashi\Source\Exception\SourceReadException::class,
            \jbboehr\Akashi\Source\Exception\UnsafeSourcePathException::class,
            \jbboehr\Akashi\Source\Exception\UnsupportedSourcePathException::class,
            \jbboehr\Akashi\Synchronization\Exception\InvalidSynchronizationRegionException::class,
            \jbboehr\Akashi\Synchronization\Exception\SynchronizationException::class,
            \jbboehr\Akashi\Synchronization\Exception\SynchronizationWriteException::class,
            \jbboehr\Akashi\Transform\Exception\PhpParseException::class,
            \jbboehr\Akashi\Transform\Exception\TransformException::class,
            \jbboehr\Akashi\Transform\Exception\UnsupportedExampleException::class,
        ],
    ];

    public function testPublicApiCategoriesAreDisjoint(): void
    {
        $seen = [];

        foreach (self::PUBLIC_TYPE_CATEGORIES as $category => $types) {
            foreach ($types as $type) {
                self::assertArrayNotHasKey(
                    $type,
                    $seen,
                    sprintf('Public API type %s appears in categories %s and %s.', $type, $seen[$type] ?? '', $category),
                );
                $seen[$type] = $category;
            }
        }
    }

    public function testEveryAutoloadedDeclarationHasAnExplicitApiBoundary(): void
    {
        $declarations = self::declarations();
        $publicTypes = array_fill_keys(self::publicTypes(), null);

        foreach ($declarations as $name => $declaration) {
            $isInternal = str_contains($declaration->getDocComment() ?: '', '@internal');

            if (array_key_exists($name, $publicTypes)) {
                self::assertFalse($isInternal, sprintf('Public API type %s must not be marked @internal.', $name));
                unset($publicTypes[$name]);

                continue;
            }

            self::assertTrue($isInternal, sprintf(
                'Declaration %s must be added to the public API allowlist or marked @internal.',
                $name,
            ));
        }

        self::assertSame([], array_keys($publicTypes), 'Every public API type must resolve to an autoloaded declaration.');
    }

    public function testPublicSignaturesDoNotExposeInternalAkashiTypes(): void
    {
        $declarations = self::declarations();

        foreach (self::publicTypes() as $name) {
            $declaration = $declarations[$name];
            $parent = $declaration->getParentClass();
            if ($parent !== false) {
                self::assertPublicAkashiType($parent->getName(), sprintf('%s parent', $name));
            }

            foreach ($declaration->getInterfaceNames() as $interface) {
                self::assertPublicAkashiType($interface, sprintf('%s interface', $name));
            }

            foreach ($declaration->getTraitNames() as $trait) {
                self::assertPublicAkashiType($trait, sprintf('%s trait', $name));
            }

            foreach ($declaration->getProperties() as $property) {
                if ($property->isPrivate()) {
                    continue;
                }

                self::assertPublicType($property->getType(), sprintf('%s::$%s', $name, $property->getName()));
            }

            foreach ($declaration->getMethods() as $method) {
                if ($method->isPrivate()) {
                    continue;
                }

                foreach ($method->getParameters() as $parameter) {
                    self::assertPublicType(
                        $parameter->getType(),
                        sprintf('%s::%s($%s)', $name, $method->getName(), $parameter->getName()),
                    );
                }

                self::assertPublicType($method->getReturnType(), sprintf('%s::%s() return', $name, $method->getName()));
            }
        }
    }

    public function testReadonlyPhpDocClassesUseNativeReadonlyProperties(): void
    {
        $readonlyClasses = 0;

        foreach (self::declarations() as $name => $declaration) {
            if (!str_contains($declaration->getDocComment() ?: '', '@readonly')) {
                continue;
            }

            ++$readonlyClasses;

            foreach ($declaration->getProperties() as $property) {
                if ($property->getDeclaringClass()->getName() !== $name) {
                    continue;
                }

                self::assertFalse($property->isStatic(), sprintf(
                    'Readonly class contract %s must not declare static property $%s.',
                    $name,
                    $property->getName(),
                ));
                self::assertTrue($property->isReadOnly(), sprintf(
                    'Readonly class contract %s must declare property $%s readonly.',
                    $name,
                    $property->getName(),
                ));
            }
        }

        self::assertGreaterThan(0, $readonlyClasses, 'At least one class must exercise the readonly compatibility contract.');
    }

    /**
     * @return array<class-string, \ReflectionClass<object>>
     */
    private static function declarations(): array
    {
        $sourceRoot = realpath(__DIR__ . '/../../src');
        self::assertNotFalse($sourceRoot);
        $sourceRoot = str_replace('\\', '/', $sourceRoot);
        $declarations = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceRoot, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = str_replace('\\', '/', $file->getPathname());
            $relativePath = substr($path, strlen($sourceRoot) + 1, -4);
            $name = 'jbboehr\\Akashi\\' . str_replace('/', '\\', $relativePath);
            self::assertTrue(self::declarationExists($name), sprintf('Unable to autoload declaration %s.', $name));

            /** @var class-string $name */
            $declarations[$name] = new \ReflectionClass($name);
        }

        ksort($declarations);

        return $declarations;
    }

    private static function declarationExists(string $name): bool
    {
        return class_exists($name)
            || interface_exists($name)
            || trait_exists($name)
            || enum_exists($name);
    }

    /** @return list<class-string> */
    private static function publicTypes(): array
    {
        return array_merge(...array_values(self::PUBLIC_TYPE_CATEGORIES));
    }

    private static function assertPublicType(?\ReflectionType $type, string $context): void
    {
        if ($type === null) {
            return;
        }

        if ($type instanceof \ReflectionNamedType) {
            if (!$type->isBuiltin() && !in_array($type->getName(), ['self', 'parent', 'static'], true)) {
                self::assertPublicAkashiType($type->getName(), $context);
            }

            return;
        }

        if (!$type instanceof \ReflectionUnionType && !$type instanceof \ReflectionIntersectionType) {
            throw new \LogicException(sprintf('Unsupported reflection type %s.', $type::class));
        }

        foreach ($type->getTypes() as $member) {
            self::assertPublicType($member, $context);
        }
    }

    private static function assertPublicAkashiType(string $name, string $context): void
    {
        if (!str_starts_with($name, 'jbboehr\\Akashi\\')) {
            return;
        }

        self::assertContains(
            $name,
            self::publicTypes(),
            sprintf('Public signature %s exposes internal Akashi type %s.', $context, $name),
        );
    }
}
