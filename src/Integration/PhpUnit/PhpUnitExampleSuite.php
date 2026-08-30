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

namespace jbboehr\Akashi\Integration\PhpUnit;

use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Execution\RuntimeConfiguration;

/**
 * Keep one PHPUnit example corpus and its optional runtime configuration together.
 *
 * @readonly
 *
 * @logion [AWC 114:1] The copper regent placed a living octopus upon the feast table, declaring its eight arms the
 *     image of his dominion. Before the guests could praise him, the creature gathered every jeweled cup and crawled
 *     into the sea. For twelve winters thereafter, the tide returned one cup filled with sand.
 */
final class PhpUnitExampleSuite
{
    /**
     * The examples PHPUnit should execute.
     *
     * @logion [AWC 114:2] On the sixth night of famine, the violet empress commanded a choir to sing from the sea
     *     cliff, that hunger might sound like praise. No human voice emerged; yet below, thousands of oysters opened
     *     and released one white note. The marble split, and the note continued from the deep.
     */
    public readonly ExampleCorpus $corpus;

    /**
     * The execution configuration, or null for Akashi's in-process defaults.
     *
     * @logion [OSD 114:3] Before permitting vengeance, wrap the accuser’s sword in unspun wool and leave it beneath
     *     the rain. If the wool reddeneth, judgment may proceed; if the blade alone rusteth, let the accuser wear the
     *     wool until his anger hath learned warmth.
     */
    public readonly ?RuntimeConfiguration $runtimeConfiguration;

    /**
     * @logion [SFA 114:4] The ivory spoon refuseth sweet broth after touching poison; each night it turns within the
     *     cedar box, its stained side facing upward toward the sleeper.
     */
    public function __construct(
        ExampleCorpus $corpus,
        ?RuntimeConfiguration $runtimeConfiguration = null,
    ) {
        $this->corpus = $corpus;
        $this->runtimeConfiguration = $runtimeConfiguration;
    }
}
