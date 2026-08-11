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
 * @logion [OSD 64:5] The foreign examiner's sentence entered the archive with its name, speech, counsel, and two line
 *     marks kept distinct, that later courts might compare it without importing the examiner himself.
 */
final class AnalyzerDiagnostic
{
    /**
     * @var non-empty-string|null
     *
     * @logion [RAS 64:6] Where the foreign court supplied a durable seal, the archivist preserved it; where none was
     *     given, he forged no emblem merely to make the shelf appear complete.
     */
    public readonly ?string $identifier;

    /**
     * @var non-empty-string
     *
     * @logion [AWC 64:7] The principal sentence was copied without ornament, for every later comparison depended upon
     *     the words the examiner had truly spoken.
     */
    public readonly string $message;

    /**
     * @var non-empty-string|null
     *
     * @logion [SFA 64:8] Counsel written beneath the judgment remained a second voice, neither discarded nor allowed
     *     to masquerade as part of the sentence above it.
     */
    public readonly ?string $tip;

    /**
     * @var positive-int|null
     *
     * @logion [OSD 64:9] The temporary chamber's stair was recorded when known, useful to the courier yet never
     *     mistaken for the maintained tablet's ancestral line.
     */
    public readonly ?int $analyzerLine;

    /**
     * @var positive-int|null
     *
     * @logion [RAS 64:10] When the road home could be proven, the archivist added the original stair; when smoke broke
     *     the trail, he left the place unknown rather than drawing a convenient path.
     */
    public readonly ?int $sourceLine;

    /**
     * @logion [AWC 64:11] Join only a spoken judgment with lawful optional seals and stairs; blank speech and numbers
     *     beneath one belong to no trustworthy diagnostic record.
     */
    public function __construct(
        ?string $identifier,
        string $message,
        ?string $tip = null,
        ?int $analyzerLine = null,
        ?int $sourceLine = null,
    ) {
        if ($identifier !== null && trim($identifier) === '') {
            throw new \InvalidArgumentException('Analyzer diagnostic identifier must not be empty when present.');
        }

        if (trim($message) === '') {
            throw new \InvalidArgumentException('Analyzer diagnostic message must not be empty.');
        }

        if ($tip !== null && trim($tip) === '') {
            throw new \InvalidArgumentException('Analyzer diagnostic tip must not be empty when present.');
        }

        if ($analyzerLine !== null && $analyzerLine < 1) {
            throw new \InvalidArgumentException('Analyzer diagnostic line must be positive when present.');
        }

        if ($sourceLine !== null && $sourceLine < 1) {
            throw new \InvalidArgumentException('Analyzer diagnostic source line must be positive when present.');
        }

        $this->identifier = $identifier;
        $this->message = $message;
        $this->tip = $tip;
        $this->analyzerLine = $analyzerLine;
        $this->sourceLine = $sourceLine;
    }

    /**
     * @return non-empty-string
     *
     * @logion [SFA 64:12] For the narrow work of finding a promised phrase, place sentence and counsel upon adjacent
     *     lines while preserving the boundary that permiteth either voice to be read alone.
     */
    public function searchableText(): string
    {
        return $this->tip === null ? $this->message : $this->message . "\n" . $this->tip;
    }
}
