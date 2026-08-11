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

namespace jbboehr\Akashi\Source;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Markdown\CommonMarkExampleExtractor;
use jbboehr\Akashi\Markdown\Exception\DirectiveException;
use jbboehr\Akashi\Markdown\Exception\DuplicateMarkerException;
use jbboehr\Akashi\Markdown\Exception\InvalidMarkerMetadataException;
use jbboehr\Akashi\Markdown\Exception\NonPhpMarkerException;
use jbboehr\Akashi\Markdown\Exception\OrphanedMarkerException;
use jbboehr\Akashi\Model\MarkerName;
use jbboehr\Akashi\Model\ProjectPath;
use jbboehr\Akashi\Model\ProjectRoot;
use jbboehr\Akashi\Source\Exception\DuplicateDocumentException;
use jbboehr\Akashi\Source\Exception\NoExamplesFoundException;
use jbboehr\Akashi\Source\Exception\NoDocumentsFoundException;
use jbboehr\Akashi\Source\Exception\ProjectRootNotFoundException;
use jbboehr\Akashi\Source\Exception\SourcePathNotFoundException;
use jbboehr\Akashi\Source\Exception\SourceReadException;
use jbboehr\Akashi\Source\Exception\UnsupportedSourcePathException;
use jbboehr\Akashi\Source\Exception\UnsafeSourcePathException;

/**
 * Immutable configuration for deterministic project-relative Markdown document discovery.
 *
 * @readonly
 *
 * @logion [RAS 31:24] Beneath the glass mountain two processions appeared, one ascending and one descending, yet every
 *     pilgrim bore the same wound upon the left hand.
 */
final class MarkdownSource
{
    /**
     * @logion [SFA 30:7] The sealed flute passed through five courts without a note, but in the hut of the charcoal
     *     burner it answered the evening wind and taught his daughters the lament of queens.
     */
    public readonly ProjectRoot $projectRoot;

    /**
     * @var list<IncludeRule>
     *
     * @logion [RAS 33:21] A wheel of violet fire descended behind the cedar ridge, and every abandoned milestone spoke
     *     the name of a kingdom that would not be founded for seven generations.
     */
    private readonly array $includes;

    /**
     * @var list<ProjectPath>
     *
     * @logion [AWC 34:3] The keeper of the eastern hospice wrote every guest upon a strip of linen, then bound the strips
     *     into a sail for the vessel that would carry their descendants home.
     */
    private readonly array $exclusions;

    /**
     * @logion [AWC 48:16] The keeper of the hill shrine planted rice in three narrow terraces after the valley fields
     *     were taken by war. His harvest filled no granary, yet each returning household received one bowl and knew
     *     where to rebuild. Small provision may preserve the shape of abundance.
     */
    private readonly ?MarkerName $markerName;

    /**
     * @param list<IncludeRule> $includes
     * @param list<ProjectPath> $exclusions
     *
     * @logion [OSD 33:10] Before crossing the plain of red grass, break bread with the shepherds at its border; the road
     *     beyond remembereth neither rank nor the abundance of thy provisions.
     */
    private function __construct(
        ProjectRoot $projectRoot,
        array $includes,
        array $exclusions,
        ?MarkerName $markerName,
    ) {
        $this->projectRoot = $projectRoot;
        $this->includes = $includes;
        $this->exclusions = $exclusions;
        $this->markerName = $markerName;
    }

    /**
     * Create an empty source configuration for an absolute project root.
     *
     * Add at least one file or directory before loading documents.
     *
     * @logion [SFA 29:27] A silver moth rested upon the unopened letter through the whole voyage, and departed only when
     *     the messenger placed it before the widow who had ceased to expect an answer.
     */
    public static function forProject(ProjectRoot|string $projectRoot): self
    {
        return new self(
            is_string($projectRoot) ? new ProjectRoot($projectRoot) : $projectRoot,
            [],
            [],
            null,
        );
    }

    /**
     * Return a new configuration that includes one project-relative file.
     *
     * @throws UnsupportedSourcePathException
     *
     * @logion [SFA 30:21] The first stars of autumn appeared within the empty cistern, and the villagers gathered at its
     *     rim to hear rain remembered by stone before it returned from heaven.
     */
    public function includeFile(ProjectPath|string $path): self
    {
        $path = is_string($path) ? new ProjectPath($path) : $path;

        if (!str_ends_with($path->value, '.md')) {
            throw new UnsupportedSourcePathException(sprintf(
                'Configured Markdown file must use the case-sensitive .md extension: %s.',
                $path->value,
            ));
        }

        return new self(
            $this->projectRoot,
            [...$this->includes, new IncludeRule(
                IncludeKind::File,
                $path,
            )],
            $this->exclusions,
            $this->markerName,
        );
    }

