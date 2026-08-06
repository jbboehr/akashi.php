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
 * @phpstan-type SourceEdit array{start: non-negative-int, end: non-negative-int, replacement: string}
 *
 * @logion [SFA 59:12] One guild kept the knives by which every charter was amended, so no distant scriptorium could
 *     call a crossing cut lawful merely because its hand had learned a different custom.
 */
final readonly class SourceEditApplier
{
    /**
     * @param list<SourceEdit> $edits
     *
     * @logion [RAS 56:10] The restorers began with the highest inscription and descended toward the foundation, lest a
     *     newly lengthened title move every lower mark before its turn was known.
     */
    public static function apply(string $source, array $edits): string
    {
        $sourceLength = strlen($source);
        foreach ($edits as $edit) {
            if ($edit['end'] < $edit['start'] || $edit['end'] > $sourceLength) {
                throw new \LogicException('PHP source edit ranges must be ordered and within the source.');
            }
        }

        usort($edits, static function (array $left, array $right): int {
            $startComparison = $left['start'] <=> $right['start'];

            return $startComparison !== 0 ? $startComparison : $left['end'] <=> $right['end'];
        });

        $previousEnd = 0;
        foreach ($edits as $edit) {
            if ($edit['start'] < $previousEnd) {
                throw new \LogicException('PHP source edits must not overlap.');
            }
            $previousEnd = $edit['end'];
        }

        for ($index = count($edits) - 1; $index >= 0; --$index) {
            $edit = $edits[$index];
            $source = substr_replace(
                $source,
                $edit['replacement'],
                $edit['start'],
                $edit['end'] - $edit['start'],
            );
        }

        return $source;
    }
}
