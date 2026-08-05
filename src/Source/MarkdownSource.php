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
use jbboehr\Akashi\Markdown\Exception\DuplicateMarkerException;
use jbboehr\Akashi\Model\MarkerName;
use jbboehr\Akashi\Model\ProjectPath;
use jbboehr\Akashi\Model\ProjectRoot;
use jbboehr\Akashi\Source\Exception\DuplicateDocumentException;
use jbboehr\Akashi\Source\Exception\NoExamplesFoundException;
use jbboehr\Akashi\Source\Exception\NoDocumentsFoundException;
use jbboehr\Akashi\Source\Exception\ProjectRootNotFoundException;
use jbboehr\Akashi\Source\Exception\SourcePathNotFoundException;
use jbboehr\Akashi\Source\Exception\SourceReadException;
use jbboehr\Akashi\Source\Exception\UnsafeSourcePathException;

/**
 * Immutable configuration for deterministic project-relative Markdown document discovery.
 *
 * @logion [RAS 31:24] Beneath the glass mountain two processions appeared, one ascending and one descending, yet every
 *     pilgrim bore the same wound upon the left hand.
 */
final readonly class MarkdownSource
{
    /**
     * @logion [SFA 30:7] The sealed flute passed through five courts without a note, but in the hut of the charcoal
     *     burner it answered the evening wind and taught his daughters the lament of queens.
     */
    public ProjectRoot $projectRoot;

    /**
     * @var list<IncludeRule>
     *
     * @logion [RAS 33:21] A wheel of violet fire descended behind the cedar ridge, and every abandoned milestone spoke
     *     the name of a kingdom that would not be founded for seven generations.
     */
    private array $includes;

    /**
     * @var list<ProjectPath>
     *
     * @logion [AWC 34:3] The keeper of the eastern hospice wrote every guest upon a strip of linen, then bound the strips
     *     into a sail for the vessel that would carry their descendants home.
     */
    private array $exclusions;

    /**
     * @logion [AWC 48:16] The keeper of the hill shrine planted rice in three narrow terraces after the valley fields
     *     were taken by war. His harvest filled no granary, yet each returning household received one bowl and knew
     *     where to rebuild. Small provision may preserve the shape of abundance.
     */
    private ?MarkerName $markerName;

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
     * @logion [SFA 30:21] The first stars of autumn appeared within the empty cistern, and the villagers gathered at its
     *     rim to hear rain remembered by stone before it returned from heaven.
     */
    public function includeFile(ProjectPath|string $path): self
    {
        return new self(
            $this->projectRoot,
            [...$this->includes, new IncludeRule(
                IncludeKind::File,
                is_string($path) ? new ProjectPath($path) : $path,
            )],
            $this->exclusions,
            $this->markerName,
        );
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
     * @throws NoDocumentsFoundException
     * @throws NoExamplesFoundException
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

                $markerLine = $example->location->metadata->markerLine;
                if ($markerLine === null) {
                    throw new \LogicException('An explicitly marked example is missing its marker source line.');
                }

                $first = $markerLocations[$markerId] ?? null;
                if ($first !== null) {
                    throw new DuplicateMarkerException(sprintf(
                        'Duplicate marker ID %s at %s:%d; first declared at %s:%d.',
                        $markerId,
                        $example->document->path->value,
                        $markerLine,
                        $first['path'],
                        $first['line'],
                    ));
                }

                $markerLocations[$markerId] = [
                    'path' => $example->document->path->value,
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
        if ($this->includes === []) {
            throw new NoDocumentsFoundException('At least one Markdown include path is required.');
        }

        if (!is_dir($this->projectRoot->value)) {
            throw new ProjectRootNotFoundException(sprintf(
                'Project root does not exist or is not a directory: %s.',
                $this->projectRoot->value,
            ));
        }

        $projectRoot = realpath($this->projectRoot->value);
        if ($projectRoot === false || !is_readable($projectRoot)) {
            throw new SourceReadException(sprintf('Unable to read project root: %s.', $this->projectRoot->value));
        }

        $projectRoot = str_replace('\\', '/', $projectRoot);
        $rootPrefix = str_ends_with($projectRoot, '/') ? $projectRoot : $projectRoot . '/';

        foreach ($this->exclusions as $exclusion) {
            $excludedPath = $exclusion->value === '.' ? $projectRoot : $rootPrefix . $exclusion->value;
            if (!file_exists($excludedPath) && !is_link($excludedPath)) {
                throw new SourcePathNotFoundException(sprintf(
                    'Configured Markdown exclusion does not exist: %s.',
                    $exclusion->value,
                ));
            }
        }

        $candidates = [];

        foreach ($this->includes as $include) {
            $configuredPath = $include->path->value === '.'
                ? $projectRoot
                : $rootPrefix . $include->path->value;
            $segments = $include->path->value === '.' ? [] : explode('/', $include->path->value);
            if ($include->kind === IncludeKind::File) {
                array_pop($segments);
            }

            $parentPath = $projectRoot;
            foreach ($segments as $segment) {
                $parentPath .= '/' . $segment;
                if (is_link($parentPath)) {
                    throw new UnsafeSourcePathException(sprintf(
                        'Configured Markdown paths must not traverse symbolic-link directories: %s.',
                        $include->path->value,
                    ));
                }
            }

            if ($include->kind === IncludeKind::File) {
                if (!is_file($configuredPath)) {
                    throw new SourcePathNotFoundException(sprintf(
                        'Configured Markdown file does not exist or is not a file: %s.',
                        $include->path->value,
                    ));
                }

                $candidates[] = $configuredPath;
                continue;
            }

            if (!is_dir($configuredPath)) {
                throw new SourcePathNotFoundException(sprintf(
                    'Configured Markdown directory does not exist or is not a directory: %s.',
                    $include->path->value,
                ));
            }

            if (!is_readable($configuredPath)) {
                throw new SourceReadException(sprintf(
                    'Unable to read Markdown directory: %s.',
                    $include->path->value,
                ));
            }

            try {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($configuredPath, \FilesystemIterator::SKIP_DOTS),
                );

                foreach ($iterator as $entry) {
                    if ($entry instanceof \SplFileInfo && $entry->isFile()) {
                        $candidates[] = $entry->getPathname();
                    }
                }
            } catch (\UnexpectedValueException $exception) {
                throw new SourceReadException(
                    sprintf('Unable to read Markdown directory: %s.', $include->path->value),
                    previous: $exception,
                );
            }
        }

        /** @var list<array{path: string, file: string, identity: string}> $resolved */
        $resolved = [];

        foreach ($candidates as $candidate) {
            $candidate = str_replace('\\', '/', $candidate);
            $path = substr($candidate, strlen($rootPrefix));
            if (!str_ends_with($path, '.md')) {
                continue;
            }

            $excluded = false;
            foreach ($this->exclusions as $exclusion) {
                if (
                    $exclusion->value === '.'
                    || $path === $exclusion->value
                    || str_starts_with($path, $exclusion->value . '/')
                ) {
                    $excluded = true;
                    break;
                }
            }
            if ($excluded) {
                continue;
            }

            $file = realpath($candidate);
            if ($file === false) {
                throw new SourceReadException(sprintf('Unable to resolve Markdown document: %s.', $path));
            }

            $file = str_replace('\\', '/', $file);
            if (!str_starts_with($file, $rootPrefix)) {
                throw new UnsafeSourcePathException(sprintf(
                    'Markdown document resolves outside the project root: %s.',
                    $path,
                ));
            }

            $metadata = @stat($file);
            if ($metadata === false) {
                throw new SourceReadException(sprintf('Unable to inspect Markdown document: %s.', $path));
            }

            $identity = $metadata['ino'] === 0
                ? $file
                : sprintf('%d:%d', $metadata['dev'], $metadata['ino']);
            $resolved[] = ['path' => $path, 'file' => $file, 'identity' => $identity];
        }

        usort(
            $resolved,
            static fn (array $left, array $right): int => strcmp($left['path'], $right['path']),
        );

        $identities = [];
        $documents = [];

        foreach ($resolved as $source) {
            if (isset($identities[$source['identity']])) {
                throw new DuplicateDocumentException(sprintf(
                    'Markdown document %s resolves to the same physical file as %s.',
                    $source['path'],
                    $identities[$source['identity']],
                ));
            }
            $identities[$source['identity']] = $source['path'];

            if (!is_readable($source['file'])) {
                throw new SourceReadException(sprintf('Unable to read Markdown document: %s.', $source['path']));
            }

            $contents = @file_get_contents($source['file']);
            if ($contents === false) {
                throw new SourceReadException(sprintf('Unable to read Markdown document: %s.', $source['path']));
            }

            $documents[] = new Document($source['path'], $contents);
        }

        if ($documents === []) {
            throw new NoDocumentsFoundException(
                'Configured source paths did not contain any included Markdown documents.',
            );
        }

        return $documents;
    }
}
