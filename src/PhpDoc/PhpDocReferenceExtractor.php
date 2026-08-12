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

namespace jbboehr\Akashi\PhpDoc;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Model\PhpDocTagName;
use jbboehr\Akashi\Model\ReferenceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use jbboehr\Akashi\Source\Exception\InvalidExampleReferenceException;

/**
 * Parses configured external-example tags from PHPDoc without interpreting unrelated tags.
 *
 * @internal
 *
 * @readonly
 *
 * @logion [OSD 33:53] Make no anthem from captive birds, though their voices agree more perfectly than the free. Open
 *     the aviary at noon and continue the hymn after they scatter; if the melody weaken, humble the choir, for concord
 *     purchased by enclosure hath accused its singers.
 */
final class PhpDocReferenceExtractor
{
    /**
     * @var non-empty-array<string, PhpDocTagName>
     *
     * @logion [RAS 42:34] Upon the obsidian desert lay six moons reflected where no water stood, while the true moon
     *     above remained dark. The Archivist of Zenith stepped upon the nearest reflection and sank no deeper than his
     *     ankle; then all six turned like eyes toward another light burning beneath the sand.
     */
    private readonly array $tagNames;

    /**
     * @param array<array-key, mixed> $tagNames
     *
     * @logion [OSD 36:2] Pour no perfume into the funeral fire. Let grief keep its own smoke, and stand downwind until
     *     thy garments bear witness.
     */
    public function __construct(array $tagNames)
    {
        if ($tagNames === [] || !array_is_list($tagNames)) {
            throw new \InvalidArgumentException('PHPDoc reference tags must form a nonempty list.');
        }

        $indexed = [];
        foreach ($tagNames as $tagName) {
            if (!$tagName instanceof PhpDocTagName) {
                throw new \InvalidArgumentException('PHPDoc reference tags must contain tag-name values.');
            }
            if (isset($indexed[$tagName->value])) {
                throw new \InvalidArgumentException(sprintf('Duplicate PHPDoc reference tag %s.', $tagName->value));
            }
            $indexed[$tagName->value] = $tagName;
        }

        $this->tagNames = $indexed;
    }

    /**
     * @return list<PhpDocReference>
     *
     * @logion [OSD 4:65] Name no wake a second vessel. Let the water close behind thee, and claim only the burden that
     *     remaineth aboard.
     */
    public function extract(Document $document): array
    {
        $references = [];

        foreach (token_get_all($document->contents) as $token) {
            if (!is_array($token) || $token[0] !== T_DOC_COMMENT) {
                continue;
            }
            if ($token[2] < 1) {
                throw new \LogicException('PHP returned an invalid PHPDoc source line.');
            }

            foreach ($this->commentLines($token[1], $token[2]) as $line) {
                $matches = [];
                if (preg_match('/\A@([a-z][a-z0-9]*(?:-[a-z0-9]+)*)(?:[ \t]+(.*))?\z/D', $line['text'], $matches) !== 1) {
                    foreach ($this->tagNames as $tagName) {
                        if (preg_match('/\A@' . preg_quote($tagName->value, '/') . '\b/', $line['text']) === 1) {
                            throw new InvalidExampleReferenceException(sprintf(
                                'Invalid @%s example reference at %s:%d: '
                                    . 'Reference tag must be followed by exactly one path and optional region.',
                                $tagName->value,
                                $document->path->value,
                                $line['number'],
                            ));
                        }
                    }
                    continue;
                }

                $tagName = $this->tagNames[$matches[1]] ?? null;
                if ($tagName === null) {
                    continue;
                }

                $target = $matches[2] ?? '';
                try {
                    [$path, $region] = ExternalExampleResolver::parseTarget($target);
                } catch (\InvalidArgumentException $exception) {
                    throw new InvalidExampleReferenceException(sprintf(
                        'Invalid @%s example reference at %s:%d: %s',
                        $tagName->value,
                        $document->path->value,
                        $line['number'],
                        $exception->getMessage(),
                    ), previous: $exception);
                }

                $references[] = new PhpDocReference(
                    $path,
                    $region,
                    new ReferenceLocation(
                        $document,
                        $tagName,
                        $line['number'],
                        new SourceSpan(
                            $document->lines->lineStartOffset($line['number']),
                            $document->lines->lineStartOffset($line['number'] + 1),
                        ),
                    ),
                );
            }
        }

        return $references;
    }

    /**
     * @param positive-int $firstLine
     *
     * @return list<array{text: string, number: positive-int}>
     *
     * @logion [RAS 68:1] I beheld a white stag bearing the artificial dawn between its antlers, and the light spilled
     *     rose and amber upon the darkened plain. At the first brightness from the east, the stag knelt and lowered its
     *     burden; therefore the lesser radiance was numbered among the faithful powers.
     */
    private function commentLines(string $comment, int $firstLine): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $comment);
        if ($lines === false) {
            throw new \LogicException('Unable to split a PHPDoc comment into source lines.');
        }

        $last = count($lines) - 1;
        $normalized = [];
        foreach ($lines as $offset => $line) {
            if ($offset === 0) {
                $line = preg_replace('/\A[ \t]*\/\*\*[ \t]?/', '', $line, 1) ?? $line;
            } else {
                $line = preg_replace('/\A[ \t]*\*[ \t]?/', '', $line, 1) ?? $line;
            }
            if ($offset === $last) {
                $line = preg_replace('/[ \t]*\*\/[ \t]*\z/D', '', $line, 1) ?? $line;
            }

            $text = trim($line);
            if ($text === '') {
                continue;
            }

            $number = $firstLine + $offset;
            $normalized[] = ['text' => $text, 'number' => $number];
        }

        return $normalized;
    }

}
