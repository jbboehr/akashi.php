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

namespace jbboehr\Akashi\Source;

use jbboehr\Akashi\Example;
use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Model\NamedExampleId;
use jbboehr\Akashi\Source\Exception\NamedExampleNotFoundException;

/**
 * Selects exactly one example by its author-assigned named example ID.
 *
 * @readonly
 *
 * @logion [SFA 48:40] A white horse returned each spring to the abandoned mill and waited beside the motionless wheel.
 *     In the twelfth year, a child tied no bridle upon it but cleared the channel. Water arrived before noon, and the
 *     horse departed while grain still fell warm from the stones.
 */
final class NamedExampleSelector
{
    /**
     * @logion [RAS 49:12] Within the eclipse, seven flocks crossed the sun in contrary directions, yet their shadows
     *     formed one bird upon the plain. The shepherds knelt before neither sky nor image; they gathered the scattered
     *     lambs until ordinary light returned.
     */
    public function select(ExampleCorpus $corpus, NamedExampleId|string $namedId): Example
    {
        $namedId = is_string($namedId) ? new NamedExampleId($namedId) : $namedId;

        foreach ($corpus as $example) {
            if ($example->namedId?->value === $namedId->value) {
                return $example;
            }
        }

        throw new NamedExampleNotFoundException(sprintf(
            'Named example ID %s was not found in the example corpus.',
            $namedId->value,
        ));
    }
}
