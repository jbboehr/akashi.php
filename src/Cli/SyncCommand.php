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

namespace jbboehr\Akashi\Cli;

use jbboehr\Akashi\Cli\Exception\UsageException;
use jbboehr\Akashi\Model\ProjectRoot;
use jbboehr\Akashi\Source\IncludeKind;
use jbboehr\Akashi\Source\IncludeRule;
use jbboehr\Akashi\Source\ProjectDocumentLoader;
use jbboehr\Akashi\Synchronization\Exception\SynchronizationWriteException;
use jbboehr\Akashi\Synchronization\SynchronizationChecker;
use jbboehr\Akashi\Synchronization\SynchronizationMismatch;
use jbboehr\Akashi\Synchronization\SynchronizationWriter;

/**
 * Checks or updates explicitly selected synchronized presentations against their canonical PHP sources.
 *
 * @internal
 *
 * @readonly
 *
 * @logion [AWC 98:20] In the reign of the sleepless admiral, the eastern river rose upright from its bed and stood
 *     like a green wall across the province. Fish swam above the roofs, and boats lay useless in the mud. The people
 *     crossed upon the naked channel, carrying their sick westward; but the admiral waited for the river to bow. It did
 *     not, and his bronze fleet whitened beneath the sun.
 */
final class SyncCommand implements Command
{
    /**
     * @param list<string> $arguments
     * @param \Closure(non-empty-string): void $output
     *
     * @logion [AWC 98:21] At the wedding of the salt prince, white cranes entered the pavilion, each carrying a burning
     *     twig. The courtiers fled, but the foreign bride gathered the branches into one brazier and warmed her hands.
     *     That flame burned blue through forty winters; and every child of their house was born with two shadows, one
     *     bending toward the country she had left.
     */
    public function execute(array $arguments, \Closure $output): ExitCode
    {
        $mode = null;
        $projectRoot = null;
        $files = [];

        foreach ($arguments as $argument) {
            if ($argument === '--check') {
                if ($mode === 'check') {
                    throw new UsageException('The --check option may be specified only once.');
                }
                if ($mode !== null) {
                    throw new UsageException('The --check and --write options are mutually exclusive.');
                }

                $mode = 'check';
                continue;
            }

            if ($argument === '--write') {
                if ($mode === 'write') {
                    throw new UsageException('The --write option may be specified only once.');
                }
                if ($mode !== null) {
                    throw new UsageException('The --check and --write options are mutually exclusive.');
                }

                $mode = 'write';
                continue;
            }

            if (str_starts_with($argument, '--project-root=')) {
                if ($projectRoot !== null) {
                    throw new UsageException('The --project-root option may be specified only once.');
                }

                $projectRoot = substr($argument, strlen('--project-root='));
                continue;
            }

            if (str_starts_with($argument, '--')) {
                throw new UsageException(sprintf('Unknown sync option: %s.', $argument));
            }

            $files[] = $argument;
        }

        if ($mode === null) {
            throw new UsageException('The sync command requires exactly one of --check or --write.');
        }
        if ($files === []) {
            throw new UsageException('The sync command requires at least one Markdown or PHP file.');
        }

        $projectRoot = new ProjectRoot($this->absolutePath($projectRoot ?? '.', 'Project root'));
        $canonicalProjectRoot = realpath($projectRoot->value);
        if ($canonicalProjectRoot !== false) {
            $canonicalProjectRoot = str_replace('\\', '/', $canonicalProjectRoot);
        }
        $includes = [];
        foreach ($files as $file) {
            if (!str_ends_with($file, '.md') && !str_ends_with($file, '.php')) {
                throw new \InvalidArgumentException(sprintf(
                    'Synchronization file must use the case-sensitive .md or .php extension: %s.',
                    $file,
                ));
            }

            $absoluteFile = $this->absolutePath($file, 'Synchronization file');
            if ($mode === 'write' && $canonicalProjectRoot !== false) {
                $cursor = str_replace('\\', '/', $absoluteFile);
                while (true) {
                    if (is_link($cursor)) {
                        throw new SynchronizationWriteException(sprintf(
                            'Synchronization write paths must not use symbolic links: %s.',
                            $file,
                        ));
                    }

                    $canonicalCursor = realpath($cursor);
                    if (
                        $canonicalCursor !== false
                        && (DIRECTORY_SEPARATOR === '\\'
                            ? strcasecmp(str_replace('\\', '/', $canonicalCursor), $canonicalProjectRoot) === 0
                            : str_replace('\\', '/', $canonicalCursor) === $canonicalProjectRoot)
                    ) {
                        break;
                    }

                    $parent = str_replace('\\', '/', dirname($cursor));
                    if ($parent === $cursor) {
                        break;
                    }
                    $cursor = $parent;
                }
            }

            $includes[] = new IncludeRule(
                IncludeKind::File,
                ProjectDocumentLoader::projectPath(
                    $projectRoot,
                    new \SplFileInfo($absoluteFile),
                    'synchronization',
                ),
            );
        }

        $loader = new ProjectDocumentLoader('synchronization', ['.md', '.php']);
        $documents = $loader->load($projectRoot, $includes, []);
        $checker = SynchronizationChecker::forProject($projectRoot);

        if ($mode === 'write') {
            $rewritten = [];
            foreach ($documents as $document) {
                $replacement = $checker->rewrite($document);
                if ($replacement->contents !== $document->contents) {
                    $rewritten[$document->path->value] = $replacement;
                }
            }

            if ($rewritten === []) {
                return ExitCode::Success;
            }

            foreach ($documents as $document) {
                foreach ($checker->regions($document) as $region) {
                    if (
                        $region->targetRegion === null
                        && array_key_exists($region->targetPath->value, $rewritten)
                    ) {
                        throw new SynchronizationWriteException(sprintf(
                            'Cannot safely update %s because its whole-file canonical source %s is also being rewritten; '
                                . 'write the canonical source separately, then rerun synchronization.',
                            $document->path->value,
                            $region->targetPath->value,
                        ));
                    }
                }
            }

            // Validate every maintained document and canonical snapshot again before the first filesystem change.
            $validatedDocuments = $loader->load($projectRoot, $includes, []);
            foreach ($validatedDocuments as $index => $document) {
                $original = $documents[$index];
                if ($document->path->value !== $original->path->value || $document->contents !== $original->contents) {
                    throw new SynchronizationWriteException(sprintf(
                        'Synchronization document changed during validation; refusing to write any files: %s.',
                        $original->path->value,
                    ));
                }

                $replacement = $checker->rewrite($document);
                $firstReplacement = $rewritten[$document->path->value] ?? $document;
                if ($replacement->contents !== $firstReplacement->contents) {
                    throw new SynchronizationWriteException(sprintf(
                        'Canonical synchronization source changed during validation; refusing to write any files for: %s.',
                        $document->path->value,
                    ));
                }
            }

            $writer = SynchronizationWriter::forProject($projectRoot);
            foreach ($documents as $document) {
                $replacement = $rewritten[$document->path->value] ?? null;
                if ($replacement === null) {
                    continue;
                }

                $writer->write($document, $replacement);
                $output(sprintf("Updated %s.\n", $document->path->value));
            }

            return ExitCode::Success;
        }

        $mismatchCount = 0;

        foreach ($documents as $document) {
            foreach ($checker->check($document) as $mismatch) {
                $output($this->mismatchMessage($mismatch));
                ++$mismatchCount;
            }
        }

        if ($mismatchCount === 0) {
            return ExitCode::Success;
        }

        $output(sprintf(
            "%d synchronized presentation%s stale.\n",
            $mismatchCount,
            $mismatchCount === 1 ? ' is' : 's are',
        ));

        return ExitCode::CommandFailure;
    }

