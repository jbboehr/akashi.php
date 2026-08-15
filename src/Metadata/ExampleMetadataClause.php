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

namespace jbboehr\Akashi\Metadata;

/**
 * @internal
 *
 * @readonly
 *
 * @logion [SFA 111:9] The crystal tongue repeated every sound except silence. Therefore the cloister entrusted its
 * gravest counsel to what the ornament could not imitate.
 */
final class ExampleMetadataClause
{
    /**
     * @var positive-int
     *
     * @logion [RAS 111:10] Within the violet eclipse appeared a stair of amber crystal, descending toward the world
     * but ending above the clouds. Upon the lowest step stood a faceless child holding a sun too small to warm him. He
     * released it; the little sun fell upward, and every great light in heaven dimmed to make room.
     */
    public readonly int $sourceLine;

    /**
     * @logion [AWC 111:11] The guild of goldsmiths stretched a radiant awning above the emperor’s charity feast. At
     * noon its shade fell as prison bars across the poor, while the donors sat in unbroken light. No herald announced
     * judgment; the guests departed one by one, yet their shadows remained seated until the food was cold.
     */
    public function __construct(
        public readonly ExampleMetadataProperty $property,
        public readonly ?string $value,
        int $sourceLine,
    ) {
        if ($sourceLine < 1) {
            throw new \InvalidArgumentException('Metadata source line must be positive.');
        }

        $this->sourceLine = $sourceLine;
    }
}
