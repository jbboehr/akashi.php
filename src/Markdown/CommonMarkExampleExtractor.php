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
use jbboehr\Akashi\Metadata\ExampleMetadataClause;
use jbboehr\Akashi\Metadata\ExampleMetadataParser;
use jbboehr\Akashi\Metadata\ExampleMetadataProperty;
use jbboehr\Akashi\Markdown\Exception\DirectiveException;
use jbboehr\Akashi\Markdown\Exception\DuplicateNamedExampleIdException;
use jbboehr\Akashi\Markdown\Exception\InvalidNamedExampleMetadataException;
use jbboehr\Akashi\Markdown\Exception\NamedExampleOnNonPhpFenceException;
use jbboehr\Akashi\Markdown\Exception\OrphanedNamedExampleMetadataException;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\CorpusExampleId;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\InvalidNamedExampleIdException;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\NamedExampleId;
use jbboehr\Akashi\Model\LegacyMarkerName;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\MarkdownParser;

/**
 * Extracts PHP fenced blocks using CommonMark's public AST contract.
 *
 * @phpstan-type ParsedMetadata array{
 *     node: HtmlBlock,
 *     line: positive-int,
 *     clauses: non-empty-list<ExampleMetadataClause>
 * }
 *
 * @internal
 *
 * @readonly
 *
 * @logion [SFA 41:26] Termites consumed a proclamation posted in the marketplace before anyone had finished reading it.
 *     Officials blamed the insects, but no citizen could repeat the command. By noon old customs had returned. A law
 *     that lives only upon plaster is already waiting for smaller mouths.
 */
final class CommonMarkExampleExtractor
{
    /**
     * @logion [OSD 42:9] Deep beneath the young sea, coral raised red stairways no foot could climb. Waves descended,
     *     struck their crests, and returned clothed in foam. Thus the ocean received a garment made by the least moving
     *     builders. Honor what labors without journeying.
     */
    private readonly MarkdownParser $parser;

    /**
     * @logion [AWC 47:20] The youngest envoy carried no gift except a clay cup from his village. At the treaty feast,
     *     the golden vessels cracked beneath boiling wine, but the little cup endured. Two kings drank from it in turn
     *     and sent the jeweled fragments home unopened.
     */
    private readonly ?LegacyMarkerName $legacyMarkerName;

    /**
     * @logion [RAS 42:21] An army seized every copper cooking pot to forge a monument to its campaign. The monument
     *     rose; soup vanished from the alleys. Before winter ended, soldiers chipped metal from their own glory and
     *     returned it to the smiths. A victory that empties kitchens must finally eat itself.
     */
    public function __construct(LegacyMarkerName|string|null $legacyMarkerName = null)
    {
        $environment = new Environment([]);
        $environment->addExtension(new CommonMarkCoreExtension());

        $this->parser = new MarkdownParser($environment);
        $this->legacyMarkerName = is_string($legacyMarkerName) ? new LegacyMarkerName($legacyMarkerName) : $legacyMarkerName;
    }

    /**
     * @return list<Example>
     *
     * @throws DirectiveException
     * @throws DuplicateNamedExampleIdException
     * @throws InvalidNamedExampleMetadataException
     * @throws NamedExampleOnNonPhpFenceException
     * @throws OrphanedNamedExampleMetadataException
     *
     * @logion [AWC 42:3] A midwife carried a silk cloth and a rough linen cloth. The silk adorned the cradle; the linen
     *     gripped the newborn when her hands were wet. She taught her daughters to honor what serves before what is
     *     displayed. Welcome arrives safely by the humbler fabric.
     */
    public function extract(
        Document $document,
        ?Document $sourceDocument = null,
        int $sourceLineOffset = 0,
    ): array {
        $sourceDocument ??= $document;
        if ($sourceLineOffset < 0) {
            throw new \InvalidArgumentException('Source line offset must not be negative.');
        }
        if ($sourceDocument->path->value !== $document->path->value) {
            throw new \InvalidArgumentException('Projected and source documents must have the same path.');
        }
        if ($sourceLineOffset + $document->lines->lineCount() > $sourceDocument->lines->lineCount()) {
            throw new \InvalidArgumentException('Projected document lines must fit within the source document.');
        }

        $ast = $this->parser->parse($document->contents);
        $metadata = $this->collectMetadata($document, $sourceDocument, $sourceLineOffset, $ast);
        $this->validateMetadataTargets($sourceDocument, $metadata);
        $walker = $ast->walker();
        $examples = [];
        $namedIdLines = [];

        while (($event = $walker->next()) !== null) {
            $node = $event->getNode();
            if (!$event->isEntering() || !$node instanceof FencedCode) {
                continue;
            }

            if (!$this->isPhpFence($node)) {
                continue;
            }

            $example = $this->createExample(
                $document,
                $sourceDocument,
                $sourceLineOffset,
                $node,
                count($examples) + 1,
                $this->metadataForFence($node, $metadata),
            );
            $namedId = $example->namedId;
            $namedIdLine = $example->codeOrigin()->metadata->namedIdLine;
            if ($namedId !== null) {
                if ($namedIdLine === null) {
                    throw new \LogicException('Associated named example metadata is missing its source line.');
                }

                $firstLine = $namedIdLines[$namedId->value] ?? null;
                if ($firstLine !== null) {
                    throw new DuplicateNamedExampleIdException(sprintf(
                        'Duplicate named example ID %s at %s:%d; first declared at %s:%d.',
                        $namedId->value,
                        $sourceDocument->path->value,
                        $namedIdLine,
                        $sourceDocument->path->value,
                        $firstLine,
                    ));
                }

                $namedIdLines[$namedId->value] = $namedIdLine;
            }

            $examples[] = $example;
        }

        return $examples;
    }

