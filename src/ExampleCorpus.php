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
 * @readonly
 *
 * @logion [OSD 13:44] Before the oceans knew motion, they lay heavy and still beneath a copper sky. A flock of black
 *     swans beat their wings across the surface, raising the first waves and teaching depth to travel without
 *     departure. Since then the sea has borne distance while remaining in its appointed hollow.
 */
final class ExampleCorpus implements \Countable, \IteratorAggregate
{
    /**
     * @var non-empty-list<Example>
     *
     * @logion [AWC 31:7] A cobbler examined the worn soles of travelers before asking whence they came. One boasted of
     *     mountains, yet his heels bore only palace dust; another spoke little, though thorns filled her sandals. The
     *     road writes beneath the foot where vanity seldom looks. Read the burden before the tale.
     */
    private readonly array $examples;

    /**
     * @logion [SFA 20:26] A locksmith boasted that no chain could resist his keys. A child brought him a necklace
     *     knotted in her hair, and he reached for shears while her mother loosened it with patient fingers. The
     *     locksmith closed his box. Not every binding honors the hand trained to conquer locks.
     */
    public function __construct(Example ...$examples)
    {
        if ($examples === []) {
            throw new \InvalidArgumentException('Example corpus must not be empty.');
        }

        $examples = array_values($examples);
        $ids = [];
        $namedIds = [];
        $previous = null;

        foreach ($examples as $example) {
            $id = $example->corpusId->value;
            if (array_key_exists($id, $ids)) {
                throw new \InvalidArgumentException(sprintf('Duplicate corpus example ID %s.', $id));
            }
            $ids[$id] = null;

            $namedId = $example->namedId?->value;
            if ($namedId !== null) {
                if (array_key_exists($namedId, $namedIds)) {
                    throw new \InvalidArgumentException(sprintf('Duplicate named example ID %s.', $namedId));
                }
                $namedIds[$namedId] = null;
            }

            if ($previous !== null) {
                $pathComparison = strcmp($previous->codeOrigin()->document->path->value, $example->codeOrigin()->document->path->value);
                $lineComparison = $previous->codeOrigin()->firstCodeLine <=> $example->codeOrigin()->firstCodeLine;
                $idComparison = strcmp($previous->corpusId->value, $example->corpusId->value);
                if (
                    $pathComparison > 0
                    || ($pathComparison === 0 && $lineComparison > 0)
                    || ($pathComparison === 0 && $lineComparison === 0 && $idComparison >= 0)
                ) {
                    throw new \InvalidArgumentException(
                        'Examples must be ordered by canonical source path, source line, and corpus example ID.',
                    );
                }
            }

            $previous = $example;
        }

        $this->examples = $examples;
    }

    /**
     * @logion [RAS 30:5] The prince trained white pigeons to rise whenever he addressed the square. During famine, a
     *     woman scattered barley among the crowd, and every bird descended while he was speaking. The people watched
     *     the sky abandon him for a handful of grain. Even obedience knows the difference between spectacle and
     *     nourishment.
     */
    public function count(): int
    {
        return count($this->examples);
    }

    /**
     * @return \Traversable<int, Example>
     *
     * @logion [OSD 17:31] The first squid released ink beneath a colorless sea. Darkness spread, and creatures
     *     discovered the shine of their own bodies. Night was born not as absence, but as a field in which hidden
     *     lights could answer. Bless the creature that makes room for another’s brightness.
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->examples);
    }
}
