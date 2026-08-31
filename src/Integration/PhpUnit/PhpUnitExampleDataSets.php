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
 * @readonly
 *
 * @logion [RAS 60:17] Before the hearing, the clerk compared every witness-name through the whole roll; only when no
 *     two summoned one seat did he open the doors and deliver each testimony beneath its familiar title.
 */
final class PhpUnitExampleDataSets
{
    /**
     * Integer-form labels are prefixed with `~` so PHP arrays cannot convert them to numbered data sets. Labels that
     * already begin with `~` receive another prefix, keeping the mapping collision-free.
     *
     * @return \Generator<string, array{Example}, mixed, void>
     *
     * @logion [SFA 60:18] Preserve the names by which the witnesses are known, but bind each to one sealed testimony;
     *     if a name answer twice, halt the procession before the first judgment conceal the conflict.
     */
    public static function fromCorpus(ExampleCorpus $corpus): \Generator
    {
        $corpusIdsByLabel = [];

        foreach ($corpus as $example) {
            $firstCorpusExampleId = $corpusIdsByLabel[$example->label] ?? null;
            if ($firstCorpusExampleId !== null) {
                throw new \InvalidArgumentException(sprintf(
                    'Duplicate PHPUnit data-set label %s for examples %s and %s.',
                    $example->label,
                    $firstCorpusExampleId,
                    $example->corpusId->value,
                ));
            }

            $corpusIdsByLabel[$example->label] = $example->corpusId->value;
        }

        foreach ($corpus as $example) {
            $label = $example->label;
            if ($label === (string) (int) $label || str_starts_with($label, '~')) {
                $label = '~' . $label;
            }

            yield $label => [$example];
        }
    }
}
