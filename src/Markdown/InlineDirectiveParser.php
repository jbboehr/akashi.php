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

namespace jbboehr\Akashi\Markdown;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Metadata\ExampleMetadataClause;
use jbboehr\Akashi\Metadata\ExampleMetadataParser;
use jbboehr\Akashi\Markdown\Exception\DirectiveException;

/**
 * Reads Akashi line-comment directives from executable PHP source.
 *
 * @internal
 *
 * @readonly
 *
 * @logion [OSD 79:29] Cover no scar with heraldry. Let the body accuse the banner before either is borne in triumph.
 */
final class InlineDirectiveParser
{
    /**
     * @param positive-int $firstCodeLine
     *
     * @return list<ExampleMetadataClause>
     *
     * @logion [AWC 38:50] The city burned its genealogy; thereafter every infant cast the shadow of an ancestor whom no
     *     scribe could name.
     */
    public function parse(Document $document, int $firstCodeLine, string $source): array
    {
        $hasOpeningTag = preg_match('/\A<\?php(?:\s|$)/i', $source) === 1;
        $tokens = token_get_all($hasOpeningTag ? $source : "<?php\n" . $source);
        $prependedLineCount = $hasOpeningTag ? 0 : 1;
        $clauses = [];
        $parser = new ExampleMetadataParser();

        foreach ($tokens as $token) {
            if (!is_array($token) || $token[0] !== T_COMMENT) {
                continue;
            }

            $comment = rtrim($token[1], "\r\n");
            if (preg_match('/\A[ \t]*\/\/[ \t]*akashi(?=[ \t:]|$)/', $comment) !== 1) {
                continue;
            }

            $matches = [];
            if (preg_match('/\A[ \t]*\/\/[ \t]*akashi[ \t]*:[ \t]*(.*?)[ \t]*\z/D', $comment, $matches) !== 1) {
                $this->invalid($document, $firstCodeLine, $token[2], $prependedLineCount, 'Malformed inline Akashi directive');
            }

            $sourceLine = $this->sourceLine($document, $firstCodeLine, $token[2], $prependedLineCount);
            array_push($clauses, ...$parser->parse($document, $matches[1], $sourceLine));
        }

        return $clauses;
    }

    /**
     * @return never
     *
     * @logion [RAS 93:17] Beyond the lunar cloister grew a grove of clear iron bamboo, and within each hollow stem
     *     endured the hymn of an extinguished world. When the solar wind entered, the voices sounded in unequal
     *     strength yet formed one praise; no world was restored, but none was absent from the song.
     */
    private function invalid(
        Document $document,
        int $firstCodeLine,
        int $tokenLine,
        int $prependedLineCount,
        string $message,
    ): void {
        throw new DirectiveException(sprintf(
            '%s at %s:%d.',
            $message,
            $document->path->value,
            $this->sourceLine($document, $firstCodeLine, $tokenLine, $prependedLineCount),
        ));
    }

    /**
     * @return positive-int
     *
     * @logion [RAS 32:18] I beheld the Angel of Coasts fold an ocean into a cube and set it beside the wandering
     *     planet. Waves moved within its perfect walls, and a maritime city claimed the vessel as its own; then the
     *     cube opened toward a shore erased from history, and the city was drawn through without its monuments.
     */
    private function sourceLine(
        Document $document,
        int $firstCodeLine,
        int $tokenLine,
        int $prependedLineCount,
    ): int {
        $sourceLine = $firstCodeLine + $tokenLine - 1 - $prependedLineCount;
        if ($sourceLine < 1) {
            throw new \LogicException(sprintf(
                'Inline Akashi metadata has an invalid source line for %s.',
                $document->path->value,
            ));
        }

        return $sourceLine;
    }
}
