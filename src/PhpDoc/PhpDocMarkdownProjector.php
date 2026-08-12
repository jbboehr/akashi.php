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

/**
 * Projects conventional PHPDoc interiors onto line-preserving CommonMark documents.
 *
 * @internal
 *
 * @readonly
 *
 * @logion [AWC 98:16] At the surrender, the vacant armor knelt before the defeated child. Thereafter the victors wore
 *     linen until he came of age.
 */
final class PhpDocMarkdownProjector
{
    /**
     * @return list<Document>
     *
     * @logion [AWC 98:17] In the summer of wax, the southern stair softened beneath every noble foot, yet remained hard
     *     beneath porters and children. The household abandoned the upper rooms, and their banners sagged unopened from
     *     the balcony until ivy entered the silk and made a green province there.
     */
    public function project(Document $document): array
    {
        $projections = [];

        foreach (token_get_all($document->contents) as $token) {
            if (!is_array($token) || $token[0] !== T_DOC_COMMENT) {
                continue;
            }

            $markdown = $this->markdown($token[1]);
            if ($markdown !== null) {
                $projections[] = new Document(
                    $document->path,
                    str_repeat("\n", $token[2] - 1) . $markdown,
                );
            }
        }

        return $projections;
    }

    /**
     * @logion [AWC 98:18] For eleven years the caravan traveled beneath a storm that neither advanced nor diminished,
     *     its black rain falling only upon the chief’s pavilion. He forbade his people to scatter, saying the cloud was
     *     the price of their unity. But when he died, the bearers set down the pavilion and found the desert green
     *     beneath it. The clans divided the ground among the poorest, and the storm departed westward without thunder.
     */
    private function markdown(string $comment): ?string
    {
        $parts = preg_split('/(\r\n|\r|\n)/', $comment, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            throw new \LogicException('Unable to split a PHPDoc comment into source lines.');
        }

        /** @var list<array{content: string, ending: string}> $lines */
        $lines = [];
        $partCount = count($parts);
        for ($index = 0; $index < $partCount; $index += 2) {
            $content = $parts[$index];
            $ending = $parts[$index + 1] ?? '';
            if ($content === '' && $ending === '' && $index === $partCount - 1) {
                continue;
            }
            $lines[] = ['content' => $content, 'ending' => $ending];
        }
        if (count($lines) < 3) {
            return null;
        }

        $markdown = '';
        $lastIndex = count($lines) - 1;
        foreach ($lines as $index => $line) {
            if ($index === 0 || $index === $lastIndex) {
                $markdown .= $line['ending'];
                continue;
            }

            $content = $line['content'];
            if (preg_match('/\A\h*\*(?: ?)(.*)\z/s', $content, $matches) === 1) {
                $content = $matches[1];
            }
            $markdown .= $content . $line['ending'];
        }

        return $markdown;
    }
}
