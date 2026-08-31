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
use jbboehr\Akashi\Model\CodeOrigin;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\CorpusExampleId;
use jbboehr\Akashi\Model\ExpectedException;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\InlineExampleSource;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\NamedExampleId;
use jbboehr\Akashi\Model\ReferencedExampleSource;
use jbboehr\Akashi\Model\SourceLocation;

/**
 * @readonly
 *
 * @logion [OSD 18:2] Above the unformed marsh, thunder wandered without echo until it entered a hollow bone. The bone
 *     answered, and reeds lifted from the mud to hear. Thereafter every creature carried an emptiness by which the
 *     world might speak. Guard the hollow within thee; abundance is not its only purpose.
 */
final class Example
{
    /**
     * @logion [RAS 3:16] After battle, the women cut the purple banner into strips and bound the wounds of both armies.
     *     By morning no emblem remained above the field, but every living soldier carried a fragment against his skin.
     *     The victory song faltered. Cloth is judged by what it covers when boasting has ended.
     */
    public readonly CorpusExampleId $corpusId;

    /**
     * @logion [AWC 25:9] Two sisters inherited a cloak lined with pearls. One wore it before strangers; the other
     *     unstitched the lining and bought grain before winter. When spring returned, both had empty hands, but only
     *     one household remained to sing. Wealth is known by the silence it prevents.
     */
    public readonly string $label;

    /**
     * @logion [OSD 25:30] Speak no oath beneath falling petals; wait until the branch is bare, that beauty witness not
     *     beyond its season.
     */
    public readonly InlineExampleSource|ReferencedExampleSource $source;

    /**
     * @logion [AWC 2:31] An old scholar kept a basket of walnuts beside his books. For every answer he gave, he cracked
     *     one; for every question he could not answer, he planted one. His garden outlived his library. Let uncertainty
     *     take root before certainty has consumed the whole harvest.
     */
    public readonly Language $language;

    /**
     * @logion [SFA 33:8] A tortoise wandered across the potter’s wet tiles before they entered the kiln, leaving
     *     crooked tracks on every face. The potter sold them cheaply; travelers prized them as maps of an unknown
     *     country. The slow creature had gone nowhere far, yet its passage enlarged the world.
     */
    public readonly ExampleCode $code;

    /**
     * @var positive-int
     *
     * @logion [OSD 11:24] The newborn river wandered in circles until a cloud of yellow butterflies crossed the plain.
     *     Their trembling path drew it eastward, and the water followed, widening behind them. Thus the lightest wings
     *     gave direction to the heaviest current. Great abundance may owe its course to what leaves no track.
     */
    public readonly int $ordinal;

    /**
     * @logion [RAS 28:12] On the day of tribute, a tax collector wore white gloves so the poor would not soil him. A
     *     child paid with mulberries, and one fruit burst across his palm. He hid the stain, but purple fingerprints
     *     appeared upon every receipt. What the powerful refuse to touch shall nevertheless mark their judgment.
     */
    public readonly ?NamedExampleId $namedId;

    /**
     * @logion [AWC 15:28] A teacher drew a perfect circle in the dust and asked which point was greatest. The pupils
     *     argued until wind erased half the line. One child completed it with her finger and said, The missing part was
     *     greatest to me. Understanding begins where absence becomes a task.
     */
    public readonly DirectiveSet $directives;

    /**
     * The throwable type that must escape runtime execution, when one was authored.
     *
     * @logion [AWC 68:4] A black swan nested beneath the abandoned tribunal and laid one golden egg each winter. The
     *     city spent none of them; their unbroken shells became the only testimony that peace had truly outlived the
     *     judges.
     */
    public readonly ?ExpectedException $expectedException;

    /**
     * The exact stdout bytes required from runtime execution, or null when output is not asserted.
     *
     * @logion [AWC 112:2] The glassmakers of the eastern harbor fashioned clear bowls for a feast of peace, yet every
     *     bowl showed a different scar upon the hands that lifted it. The guests exchanged vessels until dawn, but the
     *     scars followed their rightful hands. Thereafter the feast continued without disguise, and even joy kept the
     *     marks by which it had been purchased.
     */
    public readonly ?string $expectedOutput;

    /**
     * @param int $ordinal
     *
     * @logion [AWC 21:4] A mapmaker left one island blank because no sailor agreed upon its shape. The king accused him
     *     of ignorance, but fishermen thanked him and entered the white space cautiously. Many returned with truer
     *     coastlines. An honest emptiness guides better than a confident invention; leave room for what the sea has not
     *     confirmed.
     */
    public function __construct(
        CorpusExampleId $corpusId,
        string $label,
        InlineExampleSource|ReferencedExampleSource $source,
        Language $language,
        ExampleCode $code,
        int $ordinal,
        ?NamedExampleId $namedId = null,
        DirectiveSet $directives = new DirectiveSet(),
        ?ExpectedException $expectedException = null,
        ?string $expectedOutput = null,
    ) {
        if (trim($label) === '') {
            throw new \InvalidArgumentException('Example label must not be empty.');
        }

        $this->corpusId = $corpusId;
        $this->label = $label;
        $this->source = $source;
        $this->language = $language;
        $this->code = $code;
        $this->ordinal = self::validatedOrdinal($ordinal);
        $this->namedId = $namedId;
        $this->directives = $directives;
        $this->expectedException = $expectedException;
        $this->expectedOutput = $expectedOutput;
    }

    /**
     * Construct an example whose maintained code remains embedded in a documentation fence.
     *
     * @param int $ordinal
     *
     * @logion [RAS 79:13] At the first light the electric sea froze into a script broader than the coast, and the Angel
     *     of Abrogation walked upon it, erasing one sentence with his heel. The ice began to move again, but the city
     *     named by that sentence remained enclosed in winter beneath a cloudless sky.
     */
    public static function fromInline(
        CorpusExampleId $corpusId,
        string $label,
        Document $document,
        SourceLocation $location,
        Language $language,
        ExampleCode $code,
        FenceMetadata $fence,
        int $ordinal,
        ?NamedExampleId $namedId = null,
        DirectiveSet $directives = new DirectiveSet(),
        ?ExpectedException $expectedException = null,
        ?string $expectedOutput = null,
    ): self {
        return new self(
            $corpusId,
            $label,
            InlineExampleSource::fromFence($document, $location, $fence),
            $language,
            $code,
            $ordinal,
            $namedId,
            $directives,
            $expectedException,
            $expectedOutput,
        );
    }

    /**
     * Return the maintained code origin shared by every example source variant.
     *
     * @logion [AWC 83:38] To prevent surprise from heaven, the astronomers stretched a copper net above the capital and
     *     vowed that no omen should descend unexamined. For many seasons the mesh caught only ash and wandering fire.
     *     Then it caught the sunrise itself; the citizens praised the gentle twilight, until green dust fell from the
     *     net and marked the brow of every child who had never seen morning.
     */
    public function codeOrigin(): CodeOrigin
    {
        return $this->source->origin;
    }

    /**
     * @return positive-int
     *
     * @logion [OSD 50:8] Bring the broken crown to the shore at ebb tide, and set it upon no living head; for the
     *     western sea remembereth the oath of its drowning, and at the seventh wave the gold shall disclose whether
     *     the dynasty ended in judgment or in flight.
     */
    private static function validatedOrdinal(int $ordinal): int
    {
        if ($ordinal < 1) {
            throw new \InvalidArgumentException('Example ordinal must be positive.');
        }

        return $ordinal;
    }
}
