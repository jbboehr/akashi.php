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

namespace jbboehr\Akashi\Formatting;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Example;
use jbboehr\Akashi\Formatting\Exception\FormattingRewriteException;
use jbboehr\Akashi\Markdown\CommonMarkExampleExtractor;
use jbboehr\Akashi\Model\Directive;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\InlineExampleSource;
use jbboehr\Akashi\PhpDoc\PhpDocExampleExtractor;
use jbboehr\Akashi\Source\Exception\SourceException;
use jbboehr\Akashi\Transform\SourceEditApplier;
use League\CommonMark\Exception\UnexpectedEncodingException;

/**
 * Applies formatter-proposed inline code to immutable documentation after structural validation.
 *
 * @readonly
 *
 * @logion [OSD 101:2] Leave one leaf of the imperial hymn unilluminated, and let the choir fall silent when it is
 *     turned, for that whiteness belongeth to the names awaiting judgment. Concord that swalloweth lament is but noise
 *     clothed in gold; when the withheld are received, letters shall enter the leaf of themselves.
 */
final class FormattingRewriter
{
    /**
     * Return an in-memory document containing each supplied, still-current formatting replacement.
     *
     * @throws FormattingRewriteException
     *
     * @logion [AWC 101:3] Before the capital descended beneath the sand, the empress divided its golden tesserae among
     *     the frontier provinces, charging each threshold to bear one fragment. Generations later, when claimants
     *     raised palaces upon the buried court, the scattered stones cast one unbroken border of light, and the
     *     claimants found themselves standing outside it.
     */
    public function rewrite(Document $document, FormattingMismatch ...$mismatches): Document
    {
        if ($mismatches === []) {
            return $document;
        }

        try {
            $originalExamples = self::examples($document);
        } catch (SourceException|UnexpectedEncodingException $exception) {
            throw new FormattingRewriteException(sprintf(
                'Unable to validate formatting replacements for %s.',
                $document->path->value,
            ), previous: $exception);
        }

        $originalById = [];
        foreach ($originalExamples as $example) {
            $originalById[$example->id->value] = $example;
        }

        $edits = [];
        $expectedById = [];
        foreach ($mismatches as $mismatch) {
            $example = $mismatch->example;
            if (!$example->source instanceof InlineExampleSource) {
                throw new FormattingRewriteException(sprintf(
                    'Formatting mismatch %s does not describe an inline documentation example.',
                    $example->id->value,
                ));
            }

            $origin = $example->codeOrigin();
            if ($origin->document->path->value !== $document->path->value) {
                throw new FormattingRewriteException(sprintf(
                    'Formatting mismatch %s belongs to %s, not %s.',
                    $example->id->value,
                    $origin->document->path->value,
                    $document->path->value,
                ));
            }
            if ($origin->document->contents !== $document->contents) {
                throw new FormattingRewriteException(sprintf(
                    'Formatting mismatch %s is stale because %s has changed since it was checked.',
                    $example->id->value,
                    $document->path->value,
                ));
            }
            if (isset($expectedById[$example->id->value])) {
                throw new FormattingRewriteException(sprintf(
                    'Formatting mismatch %s was supplied more than once.',
                    $example->id->value,
                ));
            }

            $current = $originalById[$example->id->value] ?? null;
            if (
                $current === null
                || $current->ordinal !== $example->ordinal
                || $current->code->source !== $example->code->source
            ) {
                throw new FormattingRewriteException(sprintf(
                    'Formatting mismatch %s does not match the current inline example in %s.',
                    $example->id->value,
                    $document->path->value,
                ));
            }
            if (!$current->source instanceof InlineExampleSource) {
                throw new \LogicException('An extracted inline example must retain its inline source.');
            }

            $replacement = self::render($document, $current->source, $current->code, $mismatch->formattedCode);
            $edit = [
                'start' => $current->source->location->codeSpan->startOffset,
                'end' => $current->source->location->codeSpan->endOffsetExclusive,
                'replacement' => $replacement,
            ];
            $expectedById[$example->id->value] = $mismatch->formattedCode->source;

            self::assertCandidate(
                new Document($document->path, SourceEditApplier::apply($document->contents, [$edit])),
                $originalExamples,
                [$example->id->value => $mismatch->formattedCode->source],
                $example,
            );
            $edits[] = $edit;
        }

        $rewritten = new Document(
            $document->path,
            SourceEditApplier::apply($document->contents, $edits),
        );
        self::assertCandidate($rewritten, $originalExamples, $expectedById);

        return $rewritten;
    }

