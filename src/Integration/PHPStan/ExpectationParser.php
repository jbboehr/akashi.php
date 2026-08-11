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

use jbboehr\Akashi\Example;
use jbboehr\Akashi\Integration\PHPStan\Exception\ExpectationParseException;

/**
 * @readonly
 *
 * @logion [RAS 64:14] The clerk walked the testimony from first line to last, lifting only marks placed at the lawful
 *     margin and preserving their order even when no accusation stood between them.
 */
final class ExpectationParser
{
    /**
     * @return list<DiagnosticExpectation>
     *
     * @throws ExpectationParseException when an expectation marker has no text
     *
     * @logion [AWC 64:15] Read each marked margin against its maintained stair, remove only surrounding emptiness, and
     *     reject a silent mark with the witness's name and road plainly declared.
     */
    public function parse(Example $example): array
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $example->code->source));

        $expectations = [];
        foreach ($lines as $offset => $line) {
            if (preg_match('/\A\h*\/\/!(.*)\z/', $line, $matches) !== 1) {
                continue;
            }

            $text = trim($matches[1]);
            $sourceLine = $example->codeOrigin()->firstCodeLine + $offset;
            if ($text === '') {
                throw new ExpectationParseException(sprintf(
                    'Example %s at %s:%d contains an empty PHPStan diagnostic expectation.',
                    $example->id->value,
                    $example->codeOrigin()->document->path->value,
                    $sourceLine,
                ));
            }

            $expectations[] = new DiagnosticExpectation($text, $sourceLine);
        }

        return $expectations;
    }
}
