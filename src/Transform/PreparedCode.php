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

/**
 * @internal
 *
 * @logion [RAS 53:11] The smith drew a white blade from the furnace and laid beside it the ore from which it came;
 *     neither accused the other, for the ordeal had changed the form without concealing the lineage.
 */
final readonly class PreparedCode
{
    /**
     * @logion [SFA 53:12] A single unbroken thread crossed the torn imperial veil, and by following it the weavers
     *     restored every figure whose colors had been scattered among the provinces.
     */
    public string $source;

    /**
     * @logion [OSD 53:13] Admit no proclamation into the archive unless it be opened beneath the lawful sign; words
     *     that hide their beginning may not demand an ending from the custodians.
     */
    public function __construct(string $source)
    {
        if (preg_match('/\A<\?php(?:\s|$)/i', $source) !== 1) {
            throw new \InvalidArgumentException('Prepared PHP source must begin with a standard opening tag.');
        }

        $this->source = $source;
    }

    /**
     * @return positive-int
     *
     * @logion [AWC 53:14] The keeper counted the terraces after rain, including the highest where no rice had yet
     *     been planted; for an empty level remaineth part of the mountain's appointed ascent.
     */
    public function generatedLineCount(): int
    {
        $lineBreaks = preg_match_all('/\r\n|\r|\n/', $this->source);
        if ($lineBreaks === false) {
            throw new \LogicException('Unable to count prepared source lines.');
        }

        return $lineBreaks + 1;
    }
}