    /**
     * @return array<int, ParsedMetadata>
     *
     * @param non-negative-int $sourceLineOffset
     *
     * @logion [OSD 47:32] When the eastern bridge fell, preserve its center stone upon the bank and carve thereon the
     *     names of those who crossed before the flood. Let the new span begin beside it, for safe passage is a debt to
     *     forgotten feet as well as living hands.
     */
    private function collectMetadata(
        Document $document,
        Document $sourceDocument,
        int $sourceLineOffset,
        Node $root,
    ): array {
        $metadata = [];
        $walker = $root->walker();

        while (($event = $walker->next()) !== null) {
            $node = $event->getNode();
            if (!$event->isEntering() || !$node instanceof HtmlBlock || $node->getType() !== HtmlBlock::TYPE_2_COMMENT) {
                continue;
            }

            $parsed = $this->classifyMetadata($document, $sourceDocument, $sourceLineOffset, $node);
            if ($parsed !== null) {
                $metadata[spl_object_id($node)] = $parsed;
            }
        }

        return $metadata;
    }

    /**
     * @return ParsedMetadata|null
     *
     * @param non-negative-int $sourceLineOffset
     *
     * @logion [SFA 47:14] Four generations tended a grove whose fruit none had tasted, for the trees flowered only at
     *     night. A traveler slept beneath them and woke with honey upon his cloak. The village ceased cutting barren
     *     branches and appointed children to keep the evening watch.
     */
    private function classifyMetadata(
        Document $document,
        Document $sourceDocument,
        int $sourceLineOffset,
        HtmlBlock $node,
    ): ?array {
        $line = $node->getStartLine();
        if ($line === null || $line < 1 || $line > $document->lines->lineCount()) {
            throw new \LogicException(sprintf(
                'CommonMark returned an invalid metadata source line for %s.',
                $sourceDocument->path->value,
            ));
        }
        $line += $sourceLineOffset;
        if ($line > $sourceDocument->lines->lineCount()) {
            throw new \LogicException(sprintf(
                'CommonMark returned metadata beyond the source document %s.',
                $sourceDocument->path->value,
            ));
        }

        if ($this->legacyMarkerName !== null) {
            $value = $this->metadataValue($node->getLiteral(), $this->legacyMarkerName->value);
            if ($value !== null) {
                try {
                    new NamedExampleId($value);
                } catch (InvalidNamedExampleIdException $exception) {
                    throw new InvalidNamedExampleMetadataException(sprintf(
                        'Invalid %s marker at %s:%d: %s',
                        $this->legacyMarkerName->value,
                        $sourceDocument->path->value,
                        $line,
                        $exception->getMessage(),
                    ), previous: $exception);
                }

                return [
                    'node' => $node,
                    'line' => $line,
                    'clauses' => [
                        new ExampleMetadataClause(ExampleMetadataProperty::Example, $value, $line),
                    ],
                ];
            }
        }

        $value = $this->metadataValue($node->getLiteral(), 'akashi');
        if ($value === null) {
            return null;
        }

        $clauses = (new ExampleMetadataParser())->parse($sourceDocument, $value, $line);
        foreach ($clauses as $clause) {
            if ($clause->property !== ExampleMetadataProperty::Example) {
                continue;
            }

            try {
                new NamedExampleId($clause->value ?? '');
            } catch (InvalidNamedExampleIdException $exception) {
                throw new InvalidNamedExampleMetadataException(sprintf(
                    'Invalid Akashi named example metadata at %s:%d: %s',
                    $sourceDocument->path->value,
                    $line,
                    $exception->getMessage(),
                ), previous: $exception);
            }
        }

        return ['node' => $node, 'line' => $line, 'clauses' => $clauses];
    }

