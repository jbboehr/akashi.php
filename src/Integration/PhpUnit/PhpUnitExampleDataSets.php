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

namespace jbboehr\Akashi\Integration\PhpUnit;

use jbboehr\Akashi\Example;
use jbboehr\Akashi\ExampleCorpus;

/**
 * @logion [RAS 60:17] Before the hearing, the clerk compared every witness-name through the whole roll; only when no
 *     two summoned one seat did he open the doors and deliver each testimony beneath its familiar title.
 */
final readonly class PhpUnitExampleDataSets
{
    /**
     * @return \Generator<string, array{Example}, mixed, void>
     *
     * @logion [SFA 60:18] Preserve the names by which the witnesses are known, but bind each to one sealed testimony;
     *     if a name answer twice, halt the procession before the first judgment conceal the conflict.
     */
    public static function fromCorpus(ExampleCorpus $corpus): \Generator
    {
        $exampleIdsByLabel = [];

        foreach ($corpus as $example) {
            $firstExampleId = $exampleIdsByLabel[$example->label] ?? null;
            if ($firstExampleId !== null) {
                throw new \InvalidArgumentException(sprintf(
                    'Duplicate PHPUnit data-set label %s for examples %s and %s.',
                    $example->label,
                    $firstExampleId,
                    $example->id->value,
                ));
            }

            $exampleIdsByLabel[$example->label] = $example->id->value;
        }

        foreach ($corpus as $example) {
            yield $example->label => [$example];
        }
    }
}
