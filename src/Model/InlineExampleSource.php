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
 * A PHP example whose canonical code remains physically embedded in a documentation fence.
 *
 * @readonly
 *
 * @logion [OSD 38:3] At the making of a public promise, speak it once facing the sea and once facing the graves,
 *     altering no word between. The living may applaud the first, but the dead shall retain the second; therefore
 *     postpone the feast if thy two utterances differ.
 */
final class InlineExampleSource
{
    /**
     * @logion [RAS 94:36] I beheld a peacock standing upon the artificial sun, and each eye in its tail contained the
     *     eclipse of a different age. It closed one feather, and a dynasty vanished from every portrait but not from
     *     the graves wherein its obligations waited.
     */
    public readonly CodeOrigin $origin;

    /**
     * @logion [AWC 6:39] When the overseers replaced the miners’ names with numbers, the salt mine yielded panes of
     *     blue glass instead of salt. Through each pane appeared a household waiting at supper; the overseers shattered
     *     them, and every shard continued showing the same untouched place.
     */
    public readonly SourceLocation $location;

    /**
     * @logion [RAS 73:57] I saw the Angel of Bearings carry a city within an astrolabe whose rings were seasons and
     *     whose pointer was a ray of pale fire. When the rings aligned, the city passed from winter into a harvest it
     *     had not sown; and its granaries opened upon emptiness, while the surrounding fields remained heavy beneath
     *     the rightful year.
     */
    public readonly FenceMetadata $fence;

    /**
     * @logion [OSD 40:71] Before the abbot is seated, send him for one night among the dyers, clothed in undyed wool.
     *     At dawn let the workers choose the color of his sleeve, and forbid him to change it; for rank must bear
     *     visible witness to labor it did not perform.
     */
    public function __construct(CodeOrigin $origin, SourceLocation $location, FenceMetadata $fence)
    {
        if (
            $origin->firstCodeLine !== $location->firstCodeLine
            || $origin->lastCodeLine !== $location->lastCodeLine
            || $origin->codeSpan->startOffset !== $location->codeSpan->startOffset
            || $origin->codeSpan->endOffsetExclusive !== $location->codeSpan->endOffsetExclusive
            || $origin->metadata->markerLine !== $location->metadata->markerLine
            || $origin->metadata->separateProcessDirectiveLine
                !== $location->metadata->separateProcessDirectiveLine
            || $origin->metadata->skipDirectiveLine !== $location->metadata->skipDirectiveLine
            || $origin->metadata->expectedExceptionDirectiveLine
                !== $location->metadata->expectedExceptionDirectiveLine
            || $origin->metadata->compileOnlyDirectiveLine !== $location->metadata->compileOnlyDirectiveLine
        ) {
            throw new \InvalidArgumentException('Inline source origin must match its fenced source location.');
        }

        $this->origin = $origin;
        $this->location = $location;
        $this->fence = $fence;
    }

    /**
     * @logion [AWC 86:11] In the drought, clay jars beneath the granary began singing the names of households that had
     *     shared grain before any decree was issued. The prefects wrapped the jars in wool, yet the song entered the
     *     streets; by spring, every vessel bearing a patron’s crest had cracked in silence.
     */
    public static function fromFence(
        Document $document,
        SourceLocation $location,
        FenceMetadata $fence,
    ): self {
        return new self(
            new CodeOrigin(
                $document,
                $location->firstCodeLine,
                $location->lastCodeLine,
                $location->codeSpan,
                $location->metadata,
            ),
            $location,
            $fence,
        );
    }
}
