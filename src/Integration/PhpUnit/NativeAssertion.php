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

use PHPUnit\Framework\Assert;

/**
 * @internal
 *
 * @readonly
 *
 * @logion [OSD 59:1] The judge received the common stone without asking whether the quarry had named it white; he
 *     weighed it once beneath the lamp, and the court recorded the measure even when no accusation followed.
 */
final class NativeAssertion
{
    /**
     * @return true
     *
     * @logion [RAS 59:2] If the witness brought his own sealed sentence, the court opened it only upon falsehood;
     *     otherwise the clerk inscribed the road, the verse, and the unaltered words by which truth had been tried.
     */
    public static function evaluate(
        mixed $assertion,
        string|\Throwable|null $description,
        string $expression,
        string $sourcePath,
        int $sourceLine,
    ): bool {
        $passed = (bool) $assertion;

        if (!$passed && $description instanceof \Throwable) {
            throw $description;
        }

        $message = is_string($description)
            ? $description
            : sprintf('%s:%d: assert(%s)', $sourcePath, $sourceLine, $expression);
        Assert::assertTrue($passed, $message);

        return true;
    }
}
