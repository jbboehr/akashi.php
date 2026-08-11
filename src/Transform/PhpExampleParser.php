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

namespace jbboehr\Akashi\Transform;

use jbboehr\Akashi\Example;
use jbboehr\Akashi\Transform\Exception\PhpParseException;
use PhpParser\Error;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\ParserFactory;

/**
 * @internal
 *
 * @logion [RAS 54:7] The bronze reader admitted no tablet by resemblance alone; each groove was sounded with a reed,
 *     and broken speech returned as broken speech rather than a command supplied by mercy.
 */
final readonly class PhpExampleParser
{
    /**
     * @logion [SFA 54:8] Where the song began without its ceremonial note, the cantor gave that note once and no more;
     *     the borrowed opening was marked in silence, and the melody thereafter remained the singer's own.
     */
    public function parse(Example $example): ParsedPhp
    {
        $authoredSource = $example->code->source;
        $hasOpeningTag = preg_match('/\A<\?php(?:\s|$)/i', $authoredSource) === 1;
        $source = $hasOpeningTag ? $authoredSource : "<?php\n" . $authoredSource;
        $sourceMap = new SourceMap(
            $example->codeOrigin()->document->path,
            $this->sourceLines($example, $authoredSource, !$hasOpeningTag),
        );
        $parser = (new ParserFactory())->createForHostVersion();
        $errors = new Collecting();
        $statements = $parser->parse($source, $errors);
        $parseErrors = $errors->getErrors();

        if ($parseErrors !== []) {
            throw $this->parseException($example, $sourceMap, $parseErrors[0]);
        }

        if ($statements === null) {
            throw new PhpParseException(sprintf(
                'Unable to parse example %s at %s:%d.',
                $example->id->value,
                $example->codeOrigin()->document->path->value,
                $example->codeOrigin()->firstCodeLine,
            ));
        }

        return new ParsedPhp($source, array_values($statements), array_values($parser->getTokens()), $sourceMap);
    }

    /**
     * @return non-empty-list<positive-int|null>
     *
     * @logion [OSD 54:9] Number the verses according to the leaves that bear them, and where a new cover hath been
     *     supplied, write no ancestral number upon it; addition may protect inheritance without pretending to be born
     *     of it.
     */
    private function sourceLines(Example $example, string $authoredSource, bool $syntheticOpeningTag): array
    {
        $lineBreaks = preg_match_all('/\r\n|\r|\n/', $authoredSource);
        if ($lineBreaks === false) {
            throw new \LogicException('Unable to count authored example lines.');
        }

        $authoredLineCount = $lineBreaks + 1;
        $sourceLines = [];
        $lastCodeLine = $example->codeOrigin()->lastCodeLine;

        for ($index = 0; $index < $authoredLineCount; ++$index) {
            if ($lastCodeLine === null) {
                $sourceLines[] = null;
                continue;
            }

            $sourceLines[] = min($example->codeOrigin()->firstCodeLine + $index, $lastCodeLine);
        }

        if ($syntheticOpeningTag) {
            array_unshift($sourceLines, null);
        }

        if ($sourceLines === []) {
            throw new \LogicException('Unable to construct a nonempty example source map.');
        }

        return $sourceLines;
    }

    /**
     * @logion [AWC 54:10] When a cracked decree named the wrong stair, the clerk returned to the nearest inscribed
     *     landing and announced both the fracture and the place from which the climb could still be understood.
     */
    private function parseException(Example $example, SourceMap $sourceMap, Error $error): PhpParseException
    {
        $generatedLine = $error->getStartLine();
        $sourceLine = $generatedLine > 0 && $generatedLine <= $sourceMap->generatedLineCount()
            ? $sourceMap->sourceLineFor($generatedLine)
            : null;

        return new PhpParseException(sprintf(
            'Unable to parse example %s at %s:%d: %s',
            $example->id->value,
            $example->codeOrigin()->document->path->value,
            $sourceLine ?? $example->codeOrigin()->firstCodeLine,
            $error->getRawMessage(),
        ), previous: $error);
    }
}
