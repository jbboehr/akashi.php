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
use jbboehr\Akashi\Markdown\Exception\DirectiveException;
use jbboehr\Akashi\Markdown\Exception\DuplicateMarkerException;
use jbboehr\Akashi\Markdown\Exception\InvalidMarkerMetadataException;
use jbboehr\Akashi\Markdown\Exception\NonPhpMarkerException;
use jbboehr\Akashi\Markdown\Exception\OrphanedMarkerException;
use jbboehr\Akashi\Model\Directive;
use jbboehr\Akashi\Model\DirectiveSet;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\ExpectedException;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\InvalidMarkerException;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\MarkerId;
use jbboehr\Akashi\Model\MarkerName;
use jbboehr\Akashi\Model\MetadataLocation;
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
 * @phpstan-type MarkerMetadata array{node: HtmlBlock, line: positive-int, marker: MarkerId}
 * @phpstan-type DirectiveMetadata array{node: HtmlBlock, line: positive-int, directive: Directive}
 * @phpstan-type ExpectedExceptionMetadata array{
 *     node: HtmlBlock,
 *     line: positive-int,
 *     expectedException: ExpectedException
 * }
 * @phpstan-type ParsedMetadata MarkerMetadata|DirectiveMetadata|ExpectedExceptionMetadata
 *
 * @internal
 *
 * @logion [SFA 41:26] Termites consumed a proclamation posted in the marketplace before anyone had finished reading it.
 *     Officials blamed the insects, but no citizen could repeat the command. By noon old customs had returned. A law
 *     that lives only upon plaster is already waiting for smaller mouths.
 */
