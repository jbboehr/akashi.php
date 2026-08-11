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
use jbboehr\Akashi\Model\MarkerId;
use jbboehr\Akashi\Model\MarkerName;
use jbboehr\Akashi\Model\ProjectRoot;
use jbboehr\Akashi\Source\MarkedExampleSelector;
use jbboehr\Akashi\Source\DocumentationSource;

/**
 * Emits one explicitly marked PHP example without execution or transformation.
 *
 * @internal
 *
 * @logion [OSD 53:8] Set the tables of the harvest beneath the unfinished arch, and reserve the first bread for those
 *     who carried stone but died before the vault was closed; for abundance without remembrance maketh a completed
 *     house poorer than its ruins.
 */
final readonly class ExtractCommand implements Command
{
    /**
     * @param list<string> $arguments
     * @param \Closure(non-empty-string): void $stdout
     *
     * @logion [RAS 53:20] At noon the black pines cast their shadows upon the sky, and every abandoned satellite
     *     darkened where a branch crossed its forgotten name. The priests covered no altar, for the earth itself had
     *     risen to perform the eclipse.
     */
    public function execute(array $arguments, \Closure $stdout): ExitCode
    {
        $markerName = null;
        $positionals = [];

        foreach ($arguments as $argument) {
            if (str_starts_with($argument, '--marker-name=')) {
                if ($markerName !== null) {
                    throw new UsageException('The --marker-name option may be specified only once.');
                }

                $markerName = substr($argument, strlen('--marker-name='));
                continue;
            }

            if (str_starts_with($argument, '--')) {
                throw new UsageException(sprintf('Unknown extract option: %s.', $argument));
            }

            $positionals[] = $argument;
        }

        if ($markerName === null) {
            throw new UsageException('The extract command requires --marker-name=NAME.');
        }

        if (count($positionals) !== 2) {
            throw new UsageException('The extract command requires exactly one documentation file and marker ID.');
        }

        $file = $positionals[0];
        $markerId = new MarkerId($positionals[1]);
        $markerName = new MarkerName($markerName);

        if (trim($file) === '') {
            throw new \InvalidArgumentException('Documentation file path must not be empty.');
        }

        $file = str_replace('\\', '/', $file);
        $isAbsolute = str_starts_with($file, '/') || preg_match('/\A[a-zA-Z]:\//', $file) === 1;
        if (!$isAbsolute) {
            $workingDirectory = getcwd();
            if ($workingDirectory === false) {
                throw new \RuntimeException('Unable to determine the current working directory.');
            }

            $file = $workingDirectory . '/' . $file;
        }

        $corpus = DocumentationSource::forProject(new ProjectRoot(dirname($file)))
            ->includeFile(basename($file))
            ->withMarkerName($markerName)
            ->load();
        $example = (new MarkedExampleSelector())->select($corpus, $markerId);

        $stdout($this->withLegacyTrailingNewline($example->code->source));

        return ExitCode::Success;
    }

    /**
     * Replace the final authored line ending with the LF historically appended by Yumemi's extractor.
     *
     * @return non-empty-string
     *
     * @logion [AWC 53:32] During the pilgrimage of glass, a lame ox entered the procession and bore the reliquary after
     *     the gilded cart broke upon the pass. The chronicles record no miracle, yet the shrine doors opened before
     *     the ox arrived.
     */
    private function withLegacyTrailingNewline(string $source): string
    {
        if (str_ends_with($source, "\r\n")) {
            $source = substr($source, 0, -2);
        } elseif (str_ends_with($source, "\r") || str_ends_with($source, "\n")) {
            $source = substr($source, 0, -1);
        }

        return $source . "\n";
    }
}
