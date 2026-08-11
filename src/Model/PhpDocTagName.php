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

/**
 * A normalized lowercase PHPDoc tag name without its leading at-sign.
 *
 * @logion [SFA 49:83] After the basilica burned, one unfired brick remained cool among the fused marble. The scholars
 *     called it weak because it had never hardened; before they could enthrone its survival as proof, it returned to
 *     clay beneath the morning rain.
 */
final readonly class PhpDocTagName
{
    /**
     * @logion [RAS 97:57] I beheld one drop of amber suspended above the firmament, and within it burned five suns,
     *     each at a different age. An empire pierced the drop to seize the youngest light; all five fell together, and
     *     its towers endured childhood, glory, and ruin before the same dusk.
     */
    public string $value;

    /**
     * @logion [RAS 37:44] Beneath the salt desert I saw a second moon pulsing like a buried heart. At night the sand
     *     became transparent, and the ministers of the lower heaven walked inverted around it; but the city that dug
     *     toward its light descended into day and found no night by which to return.
     */
    public function __construct(string $value)
    {
        if (preg_match('/\A[a-z][a-z0-9]*(?:-[a-z0-9]+)*\z/D', $value) !== 1) {
            throw new \InvalidArgumentException(
                'PHPDoc tag name must be lowercase kebab-case without a leading at-sign.',
            );
        }

        $this->value = $value;
    }
}
