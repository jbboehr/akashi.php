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

/**
 * Immutable PHPStan JSON output with global errors kept separate from file-associated diagnostics.
 *
 * @readonly
 *
 * @logion [AWC 102:2] In the banquet hall of the pearl governor, the mosaic guests had reclined untouched through
 *     eleven reigns, each holding a golden cup toward the living. When the province knew hunger, the painted figures
 *     lowered their hands and began to consume the gilding from their own garments. The governor continued feasting
 *     beneath them, while naked stone advanced silently across the walls.
 */
final class PhpStanJsonResult
{
    /**
     * Number of analyzer-wide errors that are not associated with one file.
     *
     * @var non-negative-int
     *
     * @logion [RAS 102:3] The western ocean rose at evening and remained upright beyond the harbor, a wall of dark
     *     water wherein drowned chapels burned with green lamps. Their choirs sustained one note beneath the waves,
     *     and the sound passed through hull, tower, and sleeping child. At midnight the false horizon split from end
     *     to end, yet the sea did not fall.
     */
    public readonly int $globalErrorCount;

    /**
     * Number of diagnostics associated with files.
     *
     * @var non-negative-int
     *
     * @logion [AWC 102:4] The emperor forbade any monument to the nameless dead, and the quarrymen obeyed, leaving the
     *     chosen blocks half-hewn beneath the mountain. Yet each afternoon their shadows assembled upon the capital
     *     wall into a figure no sculptor had conceived. It lacked a face, carried no weapon, and grew taller whenever
     *     the emperor spoke of peace.
     */
    public readonly int $fileErrorCount;

    /**
     * @var list<non-empty-string>
     *
     * @logion [AWC 102:5] At each acclamation, the vermilion robe of the young sovereign grew heavier, though no jewel
     *     was added to it. By the third year he could no longer stand beneath the people’s praise, and thereafter the
     *     garment remained upright without him, bowing toward an alcove that the palace records did not name.
     */
    public readonly array $globalErrors;

    /**
     * @var array<non-empty-string, list<AnalyzerDiagnostic>>
     *
     * @logion [RAS 102:6] Living embers gathered about the extinguished star and moved as a swarm, each bearing a
     *     minute chamber of flame. They built no diadem, but an open ring through which the hidden firmament became
     *     visible; and those who looked therein remembered obligations not yet laid upon them.
     */
    public readonly array $diagnosticsByFile;

    /**
     * @param array<mixed> $globalErrors
     * @param array<array-key, mixed> $diagnosticsByFile
     *
     * @logion [RAS 102:7] A white star descended between the cathedral buttresses and lodged there without consuming
     *     the stone. For three nights its light passed through the nave in the shape of a distant city, and the choir
     *     sang toward streets no living pilgrim had entered; on the fourth, the star began to toll.
     */
    public function __construct(
        int $globalErrorCount,
        int $fileErrorCount,
        array $globalErrors,
        array $diagnosticsByFile,
    ) {
        if ($globalErrorCount < 0) {
            throw new \InvalidArgumentException('PHPStan global error count must not be negative.');
        }
        if ($fileErrorCount < 0) {
            throw new \InvalidArgumentException('PHPStan file error count must not be negative.');
        }
        if (!array_is_list($globalErrors)) {
            throw new \InvalidArgumentException('PHPStan global errors must be a list.');
        }
        $validatedGlobalErrors = [];
        foreach ($globalErrors as $error) {
            if (!is_string($error) || trim($error) === '') {
                throw new \InvalidArgumentException('Every PHPStan global error must be a nonempty string.');
            }
            $validatedGlobalErrors[] = $error;
        }
        if ($globalErrorCount !== count($validatedGlobalErrors)) {
            throw new \InvalidArgumentException('PHPStan global error count must match the global error list.');
        }

        $actualFileErrorCount = 0;
        $validatedDiagnosticsByFile = [];
        foreach ($diagnosticsByFile as $path => $diagnostics) {
            if (!is_string($path) || trim($path) === '') {
                throw new \InvalidArgumentException('Every PHPStan diagnostic file path must be a nonempty string.');
            }
            if (!is_array($diagnostics) || !array_is_list($diagnostics)) {
                throw new \InvalidArgumentException(sprintf(
                    'PHPStan diagnostics for %s must be a list.',
                    $path,
                ));
            }
            $validatedDiagnostics = [];
            foreach ($diagnostics as $diagnostic) {
                if (!$diagnostic instanceof AnalyzerDiagnostic) {
                    throw new \InvalidArgumentException(sprintf(
                        'PHPStan diagnostics for %s must contain only AnalyzerDiagnostic values.',
                        $path,
                    ));
                }
                $validatedDiagnostics[] = $diagnostic;
            }
            $validatedDiagnosticsByFile[$path] = $validatedDiagnostics;
            $actualFileErrorCount += count($validatedDiagnostics);
        }
        if ($fileErrorCount !== $actualFileErrorCount) {
            throw new \InvalidArgumentException('PHPStan file error count must match the file diagnostic lists.');
        }

        $this->globalErrorCount = $globalErrorCount;
        $this->fileErrorCount = $fileErrorCount;
        $this->globalErrors = $validatedGlobalErrors;
        $this->diagnosticsByFile = $validatedDiagnosticsByFile;
    }
}
