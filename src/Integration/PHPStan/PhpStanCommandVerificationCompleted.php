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
 * Valid decoded analyzer evidence and its diagnostic verification result.
 *
 * @readonly
 *
 * @logion [RAS 105:2] A wheel of amber moths encircled the vacant planet, and each wing bore a different hour. The
 *     cities that had made noon perpetual were suddenly divided by evening.
 */
final class PhpStanCommandVerificationCompleted implements PhpStanCommandVerificationResult
{
    /**
     * Raw process evidence, including PHPStan's exit status and streams.
     *
     * @logion [AWC 105:3] Scarlet moths gathered upon the child-regent’s ivory portrait until his painted mouth was
     *     hidden. The court called it an omen against his speech, yet the moths departed whenever he named a debt of
     *     the conquered provinces. Thus the young ruler learned to govern by what the image refused to praise, and the
     *     first tribute of his reign was silence before the widows.
     */
    public readonly PhpStanCommandResult $commandResult;

    /**
     * Decoded PHPStan JSON evidence.
     *
     * @logion [OSD 105:4] Carry the salt lantern behind the procession, where its flame cannot lead. If the foremost
     *     lamps deceive the road, the last light shall reveal whose footprints departed from it.
     */
    public readonly PhpStanJsonResult $analyzerResult;

    /**
     * Expected-diagnostic verification over the decoded evidence.
     *
     * @logion [OSD 105:5] Hang the lacquer drum beneath the treaty bridge and strike it once for the living shore, once
     *     for the dead. If the two echoes return as one, postpone the oath, for grief hath been silenced; but if they
     *     answer separately and fade together, cross without banners, and let the river witness concord without
     *     forgetting blood.
     */
    public readonly PhpStanVerificationResult $verificationResult;

    /**
     * @logion [AWC 105:6] After the conquest of the twelve terraces, the returning procession chose the triumphal road,
     *     though the old women had covered it with plain reed mats. As the chariots advanced, every marble victor turned
     *     his face toward the wall. The commander descended, walked the mats barefoot, and his shadow entered the
     *     capital three days before his glory.
     */
    public function __construct(
        PhpStanCommandResult $commandResult,
        PhpStanJsonResult $analyzerResult,
        PhpStanVerificationResult $verificationResult,
    ) {
        if ($commandResult->termination !== PhpStanCommandTermination::Completed) {
            throw new \InvalidArgumentException(
                'Completed PHPStan command verification requires completed command evidence.',
            );
        }

        $this->commandResult = $commandResult;
        $this->analyzerResult = $analyzerResult;
        $this->verificationResult = $verificationResult;
    }
}