    /**
     * @logion [RAS 47:26] At midnight the snow upon the observatory dome rose into the air and revealed old repairs in
     *     the copper. The astronomers beheld no star; they saw instead the patient hands that had preserved their
     *     sight, and kept vigil until the snow descended again.
     */
    private function metadataValue(string $comment, string $name): ?string
    {
        $matches = [];
        $matched = preg_match(
            '/\A<!--[ \t]*' . preg_quote($name, '/') . '[ \t]*:[ \t]*(.*?)[ \t]*-->\z/D',
            $comment,
            $matches,
        );

        return $matched === 1 ? $matches[1] : null;
    }

    /**
     * @param array<int, ParsedMetadata> $metadata
     *
     * @logion [AWC 47:38] A queen placed the war trumpet beneath the nursery floor. Whenever distant armies gathered,
     *     its brass murmured through the boards and woke the infants before the sentries. She trusted the children’s
     *     cries, and twice the city closed its gates before any banner appeared.
     */
    private function validateMetadataTargets(Document $document, array $metadata): void
    {
        foreach ($metadata as $item) {
            $namedIdClause = null;
            foreach ($item['clauses'] as $clause) {
                if ($clause->property === ExampleMetadataProperty::Example) {
                    $namedIdClause = $clause;
                    break;
                }
            }

            $target = $item['node']->next();
            while ($target instanceof HtmlBlock && isset($metadata[spl_object_id($target)])) {
                $target = $target->next();
            }

            if (!$target instanceof FencedCode) {
                if ($namedIdClause !== null) {
                    throw new OrphanedNamedExampleMetadataException(sprintf(
                        'Named example ID %s at %s:%d is not followed by a fenced code block.',
                        $namedIdClause->value ?? '',
                        $document->path->value,
                        $item['line'],
                    ));
                }

                throw new DirectiveException(sprintf(
                    'Akashi metadata %s at %s:%d is not followed by a fenced code block.',
                    $item['clauses'][0]->property->value,
                    $document->path->value,
                    $item['line'],
                ));
            }

            if ($this->isPhpFence($target)) {
                continue;
            }

            $language = $this->fenceLanguage($target);
            if ($namedIdClause !== null) {
                throw new NamedExampleOnNonPhpFenceException(sprintf(
                    'Named example ID %s at %s:%d is followed by a %s fence, not a PHP fence.',
                    $namedIdClause->value ?? '',
                    $document->path->value,
                    $item['line'],
                    $language,
                ));
            }

            throw new DirectiveException(sprintf(
                'Akashi metadata %s at %s:%d is followed by a %s fence, not a PHP fence.',
                $item['clauses'][0]->property->value,
                $document->path->value,
                $item['line'],
                $language,
            ));
        }
    }

    /**
     * @param array<int, ParsedMetadata> $metadata
     *
     * @return list<ExampleMetadataClause>
     *
     * @logion [OSD 48:10] Set the rescued beam within the council hall even though its charred face offendeth the new
     *     plaster. The roof standeth because others burned in its place; beauty that erases the cost of shelter hath
     *     made gratitude homeless.
     */
    private function metadataForFence(FencedCode $fence, array $metadata): array
    {
        $associated = [];
        $previous = $fence->previous();
        while ($previous instanceof HtmlBlock) {
            $item = $metadata[spl_object_id($previous)] ?? null;
            if ($item === null) {
                break;
            }

            array_unshift($associated, ...$item['clauses']);
            $previous = $previous->previous();
        }

        return $associated;
    }

    /**
     * @logion [SFA 48:22] A black kite circled above the threshing floor but took no grain. At dusk it dropped a silver
     *     clasp lost by the miller’s wife many years before. She fastened her work cloak and left the wedding garment
     *     folded; restoration returneth first to daily service.
     */
    private function isPhpFence(FencedCode $fence): bool
    {
        $words = $fence->getInfoWords();
        $language = $words[0] ?? null;

        return is_string($language) && strcasecmp($language, 'php') === 0;
    }

