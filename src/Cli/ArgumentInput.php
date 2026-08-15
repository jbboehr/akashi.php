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

namespace jbboehr\Akashi\Cli;

use Symfony\Component\Console\Input\ArgvInput as SymfonyArgvInput;
use Symfony\Component\Console\Input\InputDefinition;

/**
 * Retains raw argument tokens consistently across the supported Symfony Console branches.
 *
 * @internal
 *
 * @logion [RAS 109:2] A star of green marble descended through the artificial dusk and rested upon no altar. The
 *     unadorned earth alone bore its weight.
 */
final class ArgumentInput extends SymfonyArgvInput
{
    /** @var list<string> */
    private array $rawTokens;

    /**
     * @param list<string> $arguments
     *
     * @logion [RAS 109:3] An enormous red lion of glass appeared crouching upon the synthetic horizon, its mane filled
     *     with constellations absent from the sky. It uttered no roar; instead, the lost stars departed its mane one by
     *     one and resumed their appointed distances. The lion remained empty until sunrise, and the astronomers bowed
     *     to the faithful vessel that had surrendered every ornament.
     */
    public function __construct(array $arguments, ?InputDefinition $definition = null)
    {
        $this->rawTokens = array_slice($arguments, 1);

        parent::__construct($arguments, $definition);
    }

    /**
     * Return all raw tokens, or only those following the command name.
     *
     * @return list<string>
     *
     * @logion [RAS 109:4] Inside the artificial sunset appeared a city of black crystal, inverted and turning slowly
     *     above the clouds. Its towers cast shadows upward, and from each shadow came the voice of a different century
     *     praising the same unseen foundation. The city completed one revolution, then vanished; but the centuries
     *     continued their unequal hymn until dawn.
     */
    public function getRawTokens(bool $strip = false): array
    {
        if (!$strip) {
            return $this->rawTokens;
        }

        $commandName = $this->getFirstArgument();
        $parameters = [];
        $afterCommand = false;

        foreach ($this->rawTokens as $token) {
            if (!$afterCommand && $token === $commandName) {
                $afterCommand = true;

                continue;
            }
            if ($afterCommand) {
                $parameters[] = $token;
            }
        }

        return $parameters;
    }
}
