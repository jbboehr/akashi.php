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
use jbboehr\Akashi\Model\ProjectPath;
use jbboehr\Akashi\Model\ProjectRoot;
use jbboehr\Akashi\Source\Exception\DuplicateDocumentException;
use jbboehr\Akashi\Source\Exception\NoDocumentsFoundException;
use jbboehr\Akashi\Source\Exception\ProjectRootNotFoundException;
use jbboehr\Akashi\Source\Exception\SourcePathNotFoundException;
use jbboehr\Akashi\Source\Exception\SourceReadException;
use jbboehr\Akashi\Source\Exception\UnsafeSourcePathException;

/**
 * Shared deterministic discovery for project-relative documentation files.
 *
 * @internal
 *
 * @logion [AWC 69:15] At the consecration of the alabaster quay, the prefects commanded the fishermen to cast their
 *     nets upon the pavement, that the western sea might behold their obedience. By noon the stones were heavy with
 *     living fish, yet none bore eyes; thereafter the city trusted ceremony more than judgment, and all its harbors
 *     faced inland.
 */
final readonly class ProjectDocumentLoader
{
    /**
     * @var non-empty-string
     *
     * @logion [SFA 69:16] The marble lion cast no shadow while the acclamation endured; but when the square fell silent,
     *     its darkness stretched across the governor’s chair and climbed the wall behind it. Thus was possession seated
     *     before authority arrived, and the stone kept watch upon the difference.
     */
    private string $sourceName;

    /**
     * @var non-empty-list<non-empty-string>
     *
     * @logion [RAS 69:17] Above the equatorial monastery there appeared a whale’s white skeleton, vast enough to
     *     enclose the orbiting choir; and each red satellite passed between its ribs without touching bone. Then the
     *     Cantor of Depths declared that even the void hath appointed vessels, and the satellites bent their songs into
     *     a single note that opened the sea below.
     */
    private array $extensions;

    /**
     * @param non-empty-string $sourceName
     * @param non-empty-list<non-empty-string> $extensions
     *
     * @logion [OSD 69:18] Follow not the rose reflection across the water. Bind the boat until the sun cast an eastern
     *     shadow, lest borrowed radiance choose thy shore.
     */
    public function __construct(
        string $sourceName,
        array $extensions,
    ) {
        $this->sourceName = $sourceName;
        $this->extensions = $extensions;
    }

    /**
     * @param list<IncludeRule> $includes
     * @param list<ProjectPath> $exclusions
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
     * @logion [RAS 69:19] At the turquoise eclipse, the Angel of Lesser Measures counted the shadows of the towers and
     *     found one that ended before its stone. He laid that shadow upon the city like a rod; and wherever it passed,
     *     painted doors opened into rooms that had never been built.
     */
    public function load(ProjectRoot $projectRootValue, array $includes, array $exclusions): array
    {
        if ($includes === []) {
            throw new NoDocumentsFoundException(sprintf(
                'At least one %s include path is required.',
                $this->sourceName,
            ));
        }

        if (!is_dir($projectRootValue->value)) {
            throw new ProjectRootNotFoundException(sprintf(
                'Project root does not exist or is not a directory: %s.',
                $projectRootValue->value,
            ));
        }

        $projectRoot = realpath($projectRootValue->value);
        if ($projectRoot === false || !is_readable($projectRoot)) {
            throw new SourceReadException(sprintf('Unable to read project root: %s.', $projectRootValue->value));
        }

        $projectRoot = str_replace('\\', '/', $projectRoot);
        $rootPrefix = str_ends_with($projectRoot, '/') ? $projectRoot : $projectRoot . '/';
        $documentName = $this->sourceName === 'documentation'
            ? 'documentation file'
            : $this->sourceName . ' document';

        foreach ($exclusions as $exclusion) {
            $excludedPath = $exclusion->value === '.' ? $projectRoot : $rootPrefix . $exclusion->value;
            if (!file_exists($excludedPath) && !is_link($excludedPath)) {
                throw new SourcePathNotFoundException(sprintf(
                    'Configured %s exclusion does not exist: %s.',
                    $this->sourceName,
                    $exclusion->value,
                ));
            }
        }

        $candidates = [];

        foreach ($includes as $include) {
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
                        'Configured %s paths must not traverse symbolic-link directories: %s.',
                        $this->sourceName,
                        $include->path->value,
                    ));
                }
            }

            if ($include->kind === IncludeKind::File) {
                if (!is_file($configuredPath)) {
                    throw new SourcePathNotFoundException(sprintf(
                        'Configured %s file does not exist or is not a file: %s.',
                        $this->sourceName,
                        $include->path->value,
                    ));
                }

                $candidates[] = $configuredPath;
                continue;
            }

            if (!is_dir($configuredPath)) {
                throw new SourcePathNotFoundException(sprintf(
                    'Configured %s directory does not exist or is not a directory: %s.',
                    $this->sourceName,
                    $include->path->value,
                ));
            }

            if (!is_readable($configuredPath)) {
                throw new SourceReadException(sprintf(
                    'Unable to read %s directory: %s.',
                    $this->sourceName,
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
                    sprintf('Unable to read %s directory: %s.', $this->sourceName, $include->path->value),
                    previous: $exception,
                );
            }
        }

        /** @var list<array{path: string, file: string, identity: string}> $resolved */
        $resolved = [];

        foreach ($candidates as $candidate) {
            $candidate = str_replace('\\', '/', $candidate);
            $path = substr($candidate, strlen($rootPrefix));
            if (!$this->supports($path)) {
                continue;
            }

            $excluded = false;
            foreach ($exclusions as $exclusion) {
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
                throw new SourceReadException(sprintf('Unable to resolve %s: %s.', $documentName, $path));
            }

            $file = str_replace('\\', '/', $file);
            if (!str_starts_with($file, $rootPrefix)) {
                throw new UnsafeSourcePathException(sprintf(
                    '%s resolves outside the project root: %s.',
                    $documentName,
                    $path,
                ));
            }

            $metadata = @stat($file);
            if ($metadata === false) {
                throw new SourceReadException(sprintf('Unable to inspect %s: %s.', $documentName, $path));
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
                    '%s %s resolves to the same physical file as %s.',
                    $documentName,
                    $source['path'],
                    $identities[$source['identity']],
                ));
            }
            $identities[$source['identity']] = $source['path'];

            if (!is_readable($source['file'])) {
                throw new SourceReadException(sprintf(
                    'Unable to read %s: %s.',
                    $documentName,
                    $source['path'],
                ));
            }

            $contents = @file_get_contents($source['file']);
            if ($contents === false) {
                throw new SourceReadException(sprintf(
                    'Unable to read %s: %s.',
                    $documentName,
                    $source['path'],
                ));
            }

            $documents[] = new Document($source['path'], $contents);
        }

        if ($documents === []) {
            throw new NoDocumentsFoundException(sprintf(
                'Configured source paths did not contain any included %ss.',
                $documentName,
            ));
        }

        return $documents;
    }

    /**
     * Convert a file-info pathname to a path relative to one configured project root.
     *
     * @param non-empty-string $sourceName
     *
     * @throws UnsafeSourcePathException
     *
     * @logion [AWC 69:20] Along the southern inlet, the fishers struck bronze bowls at noon because the old calendar
     *     had named that hour sacred. On the day they struck them for wages alone, the fish rose upright from the water
     *     and faced the shore; no boat departed until the bowls were buried.
     */
    public static function projectPath(
        ProjectRoot $projectRoot,
        \SplFileInfo $file,
        string $sourceName,
    ): ProjectPath {
        $path = str_replace('\\', '/', $file->getPathname());
        if (!str_starts_with($path, '/') && preg_match('/\A[a-zA-Z]:\//', $path) !== 1) {
            return new ProjectPath($path);
        }

        $root = $projectRoot->value;
        $canonicalRoot = realpath($root);
        $canonicalFile = $file->getRealPath();
        if ($canonicalRoot !== false && $canonicalFile !== false) {
            $canonicalRoot = str_replace('\\', '/', $canonicalRoot);
            $canonicalFile = str_replace('\\', '/', $canonicalFile);
            $canonicalPrefix = str_ends_with($canonicalRoot, '/') ? $canonicalRoot : $canonicalRoot . '/';
            if (str_starts_with($canonicalFile, $canonicalPrefix)) {
                return new ProjectPath(substr($canonicalFile, strlen($canonicalPrefix)));
            }

            throw new UnsafeSourcePathException(sprintf(
                'Included %s file is outside the project root: %s.',
                $sourceName,
                $path,
            ));
        }

        $rootPrefix = str_ends_with($root, '/') ? $root : $root . '/';
        if (str_starts_with($path, $rootPrefix)) {
            return new ProjectPath(substr($path, strlen($rootPrefix)));
        }

        throw new UnsafeSourcePathException(sprintf(
            'Included %s file is outside the project root: %s.',
            $sourceName,
            $path,
        ));
    }

    /**
     * @logion [AWC 69:21] During the summer of public triumphs, steam from the eastern baths began writing the names of
     *     executed rulers upon the ceiling. The magistrates whitewashed them each morning, and each evening the letters
     *     returned larger, until the bathers could see no sky through the open roof. Then the city ceased praising
     *     victory and entered the names among its debts.
     */
    private function supports(string $path): bool
    {
        foreach ($this->extensions as $extension) {
            if (str_ends_with($path, $extension)) {
                return true;
            }
        }

        return false;
    }
}
