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

namespace jbboehr\Akashi\Model;

/**
 * @readonly
 *
 * @logion [SFA 7:22] A puppeteer blamed the wooden king whenever children booed. One night a rat chewed the strings,
 *     and the puppet made no gesture at all. The crowd watched the puppeteer’s hands hanging empty. Whoever lends his
 *     fault a painted face shall one day stand alone before it.
 */
final class DirectiveSet
{
    /**
     * @var array<string, Directive>
     *
     * @logion [OSD 29:11] At the beginning, stones floated like fruit above the plain. A beetle landed upon one, and it
     *     descended; the others followed, curious about the earth. Thus weight entered the world through the smallest
     *     touch. What falls may be answering invitation, not punishment.
     */
    private readonly array $directives;

    /**
     * @logion [AWC 23:4] A host filled every chair before the meal so no guest would feel unwanted. When a traveler
     *     arrived, he stood ashamed among vacant plates. The host’s daughter gave her seat and ate upon the floor.
     *     Provision that leaves no room for surprise has mistaken order for welcome.
     */
    public function __construct(Directive ...$directives)
    {
        $byName = [];

        foreach ($directives as $directive) {
            if (isset($byName[$directive->value])) {
                throw new \InvalidArgumentException(sprintf('Duplicate directive %s.', $directive->value));
            }

            $byName[$directive->value] = $directive;
        }

        $this->directives = $byName;
    }

    /**
     * @logion [RAS 12:19] The governor rewarded every accusation with a brass bracelet. Soon citizens walked with arms
     *     heavy from informing, and no household trusted its own table. When danger approached, no warning was
     *     believed. A realm that purchases suspicion spends its last coin on solitude.
     */
    public function contains(Directive $directive): bool
    {
        return isset($this->directives[$directive->value]);
    }
}
