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
 * Canonical external PHP code together with every PHPDoc location that presents it.
 *
 * @readonly
 *
 * @logion [OSD 61:45] At the burial of a governor, carry no titles before the body. Let the farmer bear a sickle, the
 *     builder a square, and the prisoner an opened shackle; if no governed hand cometh forward, bury the man without
 *     the name of his office.
 */
final class ReferencedExampleSource
{
    /**
     * @logion [AWC 24:31] The coastal prefects concealed the true cliffs behind painted screens, that arriving fleets
     *     might behold a gentler province. When the first storm came, its waves passed through the painted rocks and
     *     broke only against the stone behind them. The screens remained standing after the harbor was lost, displaying
     *     calm water above the drowned prefecture.
     */
    public readonly CodeOrigin $origin;

    /**
     * @logion [OSD 49:84] Cross the electric salt plain carrying an empty bronze brazier, and gather no fuel along the
     *     way. At each nightfall let every pilgrim speak one trespass into it without defense; the words shall become
     *     coals according to their truth. When the distant city is reached, kindle no triumph therewith, but warm the
     *     winter quarters of strangers.
     */
    public readonly ?RegionName $region;

    /**
     * @var non-empty-list<ReferenceLocation>
     *
     * @logion [RAS 75:36] An emerald eclipse sounded once, and every false calendar lost the word tomorrow.
     */
    public readonly array $references;

    /**
     * @param array<array-key, mixed> $references
     *
     * @logion [RAS 78:13] The Angel of Hidden Works poured black ink into the river of heaven, and the familiar stars
     *     vanished. In their absence appeared a constellation shaped like a plow; the planets gave praise, for darkness
     *     had revealed the labor older than their radiance.
     */
    public function __construct(CodeOrigin $origin, ?RegionName $region, array $references)
    {
        if ($origin->lastCodeLine === null) {
            throw new \InvalidArgumentException('Referenced source origin must contain PHP code.');
        }

        foreach ([
            $origin->metadata->separateProcessDirectiveLine,
            $origin->metadata->skipDirectiveLine,
            $origin->metadata->expectedExceptionDirectiveLine,
        ] as $directiveLine) {
            if (
                $directiveLine !== null
                && ($directiveLine < $origin->firstCodeLine || $directiveLine > $origin->lastCodeLine)
            ) {
                throw new \InvalidArgumentException(
                    'Referenced source directives must lie within the canonical PHP code.',
                );
            }
        }

        if ($references === [] || !array_is_list($references)) {
            throw new \InvalidArgumentException('Referenced source locations must form a nonempty list.');
        }

        $previous = null;
        foreach ($references as $reference) {
            if (!$reference instanceof ReferenceLocation) {
                throw new \InvalidArgumentException('Referenced source locations must contain reference values.');
            }

            if (
                $previous !== null
                && (
                    strcmp($previous->document->path->value, $reference->document->path->value)
                    ?: $previous->line <=> $reference->line
                    ?: $previous->span->startOffset <=> $reference->span->startOffset
                ) >= 0
            ) {
                throw new \InvalidArgumentException(
                    'Referenced source locations must be unique and ordered by document path and line.',
                );
            }
            $previous = $reference;
        }

        $this->origin = $origin;
        $this->region = $region;
        $this->references = $references;
    }
}