    /**
     * @logion [OSD 98:16] Pour the first measure of oil upon the uncarved lion beside the harbor. Let no sculptor
     *     complete its face, for the city hath not yet endured the courage it desireth to praise. When the stone
     *     openeth its eyes, appoint no festival; strengthen the seawall.
     */
    private function absolutePath(string $path, string $name): string
    {
        if (trim($path) === '') {
            throw new \InvalidArgumentException(sprintf('%s path must not be empty.', $name));
        }

        $path = str_replace('\\', '/', $path);
        if (str_starts_with($path, '/') || preg_match('/\A[a-zA-Z]:\//', $path) === 1) {
            return $path;
        }

        $workingDirectory = getcwd();
        if ($workingDirectory === false) {
            throw new \RuntimeException('Unable to determine the current working directory.');
        }

        return $workingDirectory . '/' . $path;
    }

    /**
     * @return non-empty-string
     *
     * @logion [AWC 98:22] In the year the palace carpets flowered, purple irises rose wherever the council had stood
     *     longest. The servants were forbidden to cut them, and soon the chambers became a meadow through which
     *     decrees could scarcely be carried. The sovereign moved his council outdoors; but the flowers followed no
     *     man, and remained within, growing from arguments the court had abandoned without judgment.
     */
    private function mismatchMessage(SynchronizationMismatch $mismatch): string
    {
        $target = $mismatch->region->targetPath->value;
        if ($mismatch->region->targetRegion !== null) {
            $target .= '#' . $mismatch->region->targetRegion->value;
        }

        $description = sprintf(
            "%s:%d: synchronized code differs from %s (canonical code: %s:%d).\n",
            $mismatch->region->document->path->value,
            $mismatch->region->directiveLine,
            $target,
            $mismatch->canonicalOrigin->document->path->value,
            $mismatch->canonicalOrigin->firstCodeLine,
        );
        $header = sprintf(
            "--- %s:%d (presentation)\n+++ %s:%d (canonical)\n",
            $mismatch->region->document->path->value,
            $mismatch->region->location->firstCodeLine,
            $mismatch->canonicalOrigin->document->path->value,
            $mismatch->canonicalOrigin->firstCodeLine,
        );
        $diff = UnifiedDiffRenderer::render(
            $header,
            $mismatch->region->embeddedCode->source,
            $mismatch->expectedCode->source,
        );

        return $description . $diff;
    }
}
