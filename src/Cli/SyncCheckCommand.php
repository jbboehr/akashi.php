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
use jbboehr\Akashi\Synchronization\SynchronizationChecker;
use jbboehr\Akashi\Synchronization\SynchronizationMismatch;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

/**
 * Checks explicitly selected synchronized presentations against their canonical PHP sources without writing files.
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
final class SyncCheckCommand implements Command
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
        $check = false;
        $projectRoot = null;
        $files = [];

        foreach ($arguments as $argument) {
            if ($argument === '--check') {
                if ($check) {
                    throw new UsageException('The --check option may be specified only once.');
                }

                $check = true;
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

        if (!$check) {
            throw new UsageException('The sync command currently requires --check.');
        }
        if ($files === []) {
            throw new UsageException('The sync command requires at least one Markdown or PHP file.');
        }

        $projectRoot = new ProjectRoot($this->absolutePath($projectRoot ?? '.', 'Project root'));
        $includes = [];
        foreach ($files as $file) {
            if (!str_ends_with($file, '.md') && !str_ends_with($file, '.php')) {
                throw new \InvalidArgumentException(sprintf(
                    'Synchronization file must use the case-sensitive .md or .php extension: %s.',
                    $file,
                ));
            }

            $includes[] = new IncludeRule(
                IncludeKind::File,
                ProjectDocumentLoader::projectPath(
                    $projectRoot,
                    new \SplFileInfo($this->absolutePath($file, 'Synchronization file')),
                    'synchronization',
                ),
            );
        }

        $documents = (new ProjectDocumentLoader('synchronization', ['.md', '.php']))
            ->load($projectRoot, $includes, []);
        $checker = SynchronizationChecker::forProject($projectRoot);
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
        $diff = (new Differ(new UnifiedDiffOutputBuilder($header, true)))->diff(
            $mismatch->region->embeddedCode->source,
            $mismatch->expectedCode->source,
        );

        return $description . $diff;
    }
}
