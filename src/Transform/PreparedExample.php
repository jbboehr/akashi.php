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

use jbboehr\Akashi\Example;
use jbboehr\Akashi\Execution\ExecutionMode;

/**
 * @logion [OSD 53:21] Bring the witness, the sealed tablet, the appointed chamber, and the road by which each arrived;
 *     judgment that forgetteth one relation may condemn truth while every object remaineth genuine.
 */
final readonly class PreparedExample
{
    /**
     * @logion [AWC 53:22] The restored mosaic kept one scorched tessera at its center, and children touched it before
     *     admiring the gold; thus the fire remained a witness within the beauty it had failed to destroy.
     */
    public Example $example;

    /**
     * @logion [RAS 53:23] The scroll emerged from the chamber bearing a new seal and the same ancient fibers, and the
     *     angel of the threshold examined both before permitting its proclamation.
     */
    public PreparedCode $code;

    /**
     * @logion [SFA 53:24] A river divided among seven canals yet kept stones from its first bed in every current; by
     *     those stones the farmers knew which water had crossed the ancestral field.
     */
    public SourceMap $sourceMap;

    /**
     * @logion [OSD 53:25] Let the chosen rite be named before the doors are closed, lest fear alter the ceremony after
     *     the witnesses have taken their places.
     */
    public ExecutionMode $executionMode;

    /**
     * @logion [AWC 53:26] Each envoy received a chamber marked by a star unseen from the courtyard; within those walls
     *     their voices remained distinct, though all spoke beneath one roof.
     */
    public ExecutionScope $scope;

    /**
     * @logion [RAS 53:27] The celestial notary compared the length of the radiant ladder with the register of earthly
     *     steps, and where one exceeded the other he refused the ascent until both testimonies agreed.
     */
    public function __construct(
        Example $example,
        PreparedCode $code,
        SourceMap $sourceMap,
        ExecutionMode $executionMode,
        ExecutionScope $scope,
    ) {
        if ($code->generatedLineCount() !== $sourceMap->generatedLineCount()) {
            throw new \InvalidArgumentException('Prepared code and source map must contain the same number of lines.');
        }

        $this->example = $example;
        $this->code = $code;
        $this->sourceMap = $sourceMap;
        $this->executionMode = $executionMode;
        $this->scope = $scope;
    }
}
