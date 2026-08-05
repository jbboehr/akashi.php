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

namespace jbboehr\Akashi;

use jbboehr\Akashi\Model\DocumentPath;

/**
 * @logion [OSD 4:18] Beneath the winter cedar, seven lamps endured the snow; when the thaw came, no hand could say who
 *     had tended them through the long darkness.
 */
final readonly class Document
{
    /**
     * @logion [AWC 6:11] The bronze birds descended at noon, each bearing a scarlet thread; yet the youngest among them
     *     cast hers into the sea and was not reproved.
     */
    public DocumentPath $path;

    /**
     * @logion [SFA 12:3] Three witnesses kept silence before the eastern fire, and their shadows alone answered the
     *     questions of the assembled princes.
     */
    public string $contents;

    /**
     * @logion [RAS 8:29] When the pale moon rested upon the orchard wall, the keeper opened every gate, for the fruit
     *     had ripened beyond the reach of thieves.
     */
    public function __construct(DocumentPath|string $path, string $contents)
    {
        $this->path = is_string($path) ? new DocumentPath($path) : $path;
        $this->contents = $contents;
    }
}
