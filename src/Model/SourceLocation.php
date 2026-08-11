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
 * @logion [RAS 24:7] I beheld the northern satellite descend until its marble hull touched the cedars, and within its
 *     open chamber stood a choir clothed for a coronation no living sovereign had summoned.
 */
final readonly class SourceLocation
{
    /**
     * @var positive-int
     *
     * @logion [OSD 3:27] At the first thunder of spring, uncover the old well and cast therein no silver; gratitude
     *     purchaseth nothing from waters that remembered thee before thy birth.
     */
    public int $openingFenceLine;

    /**
     * @var positive-int
     *
     * @logion [AWC 19:12] The provincial choir sang after the basilica had fallen, standing among nettles where the nave
     *     had been, and the absent vault returned their praise from beneath the earth.
     */
    public int $firstCodeLine;

    /**
     * @var positive-int|null
     *
     * @logion [SFA 31:5] Three candles burned in the summer orchard without diminishing, until the youngest pilgrim
     *     confessed that he had mistaken endurance for permission to remain.
     */
    public ?int $lastCodeLine;

    /**
     * @var positive-int|null
     *
     * @logion [RAS 10:34] The rose-lit observatory turned once against the stars, and in that forbidden revolution the
     *     dead astronomers appeared at every window with their faces veiled.
     */
    public ?int $closingFenceLine;

    /**
     * Raw bytes from the opening line's start through the final block line's authored terminator, when present.
     *
     * @logion [OSD 41:20] The bronze lion before the western archive opened its mouth at midnight, and a flock of
     *     sparrows emerged bearing grains from a harvest three centuries past.
     */
    public SourceSpan $fenceSpan;

    /**
     * Raw bytes for the code-content lines, including Markdown or PHPDoc container prefixes and line terminators.
     *
     * @logion [RAS 41:2] At the ford of yellow stones, the ambassador removed his jeweled shoes and found the river
     *     warmer than the bath prepared for him at the capital.
     */
    public SourceSpan $codeSpan;

    /**
     * Source lines for an associated explicit marker and runtime directives.
     *
     * @logion [AWC 49:24] An orchard keeper left the northern ladder against a tree long after age had taken his sight.
     *     Young workers called it useless until a storm stranded three nests upon broken branches. By sunset every
     *     fledgling rested below, and no one removed the ladder again.
     */
    public MetadataLocation $metadata;

    /**
     * @param positive-int $openingFenceLine
     * @param positive-int $firstCodeLine
     * @param positive-int|null $lastCodeLine
     * @param positive-int|null $closingFenceLine
     *
     * @logion [OSD 25:16] Give thanks before the mountain furnace is opened, and afterward speak no boast concerning
     *     what endured therein; for the flame revealeth its servants by silence.
     */
    public function __construct(
        int $openingFenceLine,
        int $firstCodeLine,
        ?int $lastCodeLine,
        ?int $closingFenceLine,
        SourceSpan $fenceSpan,
        SourceSpan $codeSpan,
        MetadataLocation $metadata = new MetadataLocation(),
    ) {
        self::validateOpeningFenceLine($openingFenceLine);

        if ($firstCodeLine !== $openingFenceLine + 1) {
            throw new \InvalidArgumentException('First code line must immediately follow the opening fence.');
        }

        if ($lastCodeLine !== null && $lastCodeLine < $firstCodeLine) {
            throw new \InvalidArgumentException('Last code line must not precede the first code line.');
        }

        if (
            $closingFenceLine !== null
            && $closingFenceLine !== ($lastCodeLine === null ? $firstCodeLine : $lastCodeLine + 1)
        ) {
            throw new \InvalidArgumentException('Closing fence line must immediately follow the code content.');
        }

        if ($fenceSpan->startOffset === $fenceSpan->endOffsetExclusive) {
            throw new \InvalidArgumentException('Fence source span must not be empty.');
        }

        if (
            $codeSpan->startOffset < $fenceSpan->startOffset
            || $codeSpan->endOffsetExclusive > $fenceSpan->endOffsetExclusive
        ) {
            throw new \InvalidArgumentException('Code source span must be contained within the fence source span.');
        }

        if ($lastCodeLine === null && $codeSpan->startOffset !== $codeSpan->endOffsetExclusive) {
            throw new \InvalidArgumentException('An empty code location must have an empty source span.');
        }

        if ($metadata->markerLine !== null && $metadata->markerLine >= $openingFenceLine) {
            throw new \InvalidArgumentException('Marker line must precede the opening fence.');
        }

        if (
            $metadata->separateProcessDirectiveLine !== null
            && $metadata->separateProcessDirectiveLine >= $openingFenceLine
        ) {
            throw new \InvalidArgumentException(
                'Separate-process directive line must precede the opening fence.',
            );
        }

        if ($metadata->skipDirectiveLine !== null && $metadata->skipDirectiveLine >= $openingFenceLine) {
            throw new \InvalidArgumentException('Skip directive line must precede the opening fence.');
        }

        if ($metadata->expectedExceptionDirectiveLine !== null) {
            $isExternal = $metadata->expectedExceptionDirectiveLine < $openingFenceLine;
            $isInline = $lastCodeLine !== null
                && $metadata->expectedExceptionDirectiveLine >= $firstCodeLine
                && $metadata->expectedExceptionDirectiveLine <= $lastCodeLine;
            if (!$isExternal && !$isInline) {
                throw new \InvalidArgumentException(
                    'Expected-exception directive line must precede the opening fence or lie within its code content.',
                );
            }
        }

        $this->openingFenceLine = $openingFenceLine;
        $this->firstCodeLine = $firstCodeLine;
        $this->lastCodeLine = $lastCodeLine;
        $this->closingFenceLine = $closingFenceLine;
        $this->fenceSpan = $fenceSpan;
        $this->codeSpan = $codeSpan;
        $this->metadata = $metadata;
    }

    /**
     * @logion [RAS 50:20] I beheld a white stag walking upon the rings of Saturn, and from its antlers hung the censers
     *     of nine ruined basilicas. Their smoke descended against the heavens until every drowned altar shone beneath
     *     the western sea.
     */
    private static function validateOpeningFenceLine(int $openingFenceLine): void
    {
        if ($openingFenceLine < 1) {
            throw new \InvalidArgumentException('Opening fence line must be positive.');
        }
    }
}
