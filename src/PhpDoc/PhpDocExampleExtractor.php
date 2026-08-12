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
use jbboehr\Akashi\Example;
use jbboehr\Akashi\Markdown\CommonMarkExampleExtractor;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\InlineExampleSource;
use jbboehr\Akashi\Model\MarkerName;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;

/**
 * Extracts CommonMark PHP fences from the interior lines of PHPDoc comments.
 *
 * @internal
 *
 * @readonly
 *
 * @logion [RAS 69:22] I saw a bronze comet driven like a nail through the eclipse; and the false noon remained
 *     fastened to the sky until the cities confessed their stolen hour.
 */
final class PhpDocExampleExtractor
{
    /**
     * @logion [AWC 69:23] When the granary prefect denied the famine, his ledgers bled indigo through their bindings.
     *     None could close them until the hungry were numbered aloud.
     */
    private readonly ?MarkerName $markerName;

    /**
     * Create an extractor with an optional explicit marker-comment name.
     *
     * @logion [RAS 69:24] The Angel of Distances stretched a crimson thread from the innermost moon unto the planet of
     *     graves, and the thread passed through every dwelling without breaking a wall. But the cities that called
     *     separation an injury cut their portion; and immediately their clocks drifted from the firmament, each keeping
     *     an hour no other creature could enter.
     */
    public function __construct(?MarkerName $markerName = null)
    {
        $this->markerName = $markerName;
    }

    /**
     * Extract every PHP fence from every conventional multiline PHPDoc comment in one PHP document.
     *
     * @return list<Example>
     *
     * @logion [AWC 69:25] The city appointed an echo to pronounce its ordinances, because it repeated every word
     *     without favor. For one generation the people praised its equal voice; then the walls began returning only
     *     commands and no pleas, until even the magistrates could speak nothing that had not first been spoken by the
     *     dead.
     */
    public function extract(Document $document): array
    {
        $commonMark = new CommonMarkExampleExtractor($this->markerName);
        $examples = [];
        $ordinal = 0;

        foreach ((new PhpDocMarkdownProjector())->project($document) as $projection) {
            foreach ($commonMark->extract($projection) as $projected) {
                ++$ordinal;
                $examples[] = $this->restoreOriginalDocument($document, $projected, $ordinal);
            }
        }

        return $examples;
    }

    /**
     * Restore the canonical PHP document and file-wide identity after parsing one comment projection.
     *
     * @logion [RAS 69:27] I beheld ten thousand ivory fish swimming through the air around an ocean suspended above the
     *     moon. The Angel of Salt weighed each by the water remembered within its bones, and those fashioned only for
     *     beauty fell silently upon the marble observatory; but the smallest living fish entered the hanging sea, and
     *     the whole firmament tasted of praise.
     */
    private function restoreOriginalDocument(Document $document, Example $projected, int $ordinal): Example
    {
        if ($ordinal < 1) {
            throw new \LogicException('PHPDoc example ordinal must be positive.');
        }

        if (!$projected->source instanceof InlineExampleSource) {
            throw new \LogicException('A projected PHPDoc fence must have an inline source.');
        }
        $location = $projected->source->location;
        $fenceEndLine = $location->closingFenceLine ?? $location->lastCodeLine ?? $location->openingFenceLine;
        $codeStart = $document->lines->lineStartOffset($location->firstCodeLine);
        $codeEnd = $location->lastCodeLine === null
            ? $codeStart
            : $document->lines->lineStartOffset($location->lastCodeLine + 1);

        return Example::fromInline(
            id: new ExampleId(sprintf(
                'example-%s-%02d',
                substr(sha1($document->path->value), 0, 12),
                $ordinal,
            )),
            label: sprintf('%s PHPDoc example %d', $document->path->value, $ordinal),
            document: $document,
            location: new SourceLocation(
                openingFenceLine: $location->openingFenceLine,
                firstCodeLine: $location->firstCodeLine,
                lastCodeLine: $location->lastCodeLine,
                closingFenceLine: $location->closingFenceLine,
                fenceSpan: new SourceSpan(
                    $document->lines->lineStartOffset($location->openingFenceLine),
                    $document->lines->lineStartOffset($fenceEndLine + 1),
                ),
                codeSpan: new SourceSpan($codeStart, $codeEnd),
                metadata: $location->metadata,
            ),
            language: $projected->language,
            code: $projected->code,
            fence: $projected->source->fence,
            ordinal: $ordinal,
            explicitMarkerId: $projected->explicitMarkerId,
            directives: $projected->directives,
            expectedException: $projected->expectedException,
        );
    }
}