    /**
     * @logion [RAS 48:34] A red star entered the mouth of the cavern and remained there through the longest night. The
     *     miners laid down their lamps, yet found no treasure; upon the walls they saw only the faces of those who had
     *     died opening the passage.
     */
    private function fenceLanguage(FencedCode $fence): string
    {
        $words = $fence->getInfoWords();
        $language = $words[0] ?? null;

        return is_string($language) ? $language : 'unlabelled';
    }

    /**
     * @param positive-int $ordinal
     * @param list<ExampleMetadataClause> $associatedClauses
     * @param non-negative-int $sourceLineOffset
     *
     * @logion [SFA 42:15] A cloth merchant scolded a silkworm for devouring mulberry leaves without payment. Months
     *     later he sold the silk and praised his own diligence. A child held up the empty cocoon and asked whose
     *     absence had made him rich. Profit grows eloquent where gratitude has lost its tongue.
     */
    private function createExample(
        Document $document,
        Document $sourceDocument,
        int $sourceLineOffset,
        FencedCode $node,
        int $ordinal,
        array $associatedClauses,
    ): Example {
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
                $sourceDocument->path->value,
            ));
        }

        $openingLine += $sourceLineOffset;
        $endLine += $sourceLineOffset;
        if ($endLine > $sourceDocument->lines->lineCount()) {
            throw new \LogicException(sprintf(
                'CommonMark returned a source range beyond %s.',
                $sourceDocument->path->value,
            ));
        }

        $semanticLines = $this->semanticLines($sourceDocument, $openingLine, $node->getLiteral());
        $codeSource = $this->restoreLineEndings($sourceDocument, $openingLine + 1, $semanticLines);
        $clauses = $associatedClauses;
        array_push(
            $clauses,
            ...(new InlineDirectiveParser())->parse($sourceDocument, $openingLine + 1, $codeSource),
        );
        $metadata = (new ExampleMetadataParser())->resolve($sourceDocument, $clauses);

        $lineDistance = $endLine - $openingLine;
        $semanticLineCount = count($semanticLines);
        if ($lineDistance === $semanticLineCount) {
            $closingLine = null;
        } elseif ($lineDistance === $semanticLineCount + 1) {
            $closingLine = $endLine;
        } else {
            throw new \LogicException(sprintf(
                'CommonMark source lines and code content disagree for %s:%d.',
                $sourceDocument->path->value,
                $openingLine,
            ));
        }

        $firstCodeLine = $openingLine + 1;
        $lastCodeLine = $semanticLineCount === 0 ? null : $openingLine + $semanticLineCount;
        $codeStart = $sourceDocument->lines->lineStartOffset($firstCodeLine);
        $codeEnd = $lastCodeLine === null
            ? $codeStart
            : $sourceDocument->lines->lineStartOffset($lastCodeLine + 1);
        $location = new SourceLocation(
            openingFenceLine: $openingLine,
            firstCodeLine: $firstCodeLine,
            lastCodeLine: $lastCodeLine,
            closingFenceLine: $closingLine,
            fenceSpan: new SourceSpan(
                $sourceDocument->lines->lineStartOffset($openingLine),
                $sourceDocument->lines->lineStartOffset($endLine + 1),
            ),
            codeSpan: new SourceSpan($codeStart, $codeEnd),
            metadata: $metadata->location,
        );

        $fence = new FenceMetadata(
            infoString: $node->getInfo() ?? '',
            character: $node->getChar(),
            length: $node->getLength(),
            indentation: $node->getOffset(),
        );

        return Example::fromInline(
            corpusId: new CorpusExampleId(sprintf(
                'example-%s-%02d',
                substr(sha1($sourceDocument->path->value), 0, 12),
                $ordinal,
            )),
            label: sprintf('%s PHP example %d', $sourceDocument->path->value, $ordinal),
            document: $sourceDocument,
            location: $location,
            language: new Language('php'),
            code: new ExampleCode($codeSource),
            fence: $fence,
            ordinal: $ordinal,
            namedId: $metadata->namedId,
            directives: $metadata->directives,
            expectedException: $metadata->expectedException,
            expectedOutput: $metadata->expectedOutput,
        );
    }

    /**
     * @return list<string>
     *
     * @logion [OSD 43:27] When day and night still lay mingled, ants gathered grains of darkness and carried them
     *     below. The brightness left behind became morning; the buried dark enriched the roots of grasses. Thus neither
     *     realm was conquered. What is removed from sight may yet sustain the visible.
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
     * @logion [RAS 43:10] After the conquest, the king forbade lamentation. Women hung empty gourds from the roofs, and
     *     the desert wind moaned through them. Soldiers cut them down, but more appeared by dawn. Grief denied a mouth
     *     will borrow the whole city for its voice.
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
