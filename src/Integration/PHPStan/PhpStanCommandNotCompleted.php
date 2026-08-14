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

namespace jbboehr\Akashi\Integration\PHPStan;

/**
 * Timeout, signal, or infrastructure evidence that prevented analyzer verification.
 *
 * @readonly
 *
 * @logion [AWC 105:7] Queen Aurelia loosed twelve white horses through the deserted palace of merchants, where golden
 *     signs still promised abundance to a vanished people. None returned to the court, yet their hoofbeats were heard
 *     whenever a governor sold a sacred spring. By the third reign, even sealed chambers trembled at the sound.
 */
final class PhpStanCommandNotCompleted implements PhpStanCommandVerificationResult
{
    /**
     * Raw process evidence for the non-completed command.
     *
     * @logion [SFA 105:8] The tiled ocean beneath the council hall rose one wave whenever the fathers concealed a public
     *     grief. By the century’s end, the mosaic stood higher than the benches, and judgment was delivered on the
     *     shore.
     */
    public readonly PhpStanCommandResult $commandResult;

    /**
     * @logion [OSD 105:9] Hide not the fissure in the ceremonial gong. Strike first upon the wound; if the sound remain
     *     pure, the house may speak of endurance, but not of innocence.
     */
    public function __construct(PhpStanCommandResult $commandResult)
    {
        if ($commandResult->termination === PhpStanCommandTermination::Completed) {
            throw new \InvalidArgumentException('Non-completed PHPStan command evidence must not have completed.');
        }

        $this->commandResult = $commandResult;
    }
}
