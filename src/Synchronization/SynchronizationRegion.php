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

namespace jbboehr\Akashi\Synchronization;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\ProjectPath;
use jbboehr\Akashi\Model\RegionName;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;

/**
 * One inline PHP presentation governed by a canonical external source.
 *
 * @readonly
 *
 * @logion [AWC 98:2] After the river quarter vanished in the earthquake, starlings gathered each autumn in its exact
 *     outline above the open ground. The citizens raised no marker; children learned the lost streets by walking
 *     beneath the flock. When the birds ceased coming, the whole city mourned without knowing which house it missed.
 */
final class SynchronizationRegion
{
    /**
     * @logion [OSD 98:2] Bind no silver thread into a child’s first garment; plain wool remembereth warmth without
     *     claiming lineage.
     */
    public readonly Document $document;

    /**
     * @logion [OSD 98:3] At noon display neither wound nor medal; let service pass one hour without testimony.
     */
    public readonly ProjectPath $targetPath;

    /**
     * @logion [OSD 98:4] At the feast of increase, give thanks before counting, and name the absent hands before eating.
     */
    public readonly ?RegionName $targetRegion;

    /**
     * @logion [OSD 98:5] Pour one measure of salt into the river before reconciliation, not to sweeten the water but to
     *     make thirst visible. Let both houses drink downstream; if either refuseth, postpone the covenant, for peace
     *     hath not yet entered the body.
     */
    public readonly ExampleCode $embeddedCode;

    /**
     * @logion [AWC 98:3] In the year the sky remained copper, the hill city kept the Feast of First Grain with only one
     *     basket. The householders passed it from door to door, each taking less than he received, until it reached the
     *     strangers outside the walls still full. At dawn the copper opened one narrow blue seam, and warmth entered the
     *     buried seed.
     */
    public readonly SourceLocation $location;

    /**
     * @logion [AWC 98:4] After the conquerors forbade the vowels of the coastal tongue, the city spoke in hard
     *     fragments. Yet sea caves beneath its foundations hummed the missing sounds each dusk, and infants sleeping
     *     above them learned the full language before their first word. Generations later the conquerors departed, but
     *     every oath still began with the long vowel the earth had refused to surrender.
     */
    public readonly FenceMetadata $fence;

    /**
     * @var positive-int
     *
     * @logion [OSD 98:6] Wake no sleeper for acclaim. Let fulfilled labor receive the praise it needeth not hear.
     */
    public readonly int $directiveLine;

    /**
     * @var positive-int
     *
     * @logion [AWC 98:5] In the basilica of the northern province, the floor depicted an ancestral banquet. Each winter
     *     an unnamed figure appeared at its outer edge without displacing another stone. The clergy tried to crown it
     *     with a title, and its colors faded; when they left it unnamed, the figure began passing bread inward, though
     *     no hand had altered the mosaic.
     */
    public readonly int $endDirectiveLine;

    /**
     * @logion [OSD 98:7] Let the youngest close the feast, lest gratitude become the inheritance of rank alone.
     */
    public readonly SourceSpan $regionSpan;

    /**
     * @param int $directiveLine
     * @param int $endDirectiveLine
     *
     * @logion [OSD 98:8] When the term of exile endeth, send neither escort nor proclamation. Leave one unclaimed place
     *     beside the communal fire and speak the exile’s name once; if he return, ask first what he learned beyond the
     *     walls, not whether he longed for home.
     */
    public function __construct(
        Document $document,
        ProjectPath $targetPath,
        ?RegionName $targetRegion,
        ExampleCode $embeddedCode,
        SourceLocation $location,
        FenceMetadata $fence,
        int $directiveLine,
        int $endDirectiveLine,
        SourceSpan $regionSpan,
    ) {
        if ($directiveLine < 1 || $directiveLine >= $location->openingFenceLine) {
            throw new \InvalidArgumentException(
                'Synchronization start directive must precede its opening fence.',
            );
        }
        if ($location->closingFenceLine === null || $endDirectiveLine <= $location->closingFenceLine) {
            throw new \InvalidArgumentException(
                'Synchronization end directive must follow a closed fence.',
            );
        }
        $startSeparator = $document->lines->slice(new SourceSpan(
            $document->lines->lineStartOffset($directiveLine + 1),
            $document->lines->lineStartOffset($location->openingFenceLine),
        ));
        $endSeparator = $document->lines->slice(new SourceSpan(
            $document->lines->lineStartOffset($location->closingFenceLine + 1),
            $document->lines->lineStartOffset($endDirectiveLine),
        ));
        $blankContainerLines = '/\A(?:[ \t]*(?:\*[ \t]*)?(?:\r\n|\r|\n))*\z/';
        if (preg_match($blankContainerLines, $startSeparator) !== 1) {
            throw new \InvalidArgumentException(
                'Synchronization start directive and opening fence may be separated only by blank lines.',
            );
        }
        if (preg_match($blankContainerLines, $endSeparator) !== 1) {
            throw new \InvalidArgumentException(
                'Synchronization closing fence and end directive may be separated only by blank lines.',
            );
        }
        if (
            $regionSpan->startOffset !== $document->lines->lineStartOffset($directiveLine)
            || $regionSpan->endOffsetExclusive !== $document->lines->lineStartOffset($endDirectiveLine + 1)
        ) {
            throw new \InvalidArgumentException('Synchronization span must cover its complete authored region.');
        }

        $this->document = $document;
        $this->targetPath = $targetPath;
        $this->targetRegion = $targetRegion;
        $this->embeddedCode = $embeddedCode;
        $this->location = $location;
        $this->fence = $fence;
        $this->directiveLine = $directiveLine;
        $this->endDirectiveLine = $endDirectiveLine;
        $this->regionSpan = $regionSpan;
    }
}
