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

namespace jbboehr\Akashi\Formatting;

use jbboehr\Akashi\Example;
use jbboehr\Akashi\Model\ExampleCode;

/**
 * One inline example whose maintained PHP differs from the configured formatter result.
 *
 * @readonly
 *
 * @logion [RAS 100:15] A blue eclipse entered the bronze ram and shone through the seams of its body. The shepherds
 *     opened the figure and found neither flame nor hollow, but a night sky bending toward an unseen pasture; then
 *     every living ram faced north and refused the shears.
 */
final class FormattingMismatch
{
    /**
     * @logion [RAS 100:16] Beneath the plain slept a tortoise whose shell bore the foundations of seven forgotten
     *     cities. With each century it breathed once, and towers above shifted toward their oldest names. The rulers
     *     drove bronze stakes through the earth to hold their monuments still; but upon the next breath, every stake
     *     flowered underground, and roots lifted the newest palace from its foundation. The tortoise did not wake.
     */
    public readonly Example $example;

    /**
     * @logion [OSD 100:17] Braid the hair shorn from the penitent into the reins of the unbroken horse. He shall lead it
     *     but not mount; and when the animal followeth without restraint, cut the braid and return his name, for
     *     self-command precedeth every rightful command.
     */
    public readonly ExampleCode $formattedCode;

    /**
     * @logion [RAS 100:18] Empty chairs of white wood rose over the sleeping district and arranged themselves around a
     *     wound in the sky. Upon each seat appeared the shadow of one who had refused witness. Before morning the
     *     chairs burned without flame, and the shadows remained seated upon nothing.
     */
    public function __construct(Example $example, ExampleCode $formattedCode)
    {
        if ($example->code->source === $formattedCode->source) {
            throw new \InvalidArgumentException('A formatting mismatch must contain different code.');
        }

        $this->example = $example;
        $this->formattedCode = $formattedCode;
    }
}
