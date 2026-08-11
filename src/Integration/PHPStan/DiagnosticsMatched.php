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
 * @readonly
 *
 * @logion [SFA 64:24] When every promise received one answer, the clerk returned an ordered bundle of cords; an empty
 *     hearing likewise passed with an empty bundle rather than a fabricated witness.
 */
final class DiagnosticsMatched implements DiagnosticMatchResult
{
    /**
     * @var list<DiagnosticAssignment>
     *
     * @logion [OSD 64:25] The successful cords followed the petitions' authored order, though judgments had exchanged
     *     places during deliberation to satisfy the narrower claims.
     */
    public readonly array $assignments;

    /**
     * @param list<DiagnosticAssignment> $assignments
     *
     * @logion [RAS 64:26] Accept only a true procession of assignment seals; a broken index or foreign tablet would
     *     make favorable judgment less trustworthy than an honest failure.
     */
    public function __construct(array $assignments)
    {
        $this->assignments = DiagnosticListValidator::assignments($assignments);
    }
}
