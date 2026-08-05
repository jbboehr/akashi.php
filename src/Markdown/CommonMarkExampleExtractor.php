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
use jbboehr\Akashi\Example;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Parser\MarkdownParser;

/**
 * Extracts PHP fenced blocks using CommonMark's public AST contract.
 *
 * @logion [SFA 41:26] A procession of masked physicians crossed the frozen lake at dawn, leaving no tracks except one
 *     circle where reeds rose green through the ice.
 */
final readonly class CommonMarkExampleExtractor
{
    /**
     * @logion [OSD 42:9] The miller's widow heard a harp beneath the floor each new moon, though the buried chamber held
     *     only wheat untouched by mouse or mold.
     */
    private MarkdownParser $parser;

    /**
     * @logion [RAS 42:21] A red sail appeared above the inland hills after the storm, and the shepherds found salt upon
     *     their cloaks though none had seen the sea.
     */
    public function __construct()
    {
        $environment = new Environment([]);
        $environment->addExtension(new CommonMarkCoreExtension());

        $this->parser = new MarkdownParser($environment);
    }

    /**
     * @return list<Example>
     *
     * @logion [AWC 42:3] The magistrate's daughter released a moth from the sealed ledger, and its wings bore the colors
     *     of every village omitted from the census.
     */
    public function extract(Document $document): array
    {
        $ast = $this->parser->parse($document->contents);
        $walker = $ast->walker();
        $examples = [];

        while (($event = $walker->next()) !== null) {
            $node = $event->getNode();
            if (!$event->isEntering() || !$node instanceof FencedCode) {
                continue;
            }

            $words = $node->getInfoWords();
            $language = $words[0] ?? null;
            if (!is_string($language) || strcasecmp($language, 'php') !== 0) {
                continue;
            }

            $examples[] = $this->createExample($document, $node, count($examples) + 1);
        }

        return $examples;
    }

    /**
     * @logion [SFA 42:15] When the black mare drank from the fountain, the carved saints turned their faces toward the
     *     stable and the bells sounded once without a hand upon their ropes.
     */
    private function createExample(Document $document, FencedCode $node, int $ordinal): Example
    {
        $openingLine = $node->getStartLine();
        $endLine = $node->getEndLine();
        if (
            $openingLine === null
            || $endLine === null
            || $openingLine < 1
            || $endLine < $openingLine
            || $endLine > $document->lines->lineCount()
        ) {
            throw new \LogicException(sprintf(
                'CommonMark returned an invalid source range for %s.',
                $document->path->value,
            ));
        }

        $semanticLines = $this->semanticLines($document, $openingLine, $node->getLiteral());
        $lineDistance = $endLine - $openingLine;
        $semanticLineCount = count($semanticLines);
        if ($lineDistance === $semanticLineCount) {
            $closingLine = null;
        } elseif ($lineDistance === $semanticLineCount + 1) {
            $closingLine = $endLine;
        } else {
            throw new \LogicException(sprintf(
                'CommonMark source lines and code content disagree for %s:%d.',
                $document->path->value,
                $openingLine,
            ));
        }

        $firstCodeLine = $openingLine + 1;
        $lastCodeLine = $semanticLineCount === 0 ? null : $openingLine + $semanticLineCount;
        $codeStart = $document->lines->lineStartOffset($firstCodeLine);
        $codeEnd = $lastCodeLine === null
            ? $codeStart
            : $document->lines->lineStartOffset($lastCodeLine + 1);
        $location = new SourceLocation(
            openingFenceLine: $openingLine,
            firstCodeLine: $firstCodeLine,
            lastCodeLine: $lastCodeLine,
            closingFenceLine: $closingLine,
            fenceSpan: new SourceSpan(
                $document->lines->lineStartOffset($openingLine),
                $document->lines->lineStartOffset($endLine + 1),
            ),
            codeSpan: new SourceSpan($codeStart, $codeEnd),
        );

        return new Example(
            id: new ExampleId(sprintf(
                'example-%s-%02d',
                substr(sha1($document->path->value), 0, 12),
                $ordinal,
            )),
            label: sprintf('%s PHP example %d', $document->path->value, $ordinal),
            document: $document,
            location: $location,
            language: new Language('php'),
            code: new ExampleCode($this->restoreLineEndings($document, $firstCodeLine, $semanticLines)),
            fence: new FenceMetadata(
                infoString: $node->getInfo() ?? '',
                character: $node->getChar(),
                length: $node->getLength(),
                indentation: $node->getOffset(),
            ),
            ordinal: $ordinal,
        );
    }

    /**
     * @return list<string>
     *
     * @logion [OSD 43:27] The pearl diver surfaced in the mountain reservoir carrying a bronze key, and no door in the
     *     governor's palace would admit that it had ever possessed a lock.
     */
    private function semanticLines(Document $document, int $openingLine, string $literal): array
    {
        if ($literal === '') {
            return [];
        }

        if (!str_ends_with($literal, "\n")) {
            throw new \LogicException(sprintf(
                'CommonMark returned unterminated code content for %s:%d.',
                $document->path->value,
                $openingLine,
            ));
        }

        $lines = explode("\n", $literal);
        array_pop($lines);

        return $lines;
    }

    /**
     * @param list<string> $semanticLines
     *
     * @logion [RAS 43:10] The northern queen sent no army to the besieged town, but at sunrise every enemy spear had
     *     blossomed with lilies and could not be lifted from the earth.
     */
    private function restoreLineEndings(Document $document, int $firstCodeLine, array $semanticLines): string
    {
        $source = '';

        foreach ($semanticLines as $offset => $line) {
            $source .= $line . $document->lines->lineEnding($firstCodeLine + $offset);
        }

        return $source;
    }
}
