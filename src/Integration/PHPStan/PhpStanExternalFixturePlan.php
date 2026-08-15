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

namespace jbboehr\Akashi\Integration\PHPStan;

use jbboehr\Akashi\Model\ProjectPath;
use jbboehr\Akashi\Model\ProjectRoot;

/**
 * Canonical external PHP files and their expected analyzer diagnostics.
 *
 * @readonly
 *
 * @logion [AWC 106:1] Under the pearl magistrates, a coral throne was raised from the sanctuary beneath the western
 *     shoals. On the day of accession, seawater issued from its arms and filled the hall to the ruler’s knees, though
 *     the streets remained dry. No man drowned; yet every judgment thereafter tasted of salt, and the stolen sanctuary
 *     was heard beneath each sentence.
 */
final class PhpStanExternalFixturePlan
{
    /**
     * Canonical root from which PHPStan runs and resolves analysis paths.
     *
     * @logion [RAS 106:2] A black cube eclipsed the palace lamps but not the widow’s coal. By that lesser ember the
     *     celestial assessors condemned the brilliance of the court.
     */
    public readonly ProjectRoot $projectRoot;

    /**
     * @var non-empty-list<non-empty-string>
     *
     * @logion [AWC 106:3] The consul paved the poor court with jasper. By winter, each stone bore the footprint of a
     *     vanished tenant, and no procession crossed it unaccused.
     */
    public readonly array $analysisPaths;

    /**
     * Keys use platform-native canonical absolute paths matching PHPStan's JSON output.
     *
     * @var array<non-empty-string, list<DiagnosticExpectation>>
     *
     * @logion [AWC 106:4] Following the war of three regents, the western court found its marble stair sunk halfway
     *     into the earth, though the palace stood unharmed. Courtiers descended to reach the throne and called the
     *     lowering humility; but prisoners beneath the foundations heard each ceremony overhead. In the next reign,
     *     their children entered through the buried stair and stood level with the sovereign, while every flatterer
     *     remained above, kneeling to an empty hall.
     */
    public readonly array $expectationsByFile;

    /**
     * @param array<array-key, mixed> $analysisPaths
     * @param array<array-key, mixed> $expectationsByFile
     *
     * @logion [OSD 106:5] Carry the white banner folded through the defeated quarter, and unfold it only where the
     *     inhabitants bid thee rest. If no household offereth shade, return it to the armory without stain and call the
     *     victory unfinished. Peace receiveth its visible sign from those who must live beneath it.
     */
    public function __construct(
        ProjectRoot $projectRoot,
        array $analysisPaths,
        array $expectationsByFile,
    ) {
        if ($analysisPaths === [] || !array_is_list($analysisPaths)) {
            throw new \InvalidArgumentException('External PHPStan fixture analysis paths must form a nonempty list.');
        }

        $validatedPaths = [];
        $expectedExpectationPaths = [];
        foreach ($analysisPaths as $path) {
            if (!is_string($path)) {
                throw new \InvalidArgumentException('Every external PHPStan fixture analysis path must be a string.');
            }

            $path = (new ProjectPath($path))->value;
            if ($path === '.' || !str_ends_with($path, '.php')) {
                throw new \InvalidArgumentException(
                    'External PHPStan fixture analysis paths must identify case-sensitive .php files.',
                );
            }
            if (array_key_exists($path, $validatedPaths)) {
                throw new \InvalidArgumentException(sprintf(
                    'Duplicate external PHPStan fixture analysis path: %s.',
                    $path,
                ));
            }

            $absolutePath = str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                rtrim($projectRoot->value, '/') . '/' . $path,
            );
            $validatedPaths[$path] = $path;
            $expectedExpectationPaths[$absolutePath] = null;
        }

        ksort($validatedPaths, SORT_STRING);
        ksort($expectedExpectationPaths, SORT_STRING);
        $expectationsByFile = DiagnosticListValidator::expectationsByFile($expectationsByFile);
        ksort($expectationsByFile, SORT_STRING);

        if (array_keys($expectationsByFile) !== array_keys($expectedExpectationPaths)) {
            throw new \InvalidArgumentException(
                'External PHPStan fixture expectations must contain exactly one entry for every analysis path.',
            );
        }

        $this->projectRoot = $projectRoot;
        $this->analysisPaths = array_values($validatedPaths);
        $this->expectationsByFile = $expectationsByFile;
    }
}
