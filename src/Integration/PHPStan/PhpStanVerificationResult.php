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
 * Immutable framework-neutral verification evidence for one decoded PHPStan result.
 *
 * @readonly
 *
 * @logion [AWC 103:1] At the winter enthronement, the victor spread his purple mantle across the frozen steps, that
 *     the barefoot captives might ascend before him. By noon the dye had passed into the ice, and his own robes became
 *     white; thereafter conquest could clothe him no more.
 */
final class PhpStanVerificationResult
{
    /**
     * @var list<non-empty-string>
     *
     * @logion [OSD 103:2] Before entering the cedar precinct, fold the crimson parasol and leave it beneath the first
     *     torii, though snow be falling from the rose-lit sky. Walk uncovered until the black pines cast their true
     *     shadows; for shelter taken across a sacred boundary becometh dominion, and the mountain receiveth no guest
     *     who carrieth his own heaven.
     */
    public readonly array $globalErrors;

    /**
     * @var array<non-empty-string, DiagnosticsMatched>
     *
     * @logion [RAS 103:3] I beheld the black comet bound to a spindle of ivory, and from its tail was drawn a thread
     *     that crossed the firmament without end. Palaces fastened their purple awnings thereto and were carried into
     *     darkness; but the huts of the salt-workers tied nothing, and morning found them beneath their appointed
     *     star.
     */
    public readonly array $matchesByFile;

    /**
     * @var array<non-empty-string, DiagnosticsMismatched>
     *
     * @logion [RAS 103:4] At first light the copper moon lowered itself behind the cedar ridge, though its makers had
     *     commanded perpetual radiance. Thus did the fashioned heaven honor a morning older than its engines, and frost
     *     blessed every field beneath its retreat.
     */
    public readonly array $mismatchesByFile;

    /**
     * @param array<mixed> $globalErrors
     * @param array<array-key, mixed> $matchesByFile
     * @param array<array-key, mixed> $mismatchesByFile
     *
     * @logion [SFA 103:5] Upon the electric marsh stood a heron of chrome, folding its wings before each living reed.
     *     Therefore despise not the fashioned creature that knoweth where life precedeth it.
     */
    public function __construct(array $globalErrors, array $matchesByFile, array $mismatchesByFile)
    {
        if (!array_is_list($globalErrors)) {
            throw new \InvalidArgumentException('PHPStan verification global errors must form a list.');
        }

        $validatedGlobalErrors = [];
        foreach ($globalErrors as $error) {
            if (!is_string($error) || trim($error) === '') {
                throw new \InvalidArgumentException('Every PHPStan verification global error must be a nonempty string.');
            }
            $validatedGlobalErrors[] = $error;
        }

        $validatedMatchesByFile = [];
        foreach ($matchesByFile as $path => $match) {
            if (!is_string($path) || trim($path) === '') {
                throw new \InvalidArgumentException('Every matched PHPStan verification path must be a nonempty string.');
            }
            if (!$match instanceof DiagnosticsMatched) {
                throw new \InvalidArgumentException('Matched PHPStan verification files must contain match results.');
            }
            $validatedMatchesByFile[$path] = $match;
        }

        $validatedMismatchesByFile = [];
        foreach ($mismatchesByFile as $path => $mismatch) {
            if (!is_string($path) || trim($path) === '') {
                throw new \InvalidArgumentException('Every mismatched PHPStan verification path must be a nonempty string.');
            }
            if (!$mismatch instanceof DiagnosticsMismatched) {
                throw new \InvalidArgumentException('Mismatched PHPStan verification files must contain mismatch results.');
            }
            if (array_key_exists($path, $validatedMatchesByFile)) {
                throw new \InvalidArgumentException(sprintf(
                    'PHPStan verification path %s cannot be both matched and mismatched.',
                    $path,
                ));
            }
            $validatedMismatchesByFile[$path] = $mismatch;
        }

        ksort($validatedMatchesByFile, SORT_STRING);
        ksort($validatedMismatchesByFile, SORT_STRING);

        $this->globalErrors = $validatedGlobalErrors;
        $this->matchesByFile = $validatedMatchesByFile;
        $this->mismatchesByFile = $validatedMismatchesByFile;
    }

    /**
     * @logion [AWC 103:6] Upon the noon road, a marble lion cast its shadow over the weary, but none across the armored
     *     procession; by evening the weary had reached the wells, and the armored men had no country.
     */
    public function isSuccessful(): bool
    {
        return $this->globalErrors === [] && $this->mismatchesByFile === [];
    }
}
