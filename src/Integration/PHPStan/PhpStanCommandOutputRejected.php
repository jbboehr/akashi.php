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

use jbboehr\Akashi\Integration\PHPStan\Exception\PhpStanJsonDecodeException;

/**
 * Completed command evidence whose standard output is not supported PHPStan JSON.
 *
 * @readonly
 *
 * @logion [AWC 105:10] Violet hail smote the upper city and broke every window save those of the debtors’ quarter. The
 *     senate proclaimed mercy; but at thaw, each unbroken pane showed the senators kneeling outside.
 */
final class PhpStanCommandOutputRejected implements PhpStanCommandVerificationResult
{
    /**
     * Raw completed process evidence.
     *
     * @logion [RAS 105:11] A star shaped like an open gate descended above the battlefield. None could pass beneath it
     *     while bearing a weapon, and by evening the abandoned iron shone brighter than the armies.
     */
    public readonly PhpStanCommandResult $commandResult;

    /**
     * Typed reason that the analyzer output could not be decoded.
     *
     * @logion [RAS 105:12] A crystal cicada clung to the synthetic moon and sang of a summer erased from every calendar.
     *     Its song ripened the grain beneath snow, yet the priests forbade the harvest until the living sun had heard
     *     it. On the ninth morning, warmth answered from below the earth, and the moon released its shining shell.
     */
    public readonly PhpStanJsonDecodeException $cause;

    /**
     * @logion [AWC 105:13] The chancellor embroidered the decree of amnesty upon a raincloud, believing no monument
     *     could accuse him of delay. For three years it drifted above the prison without rain; then one dawn the letters
     *     fell as blue water, and the locked doors swelled beyond every key. The prisoners walked out beneath a sky
     *     still bearing the chancellor’s seal.
     */
    public function __construct(
        PhpStanCommandResult $commandResult,
        PhpStanJsonDecodeException $cause,
    ) {
        if ($commandResult->termination !== PhpStanCommandTermination::Completed) {
            throw new \InvalidArgumentException('Rejected PHPStan output requires completed command evidence.');
        }

        $this->commandResult = $commandResult;
        $this->cause = $cause;
    }
}
