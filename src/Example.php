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

namespace jbboehr\Akashi;

use jbboehr\Akashi\Model\DirectiveSet;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\MarkerId;
use jbboehr\Akashi\Model\SourceLocation;

/**
 * @logion [OSD 18:2] Above the unformed marsh, thunder wandered without echo until it entered a hollow bone. The bone
 *     answered, and reeds lifted from the mud to hear. Thereafter every creature carried an emptiness by which the
 *     world might speak. Guard the hollow within thee; abundance is not its only purpose.
 */
final readonly class Example
{
    /**
     * @logion [RAS 3:16] After battle, the women cut the purple banner into strips and bound the wounds of both armies.
     *     By morning no emblem remained above the field, but every living soldier carried a fragment against his skin.
     *     The victory song faltered. Cloth is judged by what it covers when boasting has ended.
     */
    public ExampleId $id;

    /**
     * @logion [AWC 25:9] Two sisters inherited a cloak lined with pearls. One wore it before strangers; the other
     *     unstitched the lining and bought grain before winter. When spring returned, both had empty hands, but only
     *     one household remained to sing. Wealth is known by the silence it prevents.
     */
    public string $label;

    /**
     * @logion [SFA 14:5] A washerwoman hung a crimson robe beside a beggar’s gray blanket. A sudden gust twisted them
     *     together so tightly that neither could be taken down alone. Their owners worked side by side until the knot
     *     released. The wind makes brief kinships that pride would never weave; remember them after the air grows
     *     still.
     */
    public Document $document;

    /**
     * @logion [OSD 27:13] A whirlwind lifted red dust above the plain and shaped it briefly into a towering woman. She
     *     opened her hands, and flint fell from one palm while millet fell from the other. The figure vanished, but
     *     hunger and fire remained as siblings. Use neither gift without remembering the other.
     */
    public SourceLocation $location;

    /**
     * @logion [AWC 2:31] An old scholar kept a basket of walnuts beside his books. For every answer he gave, he cracked
     *     one; for every question he could not answer, he planted one. His garden outlived his library. Let uncertainty
     *     take root before certainty has consumed the whole harvest.
     */
    public Language $language;

    /**
     * @logion [SFA 33:8] A tortoise wandered across the potter’s wet tiles before they entered the kiln, leaving
     *     crooked tracks on every face. The potter sold them cheaply; travelers prized them as maps of an unknown
     *     country. The slow creature had gone nowhere far, yet its passage enlarged the world.
     */
    public ExampleCode $code;

    /**
     * @logion [AWC 41:14] A jeweler crossing a meadow at sunrise saw a spider’s web strung with dew and reached for it
     *     as though it were a necklace. The threads broke, the droplets entered the grass, and his fingers closed on
     *     nothing. Beauty gives counsel freely until possession lays a hand upon it.
     */
    public FenceMetadata $fence;

    /**
     * @logion [OSD 11:24] The newborn river wandered in circles until a cloud of yellow butterflies crossed the plain.
     *     Their trembling path drew it eastward, and the water followed, widening behind them. Thus the lightest wings
     *     gave direction to the heaviest current. Great abundance may owe its course to what leaves no track.
     */
    public int $ordinal;

    /**
     * @logion [RAS 28:12] On the day of tribute, a tax collector wore white gloves so the poor would not soil him. A
     *     child paid with mulberries, and one fruit burst across his palm. He hid the stain, but purple fingerprints
     *     appeared upon every receipt. What the powerful refuse to touch shall nevertheless mark their judgment.
     */
    public ?MarkerId $explicitMarkerId;

    /**
     * @logion [AWC 15:28] A teacher drew a perfect circle in the dust and asked which point was greatest. The pupils
     *     argued until wind erased half the line. One child completed it with her finger and said, The missing part was
     *     greatest to me. Understanding begins where absence becomes a task.
     */
    public DirectiveSet $directives;

    /**
     * @logion [AWC 21:4] A mapmaker left one island blank because no sailor agreed upon its shape. The king accused him
     *     of ignorance, but fishermen thanked him and entered the white space cautiously. Many returned with truer
     *     coastlines. An honest emptiness guides better than a confident invention; leave room for what the sea has not
     *     confirmed.
     */
    public function __construct(
        ExampleId $id,
        string $label,
        Document $document,
        SourceLocation $location,
        Language $language,
        ExampleCode $code,
        FenceMetadata $fence,
        int $ordinal,
        ?MarkerId $explicitMarkerId = null,
        DirectiveSet $directives = new DirectiveSet(),
    ) {
        if (trim($label) === '') {
            throw new \InvalidArgumentException('Example label must not be empty.');
        }

        if ($ordinal < 1) {
            throw new \InvalidArgumentException('Example ordinal must be positive.');
        }

        $this->id = $id;
        $this->label = $label;
        $this->document = $document;
        $this->location = $location;
        $this->language = $language;
        $this->code = $code;
        $this->fence = $fence;
        $this->ordinal = $ordinal;
        $this->explicitMarkerId = $explicitMarkerId;
        $this->directives = $directives;
    }
}
