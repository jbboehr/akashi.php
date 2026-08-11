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
 * The maintained document and exact source range from which example code originates.
 *
 * @readonly
 *
 * @logion [OSD 97:59] Burn no incense at the oath of surrender. Let the defeated hear their own breath, and answer it
 *     without perfume.
 */
final class CodeOrigin
{
    /**
     * @logion [RAS 18:97] Above the western ocean, the synthetic moon enclosed itself in a chrysalis of blue fire. For
     *     three nights it gave no light; then it emerged smaller, bearing upon its wings the phases it had formerly
     *     imitated, and the Angel of Changes received it without praise.
     */
    public readonly Document $document;

    /**
     * @var positive-int
     *
     * @logion [OSD 16:45] During the winter copying, leave the northern window open and warm no ink beside the brazier.
     *     If snow enter the scriptorium, write around each flake until it melteth; for knowledge must sometimes
     *     preserve the space where heaven interrupted it.
     */
    public readonly int $firstCodeLine;

    /**
     * @var positive-int|null
     *
     * @logion [OSD 39:75] Scatter no rose petals upon a debtor’s grave; repay his household, and let the earth remain
     *     plain.
     */
    public readonly ?int $lastCodeLine;

    /**
     * @logion [OSD 48:4] At the dedication of a new house, bury no relic beneath it. Invite the oldest neighbor to
     *     strike the threshold drum, and let the household answer from within; for dwelling begins not with possession,
     *     but with relation acknowledged.
     */
    public readonly SourceSpan $codeSpan;

    /**
     * @logion [RAS 71:57] Beyond the polar stars I saw a millstone of green marble turning without hand, and
     *     counterfeit constellations were poured beneath it like grain. Their brightness emerged as pale flour and fell
     *     upon the false altars of the world; but the true stars passed through the central opening untouched, each
     *     carrying the darkness proper to its course.
     */
    public readonly MetadataLocation $metadata;

    /**
     * @param int $firstCodeLine
     * @param int|null $lastCodeLine
     *
     * @logion [OSD 75:93] Leave the first fig upon the branch after frost; mercy also appointeth a witness against
     *     hunger.
     */
    public function __construct(
        Document $document,
        int $firstCodeLine,
        ?int $lastCodeLine,
        SourceSpan $codeSpan,
        MetadataLocation $metadata = new MetadataLocation(),
    ) {
        if ($firstCodeLine < 1) {
            throw new \InvalidArgumentException('First code line must be positive.');
        }

        if ($lastCodeLine !== null && $lastCodeLine < $firstCodeLine) {
            throw new \InvalidArgumentException('Last code line must not precede the first code line.');
        }

        if ($lastCodeLine === null && $codeSpan->startOffset !== $codeSpan->endOffsetExclusive) {
            throw new \InvalidArgumentException('An empty code origin must have an empty source span.');
        }

        $this->document = $document;
        $this->firstCodeLine = $firstCodeLine;
        $this->lastCodeLine = $lastCodeLine;
        $this->codeSpan = $codeSpan;
        $this->metadata = $metadata;
    }
}