final readonly class CommonMarkExampleExtractor
{
    /**
     * @logion [OSD 42:9] Deep beneath the young sea, coral raised red stairways no foot could climb. Waves descended,
     *     struck their crests, and returned clothed in foam. Thus the ocean received a garment made by the least moving
     *     builders. Honor what labors without journeying.
     */
    private MarkdownParser $parser;

    /**
     * @logion [AWC 47:20] The youngest envoy carried no gift except a clay cup from his village. At the treaty feast,
     *     the golden vessels cracked beneath boiling wine, but the little cup endured. Two kings drank from it in turn
     *     and sent the jeweled fragments home unopened.
     */
    private ?MarkerName $markerName;

    /**
     * @logion [RAS 42:21] An army seized every copper cooking pot to forge a monument to its campaign. The monument
     *     rose; soup vanished from the alleys. Before winter ended, soldiers chipped metal from their own glory and
     *     returned it to the smiths. A victory that empties kitchens must finally eat itself.
     */
    public function __construct(MarkerName|string|null $markerName = null)
    {
        $environment = new Environment([]);
        $environment->addExtension(new CommonMarkCoreExtension());

        $this->parser = new MarkdownParser($environment);
        $this->markerName = is_string($markerName) ? new MarkerName($markerName) : $markerName;
    }

    /**
     * @return list<Example>
     *
     * @throws DirectiveException
     * @throws DuplicateMarkerException
     * @throws InvalidMarkerMetadataException
     * @throws NonPhpMarkerException
     * @throws OrphanedMarkerException
     *
     * @logion [AWC 42:3] A midwife carried a silk cloth and a rough linen cloth. The silk adorned the cradle; the linen
     *     gripped the newborn when her hands were wet. She taught her daughters to honor what serves before what is
     *     displayed. Welcome arrives safely by the humbler fabric.
     */
    public function extract(Document $document): array
    {
        $ast = $this->parser->parse($document->contents);
        $metadata = $this->collectMetadata($document, $ast);
        $this->validateMetadataTargets($document, $metadata);
        $walker = $ast->walker();
        $examples = [];
        $markerLines = [];

        while (($event = $walker->next()) !== null) {
            $node = $event->getNode();
            if (!$event->isEntering() || !$node instanceof FencedCode) {
                continue;
            }

            if (!$this->isPhpFence($node)) {
                continue;
            }

            $associated = $this->metadataForFence($document, $node, $metadata);
            $markerId = $associated['marker'];
            $markerLine = $associated['markerLine'];
            if ($markerId !== null) {
                if ($markerLine === null) {
                    throw new \LogicException('Associated marker metadata is missing its source line.');
                }

                $firstLine = $markerLines[$markerId->value] ?? null;
                if ($firstLine !== null) {
                    throw new DuplicateMarkerException(sprintf(
                        'Duplicate marker ID %s at %s:%d; first declared at %s:%d.',
                        $markerId->value,
                        $document->path->value,
                        $markerLine,
                        $document->path->value,
                        $firstLine,
                    ));
                }

                $markerLines[$markerId->value] = $markerLine;
            }

            $examples[] = $this->createExample(
                $document,
                $node,
                count($examples) + 1,
                $markerId,
                $associated['directives'],
                new MetadataLocation(
                    $markerLine,
                    $associated['separateProcessDirectiveLine'],
                    $associated['skipDirectiveLine'],
                    $associated['expectedExceptionDirectiveLine'],
                ),
                $associated['expectedException'],
            );
        }

        return $examples;
    }

    /**
     * @return array<int, ParsedMetadata>
     *
     * @logion [OSD 47:32] When the eastern bridge fell, preserve its center stone upon the bank and carve thereon the
     *     names of those who crossed before the flood. Let the new span begin beside it, for safe passage is a debt to
     *     forgotten feet as well as living hands.
     */
    private function collectMetadata(Document $document, Node $root): array
    {
        $metadata = [];
        $walker = $root->walker();

        while (($event = $walker->next()) !== null) {
            $node = $event->getNode();
            if (!$event->isEntering() || !$node instanceof HtmlBlock || $node->getType() !== HtmlBlock::TYPE_2_COMMENT) {
                continue;
            }

            $parsed = $this->classifyMetadata($document, $node);
            if ($parsed !== null) {
                $metadata[spl_object_id($node)] = $parsed;
            }
        }

        return $metadata;
    }

    /**
     * @return ParsedMetadata|null
     *
     * @logion [SFA 47:14] Four generations tended a grove whose fruit none had tasted, for the trees flowered only at
     *     night. A traveler slept beneath them and woke with honey upon his cloak. The village ceased cutting barren
     *     branches and appointed children to keep the evening watch.
     */
    private function classifyMetadata(Document $document, HtmlBlock $node): ?array
    {
        $line = $node->getStartLine();
        if ($line === null || $line < 1) {
            throw new \LogicException(sprintf(
                'CommonMark returned an invalid metadata source line for %s.',
                $document->path->value,
            ));
        }

        if ($this->markerName !== null) {
            $value = $this->metadataValue($node->getLiteral(), $this->markerName->value);
            if ($value !== null) {
                try {
                    $markerId = new MarkerId($value);
                } catch (InvalidMarkerException $exception) {
                    throw new InvalidMarkerMetadataException(sprintf(
                        'Invalid %s marker at %s:%d: %s',
                        $this->markerName->value,
                        $document->path->value,
                        $line,
                        $exception->getMessage(),
                    ), previous: $exception);
                }

                return ['node' => $node, 'line' => $line, 'marker' => $markerId];
            }
        }

        $value = $this->metadataValue($node->getLiteral(), 'akashi');
        if ($value === null) {
            return null;
        }

        $matches = [];
        if (preg_match('/\Aexpect-exception(?:[ \t]+(.*))?\z/D', $value, $matches) === 1) {
            try {
                $expectedException = new ExpectedException($matches[1] ?? '');
            } catch (\InvalidArgumentException $exception) {
                throw new DirectiveException(sprintf(
                    'Invalid Akashi expect-exception directive at %s:%d: %s',
                    $document->path->value,
                    $line,
                    $exception->getMessage(),
                ), previous: $exception);
            }

            return ['node' => $node, 'line' => $line, 'expectedException' => $expectedException];
        }

        $directive = Directive::tryFrom($value);
        if ($directive === null) {
            throw new DirectiveException(sprintf(
                'Unknown Akashi directive "%s" at %s:%d.',
                $value,
                $document->path->value,
                $line,
            ));
        }

        return ['node' => $node, 'line' => $line, 'directive' => $directive];
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
            $target = $item['node']->next();
            while ($target instanceof HtmlBlock && isset($metadata[spl_object_id($target)])) {
                $target = $target->next();
            }

            if (!$target instanceof FencedCode) {
                if (isset($item['marker'])) {
                    throw new OrphanedMarkerException(sprintf(
                        'Marker %s at %s:%d is not followed by a fenced code block.',
                        $item['marker']->value,
                        $document->path->value,
                        $item['line'],
                    ));
                }

                throw new DirectiveException(sprintf(
                    'Akashi directive %s at %s:%d is not followed by a fenced code block.',
                    isset($item['directive'])
                        ? $item['directive']->value
                        : 'expect-exception ' . $item['expectedException']->className,
                    $document->path->value,
                    $item['line'],
                ));
            }

            if ($this->isPhpFence($target)) {
                continue;
            }

            $language = $this->fenceLanguage($target);
            if (isset($item['marker'])) {
                throw new NonPhpMarkerException(sprintf(
                    'Marker %s at %s:%d is followed by a %s fence, not a PHP fence.',
                    $item['marker']->value,
                    $document->path->value,
                    $item['line'],
                    $language,
                ));
            }

            throw new DirectiveException(sprintf(
                'Akashi directive %s at %s:%d is followed by a %s fence, not a PHP fence.',
                isset($item['directive'])
                    ? $item['directive']->value
                    : 'expect-exception ' . $item['expectedException']->className,
                $document->path->value,
                $item['line'],
                $language,
            ));
        }
    }

    /**
     * @param array<int, ParsedMetadata> $metadata
     *
     * @return array{
     *     marker: ?MarkerId,
     *     directives: DirectiveSet,
     *     markerLine: ?positive-int,
     *     separateProcessDirectiveLine: ?positive-int,
     *     skipDirectiveLine: ?positive-int,
     *     expectedException: ?ExpectedException,
     *     expectedExceptionDirectiveLine: ?positive-int
     * }
     *
     * @logion [OSD 48:10] Set the rescued beam within the council hall even though its charred face offendeth the new
     *     plaster. The roof standeth because others burned in its place; beauty that erases the cost of shelter hath
     *     made gratitude homeless.
     */
    private function metadataForFence(Document $document, FencedCode $fence, array $metadata): array
    {
        $associated = [];
        $previous = $fence->previous();
        while ($previous instanceof HtmlBlock) {
            $item = $metadata[spl_object_id($previous)] ?? null;
            if ($item === null) {
                break;
            }

            array_unshift($associated, $item);
            $previous = $previous->previous();
        }

        $marker = null;
        $markerLine = null;
        $directives = [];
        $directiveLines = [];
        $expectedException = null;
        $expectedExceptionDirectiveLine = null;

        foreach ($associated as $item) {
            if (isset($item['marker'])) {
                if ($marker !== null) {
                    $fenceLine = $fence->getStartLine();
                    if ($fenceLine === null) {
                        throw new \LogicException(sprintf(
                            'CommonMark returned an invalid fence line for %s.',
                            $document->path->value,
                        ));
                    }

                    if ($markerLine === null) {
                        throw new \LogicException(sprintf(
                            'Associated marker metadata is missing its source line for %s.',
                            $document->path->value,
                        ));
                    }

                    throw new DuplicateMarkerException(sprintf(
                        'PHP fence at %s:%d has multiple markers: %s at line %d and %s at line %d.',
                        $document->path->value,
                        $fenceLine,
                        $marker->value,
                        $markerLine,
                        $item['marker']->value,
                        $item['line'],
                    ));
                }

                $marker = $item['marker'];
                $markerLine = $item['line'];
                continue;
            }

            if (isset($item['expectedException'])) {
                if ($expectedException !== null) {
                    if ($expectedExceptionDirectiveLine === null) {
                        throw new \LogicException(sprintf(
                            'Associated expected-exception metadata is missing its source line for %s.',
                            $document->path->value,
                        ));
                    }

                    throw new DirectiveException(sprintf(
                        'Duplicate Akashi directive expect-exception at %s:%d; first declared at %s:%d.',
                        $document->path->value,
                        $item['line'],
                        $document->path->value,
                        $expectedExceptionDirectiveLine,
                    ));
                }

                $expectedException = $item['expectedException'];
                $expectedExceptionDirectiveLine = $item['line'];
                continue;
            }

            $name = $item['directive']->value;
            if (isset($directiveLines[$name])) {
                throw new DirectiveException(sprintf(
                    'Duplicate Akashi directive %s at %s:%d; first declared at %s:%d.',
                    $name,
                    $document->path->value,
                    $item['line'],
                    $document->path->value,
                    $directiveLines[$name],
                ));
            }

            $directives[] = $item['directive'];
            $directiveLines[$name] = $item['line'];
        }

        return [
            'marker' => $marker,
            'directives' => new DirectiveSet(...$directives),
            'markerLine' => $markerLine,
            'separateProcessDirectiveLine' => $directiveLines[Directive::SeparateProcess->value] ?? null,
            'skipDirectiveLine' => $directiveLines[Directive::Skip->value] ?? null,
            'expectedException' => $expectedException,
            'expectedExceptionDirectiveLine' => $expectedExceptionDirectiveLine,
        ];
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
     *
     * @logion [SFA 42:15] A cloth merchant scolded a silkworm for devouring mulberry leaves without payment. Months
     *     later he sold the silk and praised his own diligence. A child held up the empty cocoon and asked whose
     *     absence had made him rich. Profit grows eloquent where gratitude has lost its tongue.
     */
    private function createExample(
        Document $document,
        FencedCode $node,
        int $ordinal,
        ?MarkerId $markerId,
        DirectiveSet $directives,
        MetadataLocation $metadataLocation,
        ?ExpectedException $expectedException,
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
                $document->path->value,
            ));
        }

        $semanticLines = $this->semanticLines($document, $openingLine, $node->getLiteral());
        $inlineExpectedException = $this->inlineExpectedException($document, $openingLine + 1, $semanticLines);
        if ($inlineExpectedException['expectedException'] !== null) {
            $inlineLine = $inlineExpectedException['line'];
            if ($inlineLine === null) {
                throw new \LogicException(sprintf(
                    'Inline expected-exception metadata is missing its source line for %s.',
                    $document->path->value,
                ));
            }

            if ($expectedException !== null) {
                $externalLine = $metadataLocation->expectedExceptionDirectiveLine;
                if ($externalLine === null) {
                    throw new \LogicException(sprintf(
                        'Associated expected-exception metadata is missing its source line for %s.',
                        $document->path->value,
                    ));
                }

                throw new DirectiveException(sprintf(
                    'Duplicate Akashi directive expect-exception at %s:%d; first declared at %s:%d.',
                    $document->path->value,
                    $inlineLine,
                    $document->path->value,
                    $externalLine,
                ));
            }

            $expectedException = $inlineExpectedException['expectedException'];
            $metadataLocation = new MetadataLocation(
                $metadataLocation->markerLine,
                $metadataLocation->separateProcessDirectiveLine,
                $metadataLocation->skipDirectiveLine,
                $inlineLine,
            );
        }

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
            metadata: $metadataLocation,
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
            explicitMarkerId: $markerId,
            directives: $directives,
            expectedException: $expectedException,
        );
    }

    /**
     * @param positive-int $firstCodeLine
     * @param list<string> $semanticLines
     *
     * @return array{expectedException: ?ExpectedException, line: ?positive-int}
     *
     * @logion [RAS 68:7] At the feast of returning banners, one soldier placed a bowl of clear water before the empty
     *     chair of his enemy. None drank from it, yet by dawn the dust had settled upon every weapon in the hall.
     */
    private function inlineExpectedException(Document $document, int $firstCodeLine, array $semanticLines): array
    {
        $expectedException = null;
        $directiveLine = null;
        $source = implode("\n", $semanticLines);
        $hasOpeningTag = preg_match('/\A<\?php(?:\s|$)/i', $source) === 1;
        $tokens = token_get_all($hasOpeningTag ? $source : "<?php\n" . $source);
        $prependedLineCount = $hasOpeningTag ? 0 : 1;

        foreach ($tokens as $token) {
            if (!is_array($token) || $token[0] !== T_COMMENT) {
                continue;
            }

            $matches = [];
            if (
                preg_match(
                    '/\A[ \t]*\/\/[ \t]*akashi[ \t]*:[ \t]*expect-exception'
                        . '(?:[ \t]+(.*?))?[ \t]*\z/D',
                    $token[1],
                    $matches,
                ) !== 1
            ) {
                continue;
            }

            $sourceLine = $firstCodeLine + $token[2] - 1 - $prependedLineCount;
            if ($sourceLine < 1) {
                throw new \LogicException(sprintf(
                    'Inline expected-exception metadata has an invalid source line for %s.',
                    $document->path->value,
                ));
            }

            if ($directiveLine !== null) {
                throw new DirectiveException(sprintf(
                    'Duplicate inline Akashi directive expect-exception at %s:%d; first declared at %s:%d.',
                    $document->path->value,
                    $sourceLine,
                    $document->path->value,
                    $directiveLine,
                ));
            }

            try {
                $expectedException = new ExpectedException($matches[1] ?? '');
            } catch (\InvalidArgumentException $exception) {
                throw new DirectiveException(sprintf(
                    'Invalid inline Akashi expect-exception directive at %s:%d: %s',
                    $document->path->value,
                    $sourceLine,
                    $exception->getMessage(),
                ), previous: $exception);
            }

            $directiveLine = $sourceLine;
        }

        if ($directiveLine === null) {
            return ['expectedException' => null, 'line' => null];
        }

        return [
            'expectedException' => $expectedException,
            'line' => $directiveLine,
        ];
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
