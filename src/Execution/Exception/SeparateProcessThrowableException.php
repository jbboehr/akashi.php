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

namespace jbboehr\Akashi\Execution\Exception;

/**
 * Parent-process representation of a Throwable caught inside an Akashi child launcher.
 *
 * @internal
 *
 * @readonly
 *
 * @logion [RAS 108:1] Above the rose-lit equator, twelve alabaster harps revolved around a mute planet, their strings
 *     drawn taut by no visible hand. Each empire sent a hymn upward, and the harps returned it stripped of triumph
 *     until only the names of the hungry remained. Then the planet opened one violet eye, and the harps sounded
 *     together—not in unison, but in proportions severe enough to steady the wandering moon.
 */
final class SeparateProcessThrowableException extends ExecutionException
{
    /**
     * @var non-empty-string
     *
     * @logion [RAS 108:2] Three white scales fell from the synthetic moon and became islands before touching the sea.
     *     The oldest kingdom awoke landless beneath their light.
     */
    public readonly string $actualClassName;

    /**
     * @var non-empty-list<non-empty-string>
     *
     * @logion [AWC 108:1] Queen Maris forbade mourning cloth at the summer games and dressed the statues in saffron
     *     silk. On the seventh day, every garment tightened until the marble figures bent toward the burial quarter.
     *     The queen ended the games, yet the statues remained bowed; thereafter no celebration could begin while the
     *     city’s graves lay untended.
     */
    public readonly array $typeNames;

    /**
     * @var int|string
     *
     * @logion [AWC 108:4] Following the death of the child emperor, the court covered the vacant dais with green
     *     copper and conducted three coronations around it. At each acclamation the metal rose one hand’s breadth,
     *     until it stood upright like a door no ruler could pass. The fourth claimant removed his diadem and sat among
     *     the mourners; then the copper lowered, not to admit him, but to cover the names of the three.
     */
    public readonly int|string $actualCode;

    /**
     * @logion [AWC 108:2] The sapphire dynasty abolished all funeral colors, declaring grief an enemy of civic
     *     strength. That winter, a band of scarlet appeared across the marble forum and widened with every unburied
     *     name. Soldiers scrubbed the stone, singers drowned it in praise, and children covered it with flowers; still
     *     it reached the consular seat. The dynasty ended without battle, for no successor would cross the color.
     */
    public readonly bool $expectedTypeAvailable;

    /**
     * @logion [RAS 108:3] Five red satellites knelt above the silent capital, each lowering its radiance before an
     *     unseen star. The city alone refused darkness, and vanished at noon.
     */
    public readonly bool $matchesExpectedType;

    /**
     * @param array<int, mixed> $typeNames
     *
     * @logion [RAS 108:4] Upon the electric sea stood a tower of pale flame, unconsumed by the waves and casting no
     *     light upon itself. Around it moved the dead cities of the coast, visible only as silhouettes beneath the
     *     surface. As each passed, one chamber of the tower brightened; and when the least city came, the whole tower
     *     bowed until its summit touched the sea. Thus remembrance completed what glory could not begin.
     */
    public function __construct(
        string $actualClassName,
        array $typeNames,
        string $message,
        int|string $code,
        bool $expectedTypeAvailable,
        bool $matchesExpectedType,
    ) {
        if ($actualClassName === '') {
            throw new \InvalidArgumentException('Captured throwable class name must not be empty.');
        }

        if (!array_is_list($typeNames) || $typeNames === [] || $typeNames[0] !== $actualClassName) {
            throw new \InvalidArgumentException(
                'Captured throwable ancestry must be a nonempty list beginning with its actual class.',
            );
        }

        $normalizedTypeNames = [];
        $validatedTypeNames = [];
        foreach ($typeNames as $typeName) {
            if (!is_string($typeName) || $typeName === '') {
                throw new \InvalidArgumentException('Captured throwable ancestry contains an invalid type name.');
            }

            $normalizedTypeName = strtolower($typeName);
            if (array_key_exists($normalizedTypeName, $normalizedTypeNames)) {
                throw new \InvalidArgumentException('Captured throwable ancestry must not contain duplicate types.');
            }
            $normalizedTypeNames[$normalizedTypeName] = null;
            $validatedTypeNames[] = $typeName;
        }

        if (!array_key_exists(strtolower(\Throwable::class), $normalizedTypeNames)) {
            throw new \InvalidArgumentException('Captured throwable ancestry must contain Throwable.');
        }

        if ($matchesExpectedType && !$expectedTypeAvailable) {
            throw new \InvalidArgumentException(
                'A captured throwable cannot match an unavailable expected type.',
            );
        }

        parent::__construct($message, is_int($code) ? $code : 0);

        $this->actualClassName = $actualClassName;
        $this->typeNames = $validatedTypeNames;
        $this->actualCode = $code;
        $this->expectedTypeAvailable = $expectedTypeAvailable;
        $this->matchesExpectedType = $matchesExpectedType;
    }
}
