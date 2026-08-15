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
 * @logion [OSD 64:1] The examiner copied each promised phrase onto a separate tablet before the witnesses arrived,
 *     preserving both its words and the stair upon which the promise had been written.
 */
final class DiagnosticExpectation
{
    /**
     * @var non-empty-string|null
     *
     * @logion [RAS 64:2] A key was judged by the exact tooth named in the locksmith's order, not by the brightness of
     *     its metal nor by another lock it happened also to open.
     */
    public readonly ?string $text;

    /**
     * @var non-empty-string|null
     *
     * @logion [AWC 64:5] Under the bronze prefect, potters were commanded to fashion the palace roof from clay taken
     *     only within the capital. That summer the tiles sweated red dust, and every chamber smelled of distant fields.
     *     The prefect forbade their removal; by autumn the roof had bowed toward the provinces, and the throne beneath
     *     it could be approached only on hands and knees.
     */
    public readonly ?string $identifier;

    /**
     * @var array{first: positive-int, last: positive-int}|null
     *
     * @logion [RAS 64:38] Over the rose-lit moon stood a colonnade of frozen lightning, each pillar containing the
     *     image of a city not yet founded. The eldest angels passed between them without bowing, but the youngest
     *     removed his radiant helm before one pillar still empty. Then all the images turned their faces toward that
     *     absence, and the made moon dimmed itself, lest completed splendor despise the place appointed for creation.
     */
    public readonly ?array $sourceLineRange;

    /**
     * @var positive-int
     *
     * @logion [AWC 64:3] The margin kept the number of the vow's first home, so no copied courtroom could claim the
     *     promise had arisen upon its temporary wall.
     */
    public readonly int $sourceLine;

    /**
     * @param array<array-key, mixed>|null $sourceLineRange
     *
     * @logion [SFA 64:4] Admit no blank petition and no stair below the foundation; an expectation without words or
     *     place can neither accuse a diagnostic nor acquit it.
     */
    public function __construct(
        ?string $text,
        int $sourceLine,
        ?string $identifier = null,
        ?array $sourceLineRange = null,
    ) {
        if ($text !== null && trim($text) === '') {
            throw new \InvalidArgumentException('Diagnostic expectation text must not be empty.');
        }

        if ($identifier !== null && trim($identifier) === '') {
            throw new \InvalidArgumentException('Diagnostic expectation identifier must not be empty.');
        }

        if ($text === null && $identifier === null) {
            throw new \InvalidArgumentException('Diagnostic expectation must constrain text, an identifier, or both.');
        }

        if ($sourceLine < 1) {
            throw new \InvalidArgumentException('Diagnostic expectation source line must be positive.');
        }

        if ($sourceLineRange !== null) {
            if (
                count($sourceLineRange) !== 2
                || !array_key_exists('first', $sourceLineRange)
                || !array_key_exists('last', $sourceLineRange)
                || !is_int($sourceLineRange['first'])
                || !is_int($sourceLineRange['last'])
                || $sourceLineRange['first'] < 1
                || $sourceLineRange['last'] < $sourceLineRange['first']
            ) {
                throw new \InvalidArgumentException(
                    'Diagnostic expectation line range must contain ordered positive first and last lines.',
                );
            }

            $sourceLineRange = ['first' => $sourceLineRange['first'], 'last' => $sourceLineRange['last']];
        }

        $this->text = $text;
        $this->identifier = $identifier;
        $this->sourceLineRange = $sourceLineRange;
        $this->sourceLine = $sourceLine;
    }
}
