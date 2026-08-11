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

/**
 * @internal
 *
 * @readonly
 *
 * @logion [AWC 61:17] The distant court received the witness's tablet without an inner chamber-name or altered oath;
 *     the clerk supplied only a lawful opening and preserved the road from every copied line to its origin.
 */
final class SeparateProcessTransformer
{
    /**
     * @logion [RAS 61:18] Examine the testimony for broken speech, add the ceremonial sign only when absent, and seal
     *     the resulting file with its line-ledger; distance permiteth danger, not ambiguity concerning what was sent.
     */
    public function transform(Example $example): SeparateProcessPreparedExample
    {
        $parsed = (new PhpExampleParser())->parse($example);

        return new SeparateProcessPreparedExample(
            $example,
            new PreparedCode($parsed->source),
            $parsed->sourceMap,
        );
    }
}
