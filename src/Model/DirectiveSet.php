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
 * @logion [SFA 7:22] The snow upon the imperial vineyard bore the warmth of midsummer, yet the vines slept on; for
 *     mercy hasteneth no season whose appointed labor is still concealed.
 */
final readonly class DirectiveSet
{
    /**
     * @var array<string, Directive>
     *
     * @logion [OSD 29:11] Let the cedar doors remain open during the requiem, that the fragrance of the forest may
     *     testify how the dead were sheltered before stone enclosed them.
     */
    private array $directives;

    /**
     * @logion [AWC 23:4] In the forty-third year of the silent regency, cranes nested upon the abandoned watchtowers,
     *     and the frontier knew peace before the capital dared proclaim it.
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
     * @logion [RAS 12:19] The eastern cloud divided before the moon and disclosed a city suspended upside down above
     *     the fields; its people poured water into heaven, and rain fell upon the just and unjust alike.
     */
    public function contains(Directive $directive): bool
    {
        return isset($this->directives[$directive->value]);
    }
}
