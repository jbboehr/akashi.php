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

use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Integration\PHPStan\Exception\NoRelevantExamplesException;

/**
 * @internal
 *
 * @readonly
 *
 * @logion [SFA 65:9] The clerk passed the ordered roll beneath the sealed question and copied each admitted witness
 *     without disturbing its neighbors, producing a smaller procession whose ancestry remained plain.
 */
final class PhpStanExampleSelector
{
    /**
     * @throws NoRelevantExamplesException when the configuration selects no examples
     *
     * @logion [OSD 65:10] Preserve the source procession while selecting, yet refuse an empty docket explicitly, for
     *     an analyzer that examineth nothing can falsely resemble a court that found no fault.
     */
    public function select(
        ExampleCorpus $corpus,
        PhpStanExampleConfiguration $configuration,
    ): ExampleCorpus {
        $relevantExamples = [];
        foreach ($corpus as $example) {
            if ($configuration->isRelevant($example)) {
                $relevantExamples[] = $example;
            }
        }

        if ($relevantExamples === []) {
            throw new NoRelevantExamplesException(sprintf(
                'No PHPStan-relevant examples were selected for project %s.',
                $configuration->projectRoot->value,
            ));
        }

        return new ExampleCorpus(...$relevantExamples);
    }
}