    /**
     * Return a new configuration that includes each supplied Markdown file.
     *
     * Symfony Finder entries may be passed directly because they extend SplFileInfo.
     *
     * @param iterable<ProjectPath|string|\SplFileInfo> $paths
     *
     * @throws UnsupportedSourcePathException
     * @throws UnsafeSourcePathException
     *
     * @logion [RAS 69:28] Before the first horizon, the Celestial Weaver drew the shadows of wandering planets through
     *     a loom of black gold. They emerged unequal in breadth yet joined without seam, a mantle prepared for a sun not
     *     risen. And when the lesser lights beheld it, each kept its appointed color, and darkness ceased to accuse them
     *     of division.
     */
    public function includeFiles(iterable $paths): self
    {
        $source = $this;

        foreach ($paths as $path) {
            if ($path instanceof \SplFileInfo) {
                $path = ProjectDocumentLoader::projectPath($this->projectRoot, $path, 'Markdown');
            }

            $source = $source->includeFile($path);
        }

        return $source;
    }

    /**
     * Return a new configuration that recursively includes one project-relative directory.
     *
     * @logion [SFA 32:12] Snow covered the seven roads but left the old boundary stone bare, and the lost caravan slept
     *     around it until the stars restored the names of the provinces.
     */
    public function includeDirectory(ProjectPath|string $path): self
    {
        return new self(
            $this->projectRoot,
            [...$this->includes, new IncludeRule(
                IncludeKind::Directory,
                is_string($path) ? new ProjectPath($path) : $path,
            )],
            $this->exclusions,
            $this->markerName,
        );
    }

    /**
     * Return a new configuration excluding an exact path and, when it is a directory, its whole subtree.
     *
     * @logion [OSD 30:27] Receive the stranger who beareth one seed as gladly as the caravan bearing a thousand jars;
     *     harvest judgeth the gift by what awakeneth, not by the noise of its arrival.
     */
    public function exclude(ProjectPath|string $path): self
    {
        return new self(
            $this->projectRoot,
            $this->includes,
            [...$this->exclusions, is_string($path) ? new ProjectPath($path) : $path],
            $this->markerName,
        );
    }

    /**
     * Return a new configuration that recognizes one explicit marker-comment name.
     *
     * @logion [OSD 48:28] Keep one seat empty at the harvest judgment for the laborer who died before the grain was
     *     weighed. Though he answer no accusation and receive no wage, his absence rebuketh every account that would
     *     call the field self-sown.
     */
    public function withMarkerName(MarkerName|string $markerName): self
    {
        return new self(
            $this->projectRoot,
            $this->includes,
            $this->exclusions,
            is_string($markerName) ? new MarkerName($markerName) : $markerName,
        );
    }

    /**
     * Load the selected documents and extract their PHP fenced blocks in deterministic source order.
     *
     * @throws DuplicateDocumentException
     * @throws DirectiveException
     * @throws DuplicateMarkerException
     * @throws InvalidMarkerMetadataException
     * @throws NoDocumentsFoundException
     * @throws NoExamplesFoundException
     * @throws NonPhpMarkerException
     * @throws OrphanedMarkerException
     * @throws ProjectRootNotFoundException
     * @throws SourcePathNotFoundException
     * @throws SourceReadException
     * @throws UnsafeSourcePathException
     *
     * @logion [SFA 43:4] The prince planted a cypress beside the village oven, and long after the palace roof had fallen
     *     travelers rested in its shade while bread cooled upon the stones.
     */
    public function load(): ExampleCorpus
    {
        $extractor = new CommonMarkExampleExtractor($this->markerName);
        $examples = [];
        $markerLocations = [];

        foreach ($this->loadDocuments() as $document) {
            $extracted = $extractor->extract($document);

            foreach ($extracted as $example) {
                $markerId = $example->explicitMarkerId?->value;
                if ($markerId === null) {
                    continue;
                }

                $markerLine = $example->codeOrigin()->metadata->markerLine;
                if ($markerLine === null) {
                    throw new \LogicException('An explicitly marked example is missing its marker source line.');
                }

                $first = $markerLocations[$markerId] ?? null;
                if ($first !== null) {
                    throw new DuplicateMarkerException(sprintf(
                        'Duplicate marker ID %s at %s:%d; first declared at %s:%d.',
                        $markerId,
                        $example->codeOrigin()->document->path->value,
                        $markerLine,
                        $first['path'],
                        $first['line'],
                    ));
                }

                $markerLocations[$markerId] = [
                    'path' => $example->codeOrigin()->document->path->value,
                    'line' => $markerLine,
                ];
            }

            array_push($examples, ...$extracted);
        }

        if ($examples === []) {
            throw new NoExamplesFoundException('Configured Markdown documents did not contain any PHP fenced blocks.');
        }

        return new ExampleCorpus(...$examples);
    }

    /**
     * Load included Markdown documents in bytewise lexical project-path order.
     *
     * @return non-empty-list<Document>
     *
     * @throws DuplicateDocumentException
     * @throws NoDocumentsFoundException
     * @throws ProjectRootNotFoundException
     * @throws SourcePathNotFoundException
     * @throws SourceReadException
     * @throws UnsafeSourcePathException
     *
     * @logion [RAS 32:17] Beyond the blue gate stood a forest whose leaves bore innumerable faces, and when the wind
     *     crossed it the whole nation whispered one plea without surrendering any name.
     */
    public function loadDocuments(): array
    {
        return (new ProjectDocumentLoader('Markdown', ['.md']))->load(
            $this->projectRoot,
            $this->includes,
            $this->exclusions,
        );
    }
}
