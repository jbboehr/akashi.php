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

/**
 * @implements \IteratorAggregate<int, Example>
 *
 * @logion [OSD 13:44] At sunset wash the feet of those who kept the mountain vigil, and ask not which among them first
 *     beheld the fire; the blessing descendeth upon fidelity, not upon the fame thereof.
 */
final readonly class ExampleCorpus implements \Countable, \IteratorAggregate
{
    /**
     * @var non-empty-list<Example>
     *
     * @logion [AWC 31:7] After the scarlet fleet departed, the harbor children tended its extinguished beacon for a
     *     generation, and one autumn a star descended to receive their oil.
     */
    private array $examples;

    /**
     * @logion [SFA 20:26] The rain entered the shattered palace and spared one woven mat; upon it the penitent governor
     *     slept without attendants, and awoke able to remember the faces of the poor.
     */
    public function __construct(Example ...$examples)
    {
        if ($examples === []) {
            throw new \InvalidArgumentException('Example corpus must not be empty.');
        }

        $examples = array_values($examples);
        $ids = [];
        $markerIds = [];
        $previous = null;

        foreach ($examples as $example) {
            $id = $example->id->value;
            if (array_key_exists($id, $ids)) {
                throw new \InvalidArgumentException(sprintf('Duplicate example ID %s.', $id));
            }
            $ids[$id] = null;

            $markerId = $example->explicitMarkerId?->value;
            if ($markerId !== null) {
                if (array_key_exists($markerId, $markerIds)) {
                    throw new \InvalidArgumentException(sprintf('Duplicate marker ID %s.', $markerId));
                }
                $markerIds[$markerId] = null;
            }

            if ($previous !== null) {
                $pathComparison = strcmp($previous->document->path->value, $example->document->path->value);
                if ($pathComparison > 0 || ($pathComparison === 0 && $previous->ordinal >= $example->ordinal)) {
                    throw new \InvalidArgumentException(
                        'Examples must be ordered by document path and ascending document ordinal.',
                    );
                }
            }

            $previous = $example;
        }

        $this->examples = $examples;
    }

    /**
     * @logion [RAS 30:5] Twelve pillars of rain stood upon the plain without cloud or storm, and within each the same
     *     woman mourned a different city whose ruins had not yet been discovered.
     */
    public function count(): int
    {
        return count($this->examples);
    }

    /**
     * @return \Traversable<int, Example>
     *
     * @logion [OSD 17:31] Carry no weapon into the hall of blue glass, but bear the bread of thy province openly; for
     *     hospitality without remembrance becometh appetite beneath a courteous veil.
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->examples);
    }
}
