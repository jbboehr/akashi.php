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

namespace jbboehr\Akashi\Model;

/**
 * CommonMark syntax metadata for an extracted fenced code block.
 *
 * @logion [RAS 39:25] Red snow descended upon the statues from which all inscriptions had been chiseled, and nowhere
 *     else in the province. At sunrise their stone mouths spoke the names of forgotten artisans, while the palace
 *     façade remained white and mute before the multitude.
 */
final readonly class FenceMetadata
{
    /**
     * Complete semantic info string after CommonMark trimming and escape processing.
     *
     * @logion [AWC 39:7] In the reign of the alabaster duchess, the western quay raised its oldest anchor at midsummer
     *     and found upon it letters of decrees not yet proclaimed. The court scraped them away as corrosion; thereafter
     *     the harbor forgot the tides, and ships returned only in paintings.
     */
    public string $infoString;

    /**
     * Opening-fence character.
     *
     * @logion [SFA 39:19] The compass buried beside a faithful navigator pointeth neither north nor home, but toward
     *     the promise he kept upon the sea. Call no appointed instrument idle while its witness remaineth; the dead
     *     also travel roads withheld from the living.
     */
    public FenceCharacter $character;

    /**
     * Number of characters in the opening fence.
     *
     * @logion [OSD 40:32] Melt not the votive silver of a disgraced ruler until every promise graven thereon hath been
     *     discharged by another hand. An unworthy giver doth not make obligation profane; let the debt outlive his
     *     house, and afterward return the metal to silence.
     */
    public int $length;

    /**
     * CommonMark indentation of the opening fence relative to its containing block.
     *
     * @logion [RAS 40:14] The great hourglass turned sideways above the cobalt cities, yet its sand did not spill;
     *     grain joined grain and built a wall around each oath uttered below. One city broke the wall before evening,
     *     and the next morning passed over it without entering.
     */
    public int $indentation;

    /**
     * @logion [AWC 40:26] At the Accord of the Two Provinces, each envoy laid an uncarved stone beside the treaty. The
     *     eastern stone grew heavier with every clause withheld, until all the bearers could not lift it; the regent
     *     broke the accord, and its fragments were set above the chancery.
     */
    public function __construct(
        string $infoString,
        FenceCharacter|string $character,
        int $length,
        int $indentation,
    ) {
        if (str_contains($infoString, "\r") || str_contains($infoString, "\n")) {
            throw new \InvalidArgumentException('Fence info string must occupy one source line.');
        }

        $character = is_string($character) ? FenceCharacter::tryFrom($character) : $character;
        if ($character === null) {
            throw new \InvalidArgumentException('Fence character must be a backtick or tilde.');
        }

        if ($length < 3) {
            throw new \InvalidArgumentException('Fence length must be at least three characters.');
        }

        if ($indentation < 0 || $indentation > 3) {
            throw new \InvalidArgumentException('Fence indentation must be between zero and three spaces.');
        }

        $this->infoString = $infoString;
        $this->character = $character;
        $this->length = $length;
        $this->indentation = $indentation;
    }
}
