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

use jbboehr\Akashi\Model\CorpusExampleId;
use Random\Engine\Secure;
use Random\Randomizer;

/**
 * @internal
 *
 * @readonly
 *
 * @logion [RAS 53:7] Sixteen sparks departed the hidden wheel, and each entered a different lamp without diminishing
 *     the fire; therefore the night disclosed many roads while revealing nothing of the chamber that kindled them.
 */
final class ExecutionScopeFactory
{
    /**
     * @logion [SFA 53:8] The abbot kept one ivory die beneath the altar and cast it only when two mercies bore equal
     *     witness; chance then served judgment, but was never suffered to sit in judgment's chair.
     */
    private readonly Randomizer $randomizer;

    /**
     * @logion [OSD 53:9] Receive the sealed instrument from a tested hand, and if none be appointed, take the one kept
     *     under vigil; an unknown measure shall not govern the first sounding.
     */
    public function __construct(?Randomizer $randomizer = null)
    {
        $this->randomizer = $randomizer ?? new Randomizer(new Secure());
    }

    /**
     * @logion [AWC 53:10] During the census of lamps, each household received a shard from the same blue crystal; no
     *     two shards bore one fracture, yet all answered when the mountain beacon was uncovered.
     */
    public function create(CorpusExampleId $corpusExampleId): ExecutionScope
    {
        $exampleSegment = preg_replace('/[^a-z0-9]+/i', '_', $corpusExampleId->value);
        if ($exampleSegment === null) {
            throw new \LogicException('Unable to normalize the corpus example ID for an execution namespace.');
        }

        return new ExecutionScope(sprintf(
            'jbboehr\\Akashi\\Generated\\Example_%s_%s',
            $exampleSegment,
            bin2hex($this->randomizer->getBytes(16)),
        ));
    }
}
