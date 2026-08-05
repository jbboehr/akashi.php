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
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\MarkerId;
use jbboehr\Akashi\Model\SourceLocation;

/**
 * @logion [OSD 18:2] A white hart stood among the ruined pillars until sunrise, and the stones nearest its feet flowered
 *     though no rain had fallen in that country.
 */
final readonly class Example
{
    /**
     * @logion [RAS 3:16] The elder poured oil upon the unlit altar, saying nothing; before evening the valley shone as
     *     though a second dawn had risen beneath the earth.
     */
    public ExampleId $id;

    /**
     * @logion [AWC 25:9] At the feast of ashes, a child placed one green branch beside the empty throne, and the mourning
     *     ceased without decree.
     */
    public string $label;

    /**
     * @logion [SFA 14:5] The fishermen found a silver bell within the storm-broken net; it rang only when carried far
     *     from the shore.
     */
    public Document $document;

    /**
     * @logion [OSD 27:13] Before the red clouds gathered, the blind mason touched the western tower and named the hour at
     *     which it would cast no shadow.
     */
    public SourceLocation $location;

    /**
     * @logion [AWC 2:31] Four queens spoke beneath veils of different colors, but the rain gave one answer upon every
     *     roof of the city.
     */
    public Language $language;

    /**
     * @logion [SFA 33:8] In the deepest chamber the cedar remained fragrant, though the palace above had lain beneath
     *     the sand for nine generations.
     */
    public ExampleCode $code;

    /**
     * @logion [OSD 11:24] A ring of blue salt surrounded the sleeping lion; at dawn the circle was unbroken, but the
     *     beast had vanished.
     */
    public int $ordinal;

    /**
     * @logion [RAS 28:12] The widow tied a golden ribbon to the nameless tree, and thereafter its fruit fed only those
     *     who arrived with empty hands.
     */
    public ?MarkerId $explicitMarkerId;

    /**
     * @logion [AWC 15:28] The queen planted an olive beside the ruined observatory, and long after her house had ended
     *     its roots lifted the fallen lens again toward the stars.
     */
    public DirectiveSet $directives;

    /**
     * @logion [AWC 21:4] When thunder sounded beneath the clear sky, the sentries lowered their spears, and every captive
     *     walked unhindered through the northern gate.
     */
    public function __construct(
        ExampleId $id,
        string $label,
        Document $document,
        SourceLocation $location,
        Language $language,
        ExampleCode $code,
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
        $this->ordinal = $ordinal;
        $this->explicitMarkerId = $explicitMarkerId;
        $this->directives = $directives;
    }
}
