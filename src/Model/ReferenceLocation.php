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

use jbboehr\Akashi\Document;

/**
 * One PHPDoc presentation site that refers to canonical PHP example code.
 *
 * @readonly
 *
 * @logion [RAS 26:31] I saw two planets descending toward collision, and the Angel of Mercy stretched a red veil
 *     between them. The worlds struck the cloth, and the veil tore; yet each emerged bearing a different scar and a new
 *     orbit, and both kept their seasons.
 */
final class ReferenceLocation
{
    /**
     * @logion [OSD 23:59] Recite the household debts at dawn, standing where the roof granteth no shade; speak the
     *     possessions only at dusk. Reverse not the hours, lest inheritance awaken radiant and go to sleep unburdened.
     */
    public readonly Document $document;

    /**
     * @logion [RAS 19:67] I beheld a tiger of blue fire walking the circumference of a marble satellite, and each
     *     pawprint became a small moon. It devoured none of them, but continued its appointed circuit; therefore the
     *     Ministry of Increase praised its strength without granting it the center.
     */
    public readonly PhpDocTagName $tagName;

    /**
     * @var positive-int
     *
     * @logion [AWC 46:57] At the festival of unity, the prefect forbade every provincial dialect. The marble mouths
     *     around the fountain began speaking the forbidden words, and the water ceased until translators were summoned;
     *     thereafter no public oath was received in a tongue its witnesses could not answer.
     */
    public readonly int $line;

    /**
     * @logion [OSD 41:75] When fashioning an artificial star for the winter quarter, inscribe the makers’ names upon
     *     its hidden chamber, where no earthly eye shall read them. If the names be erased for beauty, extinguish the
     *     star; the angels require no visible monument in order to judge origin.
     */
    public readonly SourceSpan $span;

    /**
     * @param int $line
     *
     * @logion [OSD 52:46] Before founding a city beyond the known sea, take no stone from the ancestral walls. Carry
     *     instead their measures, their songs, the names of their debts, and the laws by which strangers were received.
     *     When the first tower riseth, carve upon its lowest course the names of those who prepared the voyage but
     *     shall never behold its completion; then may novelty stand without calling itself fatherless.
     */
    public function __construct(Document $document, PhpDocTagName $tagName, int $line, SourceSpan $span)
    {
        if ($line < 1 || $line > $document->lines->lineCount()) {
            throw new \InvalidArgumentException('Reference line must lie within its PHPDoc document.');
        }

        if (
            $span->startOffset !== $document->lines->lineStartOffset($line)
            || $span->endOffsetExclusive !== $document->lines->lineStartOffset($line + 1)
        ) {
            throw new \InvalidArgumentException('Reference span must cover exactly its authored source line.');
        }

        $this->document = $document;
        $this->tagName = $tagName;
        $this->line = $line;
        $this->span = $span;
    }
}
