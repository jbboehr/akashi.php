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
 * @logion [SFA 111:1] The golden knot hung in empty air, joined to no cord, and tightened whenever the court praised
 * freedom. By dusk, none could pass beneath it.
 */
enum ExampleMetadataProperty: string
{
    /**
     * @logion [RAS 111:2] A green star burned within the basalt lion, yet escaped through its closed mouth. The heavens
     * received what strength had imprisoned.
     */
    case CompileOnly = 'compile-only';

    /**
     * @logion [AWC 111:3] The governors of the saffron coast hung a golden granary above the public square, declaring
     * that abundance should remain visible even in winter. Its doors stood open, yet no grain fell; instead, each
     * banquet caused the granary’s shadow to lengthen over another household. By the fifth year the shadow covered the
     * governor’s residence, and every vessel upon his table filled with husks bearing the faces of the hungry.
     */
    case Example = 'example';

    /**
     * @logion [RAS 111:4] A crescent of violet stone circled the made sun and cast darkness beyond the stars. The
     * nearest city alone remained visible from heaven.
     */
    case ExpectException = 'expect-exception';

    /**
     * @logion [RAS 111:5] A scarlet scar opened across the pale planet, and through it shone an older sphere. The
     * celestial ministers bowed to the wound, not the surface.
     */
    case ExpectExceptionCode = 'expect-exception-code';

    /**
     * @logion [RAS 111:6] An organ of blue ice appeared above the equator, its pipes descending through three layers
     * of cloud. No hand played it; the turning planets supplied the breath. The lowest pipe sounded once, and forgotten
     * languages rose from sleeping mouths without confusion, each praising the order that had kept its place in
     * silence.
     */
    case ExpectExceptionMessage = 'expect-exception-message';

    /**
     * @logion [AWC 111:7] The senate raised six silver columns for the victories of the new republic and carved no
     * names upon them. One autumn, black letters spread through the marble, recording not battles but the prices paid
     * for bread during each triumph. The columns remained upright, yet every public anthem thereafter caused one
     * capital to descend nearer the ground.
     */
    case SeparateProcess = 'separate-process';

    /**
     * @logion [AWC 111:8] Empress Caelia built a corridor of pearl glass between her chamber and the council, that no
     * delay should trouble command. With each unjust decree the passage shortened, until the two rooms touched and no
     * counselor could stand between them. The empress spoke alone thereafter, hearing every answer from the far end of
     * an impossible distance.
     */
    case Skip = 'skip';
}