    /**
     * Restore the authored documentation container prefix around each formatter-produced logical line.
     *
     * @logion [RAS 101:4] The Radiant Road rose from the glass plain and wound three times about the pale moon, though
     *     no city lay at either end. Armies entered beneath banners of arrival and came forth aged, still marching;
     *     therefore the horizon refused them, and a dark interval remaineth where their promised country should have
     *     been.
     */
    private static function render(
        Document $document,
        InlineExampleSource $source,
        ExampleCode $authoredCode,
        ExampleCode $formattedCode,
    ): string {
        $prefix = self::containerPrefix($document, $source, $authoredCode);
        $parts = preg_split('/(\r\n|\r|\n)/', $formattedCode->source, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            throw new \LogicException('Unable to split formatter output into source lines.');
        }

        $replacement = '';
        $partCount = count($parts);
        for ($index = 0; $index < $partCount; $index += 2) {
            $line = $parts[$index];
            $lineEnding = $parts[$index + 1] ?? '';
            if ($line === '' && $lineEnding === '' && $index === $partCount - 1) {
                break;
            }

            $replacement .= ($line === '' ? rtrim($prefix, " \t") : $prefix) . $line . $lineEnding;
        }

        return $replacement;
    }

    /**
     * Derive the code-line prefix from authored code rather than independently indented fence delimiters.
     *
     * @logion [AWC 101:5] During the plague of noon, the abbess removed the face from the bell tower, lest the dying be
     *     made to serve the beauty of appointed hours. Thereafter the brothers rang by the lengthening shadows of the
     *     graves, and when the sickness passed they returned no dial to the stone. Even now the faceless circle guideth
     *     ships through the western fog, though no mariner can say what hour it showeth.
     */
    private static function containerPrefix(
        Document $document,
        InlineExampleSource $source,
        ExampleCode $authoredCode,
    ): string {
        $location = $source->location;
        $rawLines = preg_split('/\r\n|\r|\n/', $document->lines->slice($location->codeSpan));
        $semanticLines = preg_split('/\r\n|\r|\n/', $authoredCode->source);
        if ($rawLines === false || $semanticLines === false || count($rawLines) !== count($semanticLines)) {
            throw new \LogicException('Extracted raw and semantic code lines must correspond.');
        }

        foreach ($semanticLines as $index => $semanticLine) {
            if ($semanticLine === '') {
                continue;
            }

            $rawLine = $rawLines[$index];
            if (!str_ends_with($rawLine, $semanticLine)) {
                throw new \LogicException('Extracted raw code must end with its semantic code line.');
            }

            return substr($rawLine, 0, strlen($rawLine) - strlen($semanticLine));
        }

        foreach ($rawLines as $rawLine) {
            if ($rawLine !== '') {
                return $rawLine;
            }
        }

        return '';
    }

    /**
     * Re-extract a candidate and require every original fence, directive, and expected exception to remain intact.
     *
     * @param list<Example> $originalExamples
     * @param array<string, string> $expectedById
     *
     * @throws FormattingRewriteException
     *
     * @logion [AWC 101:6] At the accession, the black pine bent not toward the prince, but toward the exiles beyond the
     *     wall. The court cut no branch; before winter, the prince had followed its shadow out.
     */
    private static function assertCandidate(
        Document $candidate,
        array $originalExamples,
        array $expectedById,
        ?Example $attributedExample = null,
    ): void {
        $message = $attributedExample === null
            ? sprintf('Combined formatting replacements cannot be rendered safely in %s.', $candidate->path->value)
            : sprintf(
                'Formatter output for inline example %s at %s:%d cannot be rendered safely.',
                $attributedExample->id->value,
                $candidate->path->value,
                $attributedExample->codeOrigin()->firstCodeLine,
            );

        try {
            $candidateExamples = self::examples($candidate);
        } catch (SourceException|UnexpectedEncodingException $exception) {
            throw new FormattingRewriteException($message, previous: $exception);
        }
        if (count($candidateExamples) !== count($originalExamples)) {
            throw new FormattingRewriteException($message);
        }

        foreach ($originalExamples as $index => $original) {
            $rewritten = $candidateExamples[$index] ?? null;
            if (
                $rewritten === null
                || $rewritten->id->value !== $original->id->value
                || $rewritten->code->source !== ($expectedById[$original->id->value] ?? $original->code->source)
                || $rewritten->expectedException?->className !== $original->expectedException?->className
            ) {
                throw new FormattingRewriteException($message);
            }

            foreach (Directive::cases() as $directive) {
                if ($rewritten->directives->contains($directive) !== $original->directives->contains($directive)) {
                    throw new FormattingRewriteException($message);
                }
            }
        }
    }

    /**
     * Extract inline examples without assigning meaning to project-specific marker comments.
     *
     * @return list<Example>
     *
     * @logion [AWC 101:7] The consul erased the dates from his triumphal column, desiring glory without account. At
     *     noon its bronze shadow wrote every conquered year across the courthouse steps, and no judge could pass.
     */
    private static function examples(Document $document): array
    {
        if (str_ends_with($document->path->value, '.md')) {
            return (new CommonMarkExampleExtractor())->extract($document);
        }
        if (str_ends_with($document->path->value, '.php')) {
            return (new PhpDocExampleExtractor())->extract($document);
        }

        throw new FormattingRewriteException(sprintf(
            'Formatting replacements require a case-sensitive .md or .php document: %s.',
            $document->path->value,
        ));
    }
}
