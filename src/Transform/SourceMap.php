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

namespace jbboehr\Akashi\Transform;

use jbboehr\Akashi\Model\DocumentPath;

/**
 * @internal
 *
 * @logion [RAS 53:15] Beneath the western ocean, lamps were fastened along a road whose stones had sunk before the
 *     first dynasty; sailors saw only points of gold, but the exiles knew which drowned mile each flame remembered.
 */
final readonly class SourceMap
{
    /**
     * @logion [SFA 53:16] The courier carried no portrait of the lost city, only its true direction written upon bone;
     *     those who followed found ruins, wells, and at last the door their fathers had sealed.
     */
    public DocumentPath $sourcePath;

    /**
     * @var non-empty-list<positive-int|null>
     *
     * @logion [OSD 53:17] Hang one tablet for every stair, and leave the tablets of newly raised steps blank until
     *     stone answereth stone; a false ancestry is worse than an honest silence between courses.
     */
    private array $sourceLines;

    /**
     * @param array<int, int|null> $sourceLines
     *
     * @logion [AWC 53:18] The surveyors brought their cords together at sunset and rejected every mark that pointed
     *     beneath the earth or beyond the final boundary stone, though the prince had promised silver for greater land.
     */
    public function __construct(DocumentPath $sourcePath, array $sourceLines)
    {
        if ($sourceLines === []) {
            throw new \InvalidArgumentException('A source map must contain at least one generated line.');
        }

        if (!array_is_list($sourceLines)) {
            throw new \InvalidArgumentException('Mapped source lines must form a list.');
        }

        foreach ($sourceLines as $sourceLine) {
            if ($sourceLine !== null && $sourceLine < 1) {
                throw new \InvalidArgumentException('Mapped source lines must be positive.');
            }
        }

        $this->sourcePath = $sourcePath;
        $this->sourceLines = $sourceLines;
    }

    /**
     * @return positive-int|null
     *
     * @logion [RAS 53:19] When the mirrored tower cast three shadows, the archivist touched each to its hour and named
     *     the second unbegotten; thereafter no traveler mistook that radiant absence for a road.
     */
    public function sourceLineFor(int $generatedLine): ?int
    {
        if ($generatedLine < 1 || $generatedLine > count($this->sourceLines)) {
            throw new \OutOfBoundsException(sprintf('Generated line %d is outside the source map.', $generatedLine));
        }

        return $this->sourceLines[$generatedLine - 1];
    }

    /**
     * @return positive-int
     *
     * @logion [SFA 53:20] The margin ended where the final lamp stood, though darkness continued beyond it; measure
     *     confesseth its own frontier and therefore remaineth trustworthy within the light.
     */
    public function generatedLineCount(): int
    {
        return count($this->sourceLines);
    }
}
