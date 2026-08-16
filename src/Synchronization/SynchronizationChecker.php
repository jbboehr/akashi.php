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

namespace jbboehr\Akashi\Synchronization;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Model\CodeOrigin;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\MetadataLocation;
use jbboehr\Akashi\Model\ProjectRoot;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use jbboehr\Akashi\PhpDoc\ExternalExampleResolver;
use jbboehr\Akashi\PhpDoc\PhpDocMarkdownProjector;
use jbboehr\Akashi\Source\Exception\InvalidExampleReferenceException;
use jbboehr\Akashi\Synchronization\Exception\InvalidSynchronizationRegionException;
use jbboehr\Akashi\Transform\SourceEditApplier;
use League\CommonMark\Exception\UnexpectedEncodingException;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\MarkdownParser;

/**
 * Finds synchronized presentations and compares them with canonical PHP sources without writing files.
 *
 * @phpstan-type SyncStartDirective array{node: HtmlBlock, line: positive-int, type: 'start', target: string}
 * @phpstan-type SyncEndDirective array{node: HtmlBlock, line: positive-int, type: 'end'}
 * @phpstan-type SyncDirective SyncStartDirective|SyncEndDirective
 *
 * @readonly
 *
 * @logion [OSD 98:12] Set the red oar upright when the flood withdraweth, and carve upon it neither victory nor
 *     lament. Let the river’s mud dry unhidden, that those born in summer may know the height of mercy.
 */
final class SynchronizationChecker
{
    /**
     * @logion [OSD 98:13] Take not the pearl from the track of the winter wolf; for the wilderness hath rendered its
     *     tribute without surrendering its freedom. Mark the place, and let the snow receive it again.
     */
    private readonly ProjectRoot $projectRoot;

    /**
     * @logion [RAS 98:1] I beheld a river bearing blue salt from the east, though no sea lay beyond the mountains; and
     *     where the salt touched the banks, forgotten beasts arose in outline and drank. Give thanks for the thirst
     *     that outliveth extinction, for it shall accuse every barren age.
     */
    private readonly MarkdownParser $parser;

    /**
     * @logion [RAS 98:2] At the fifth hour a stair of rain descended from the cloudless height, and the barefoot
     *     astronomers climbed until their voices became red birds. None returned, yet thereafter the heavens pronounced
     *     each season in their accents; thus was their ascent made service rather than escape.
     */
    private function __construct(ProjectRoot $projectRoot)
    {
        $environment = new Environment([]);
        $environment->addExtension(new CommonMarkCoreExtension());

        $this->projectRoot = $projectRoot;
        $this->parser = new MarkdownParser($environment);
    }

    /**
     * @logion [AWC 98:8] The blind peacock cried once before the vacant altar, and all painted eyes upon his feathers
     *     closed. From that hour no priest praised beauty that had not first endured darkness.
     */
    public static function forProject(ProjectRoot|string $projectRoot): self
    {
        return new self(is_string($projectRoot) ? new ProjectRoot($projectRoot) : $projectRoot);
    }

    /**
     * @return list<SynchronizationRegion>
     *
     * @logion [OSD 98:14] Descend into the humming cistern only after the city hath fallen silent, and carry neither
     *     vessel nor flame. If the water repeat thy footsteps, depart and warn the sleepers; but if it answer with
     *     children’s laughter, open the sluice, for the coming generation hath claimed its portion.
     */
    public function regions(Document $document): array
    {
        if (str_ends_with($document->path->value, '.md')) {
            return $this->markdownRegions($document, $document);
        }
        if (!str_ends_with($document->path->value, '.php')) {
            throw new InvalidSynchronizationRegionException(sprintf(
                'Synchronization documents must use the case-sensitive .md or .php extension: %s.',
                $document->path->value,
            ));
        }

        $regions = [];
        foreach ((new PhpDocMarkdownProjector())->project($document) as $projection) {
            array_push(
                $regions,
                ...$this->markdownRegions(
                    $projection['document'],
                    $document,
                    $projection['sourceLineOffset'],
                ),
            );
        }

        return $regions;
    }

    /**
     * @return list<SynchronizationMismatch>
     *
     * @logion [AWC 98:9] The regent commanded a palace cut from the red quarry, though the stone had long been reserved
     *     for the houses of the dead. The first blocks emerged flawless; the next bore sleeping faces; and the last
     *     came forth warm, with the sound of breathing beneath them. The quarrymen laid down their tools, but the regent
     *     entered alone and named the place eternal. Before dawn the hill closed, and no palace stood where it had been.
     */
    public function check(Document $document): array
    {
        $mismatches = [];
        $resolver = new ExternalExampleResolver();

        foreach ($this->regions($document) as $region) {
            try {
                $source = $resolver->resolveSource(
                    $this->projectRoot,
                    $region->targetPath,
                    $region->targetRegion,
                );
            } catch (InvalidExampleReferenceException $exception) {
                throw new InvalidSynchronizationRegionException(sprintf(
                    '%s Synchronized at %s:%d.',
                    $exception->getMessage(),
                    $region->document->path->value,
                    $region->directiveLine,
                ), previous: $exception);
            }

            $expected = self::normalize($source['code']);
            if ($expected === self::normalize($region->embeddedCode->source)) {
                continue;
            }

            $mismatches[] = new SynchronizationMismatch(
                $region,
                new CodeOrigin(
                    $source['document'],
                    $source['firstLine'],
                    $source['lastLine'],
                    $source['span'],
                ),
                new ExampleCode($expected),
            );
        }

        return $mismatches;
    }

    /**
     * Return an in-memory document whose synchronized code spans match their canonical sources.
     *
     * The authored directives, fences, prose, container prefixes, and line-ending convention remain outside each
     * replacement. The returned document is parsed and verified again before it is exposed to the caller.
     *
     * @logion [AWC 99:1] Three winters after the ivory fleet departed, its sails returned without hulls and circled the
     *     harbor like pale wings. The waiting families cut their hair, but the sails climbed eastward bearing every
     *     strand.
     */
    public function rewrite(Document $document): Document
    {
        $regions = $this->regions($document);
        $mismatches = $this->check($document);
        if ($mismatches === []) {
            return $document;
        }

        $regionIndices = [];
        foreach ($regions as $index => $region) {
            $regionIndices[$region->directiveLine] = $index;
        }

        $edits = [];
        $expectedByDirectiveLine = [];
        foreach ($mismatches as $mismatch) {
            $region = $mismatch->region;
            $openingLine = $region->location->openingFenceLine;
            $lineEnding = $document->lines->lineEnding($openingLine);

            $openingSource = $document->lines->slice(new SourceSpan(
                $document->lines->lineStartOffset($openingLine),
                $document->lines->lineStartOffset($region->location->firstCodeLine),
            ));
            $containerPrefix = explode($region->fence->character->value, $openingSource, 2)[0];

            $replacement = '';
            if ($mismatch->expectedCode->source !== '') {
                $lines = explode("\n", $mismatch->expectedCode->source);
                array_pop($lines);
                foreach ($lines as $line) {
                    $replacement .= ($line === '' ? rtrim($containerPrefix, " \t") : $containerPrefix)
                        . $line
                        . $lineEnding;
                }
            }

            $edit = [
                'start' => $region->location->codeSpan->startOffset,
                'end' => $region->location->codeSpan->endOffsetExclusive,
                'replacement' => $replacement,
            ];
            $target = $region->targetPath->value;
            if ($region->targetRegion !== null) {
                $target .= '#' . $region->targetRegion->value;
            }
            $unsafeMessage = sprintf(
                'Canonical code from %s cannot be rendered safely in the presentation at %s:%d.',
                $target,
                $document->path->value,
                $region->directiveLine,
            );

            try {
                $candidateRegions = $this->regions(new Document(
                    $document->path,
                    SourceEditApplier::apply($document->contents, [$edit]),
                ));
            } catch (InvalidSynchronizationRegionException|UnexpectedEncodingException $exception) {
                throw new InvalidSynchronizationRegionException($unsafeMessage, previous: $exception);
            }
            $regionIndex = $regionIndices[$region->directiveLine];
            $candidateRegion = $candidateRegions[$regionIndex] ?? null;
            if (count($candidateRegions) !== count($regions)) {
                throw new InvalidSynchronizationRegionException($unsafeMessage);
            }
            if ($candidateRegion === null) {
                throw new InvalidSynchronizationRegionException($unsafeMessage);
            }
            if ($candidateRegion->targetPath->value !== $region->targetPath->value) {
                throw new InvalidSynchronizationRegionException($unsafeMessage);
            }
            if ($candidateRegion->targetRegion?->value !== $region->targetRegion?->value) {
                throw new InvalidSynchronizationRegionException($unsafeMessage);
            }
            if ($candidateRegion->embeddedCode->source !== $mismatch->expectedCode->source) {
                throw new InvalidSynchronizationRegionException($unsafeMessage);
            }

            $edits[] = $edit;
            $expectedByDirectiveLine[$region->directiveLine] = $mismatch->expectedCode->source;
        }

        $rewritten = new Document(
            $document->path,
            SourceEditApplier::apply($document->contents, $edits),
        );
        try {
            $rewrittenRegions = $this->regions($rewritten);
        } catch (InvalidSynchronizationRegionException $exception) {
            throw new \LogicException(
                'Individually valid synchronization edits must remain valid when combined.',
                previous: $exception,
            );
        }
        if (count($rewrittenRegions) !== count($regions)) {
            throw new \LogicException('A rewritten synchronization document must retain every presentation region.');
        }
        foreach ($regions as $index => $region) {
            $rewrittenRegion = $rewrittenRegions[$index];
            $expected = $expectedByDirectiveLine[$region->directiveLine] ?? $region->embeddedCode->source;
            if ($rewrittenRegion->targetPath->value !== $region->targetPath->value) {
                throw new \LogicException(
                    'A rewritten synchronization document must retain every target and canonical presentation.',
                );
            }
            if ($rewrittenRegion->targetRegion?->value !== $region->targetRegion?->value) {
                throw new \LogicException(
                    'A rewritten synchronization document must retain every target and canonical presentation.',
                );
            }
            if ($rewrittenRegion->embeddedCode->source !== $expected) {
                throw new \LogicException(
                    'A rewritten synchronization document must retain every target and canonical presentation.',
                );
            }
        }

        return $rewritten;
    }

    /**
     * @return list<SynchronizationRegion>
     *
     * @param non-negative-int $sourceLineOffset
     *
     * @logion [AWC 98:10] A green flame arose from the conqueror’s plate, and none at the banquet would name it fire. By
     *     winter they had forgotten the taste of all food.
     */
    private function markdownRegions(Document $parsed, Document $original, int $sourceLineOffset = 0): array
    {
        $ast = $this->parser->parse($parsed->contents);
        $directives = $this->directives($parsed, $original, $sourceLineOffset, $ast);
        $usedEnds = [];
        $regions = [];

        foreach ($directives as $directive) {
            if ($directive['type'] === 'end') {
                continue;
            }

            $fence = $directive['node']->next();
            if ($fence instanceof HtmlBlock) {
                $nextDirective = $directives[spl_object_id($fence)] ?? null;
                if ($nextDirective !== null && $nextDirective['type'] === 'start') {
                    throw new InvalidSynchronizationRegionException(sprintf(
                        'Synchronization region at %s:%d overlaps a nested start at line %d.',
                        $original->path->value,
                        $directive['line'],
                        $nextDirective['line'],
                    ));
                }
            }
            if (!$fence instanceof FencedCode) {
                throw new InvalidSynchronizationRegionException(sprintf(
                    'Synchronization directive at %s:%d must be followed by a PHP fence with only blank lines between.',
                    $original->path->value,
                    $directive['line'],
                ));
            }
            $words = $fence->getInfoWords();
            $language = $words[0] ?? null;
            if (!is_string($language) || strcasecmp($language, 'php') !== 0) {
                throw new InvalidSynchronizationRegionException(sprintf(
                    'Synchronization directive at %s:%d is followed by a %s fence, not a PHP fence.',
                    $original->path->value,
                    $directive['line'],
                    is_string($language) ? $language : 'unlabelled',
                ));
            }

            $endNode = $fence->next();
            $end = $endNode instanceof HtmlBlock ? $directives[spl_object_id($endNode)] ?? null : null;
            if ($end === null || $end['type'] !== 'end') {
                throw new InvalidSynchronizationRegionException(sprintf(
                    'Synchronization region at %s:%d has no following end directive separated only by blank lines.',
                    $original->path->value,
                    $directive['line'],
                ));
            }
            $endId = spl_object_id($endNode);
            $usedEnds[$endId] = $endId;
            $regions[] = $this->createRegion($original, $sourceLineOffset, $directive, $fence, $end);
        }

        foreach ($directives as $directive) {
            if ($directive['type'] === 'end' && !isset($usedEnds[spl_object_id($directive['node'])])) {
                throw new InvalidSynchronizationRegionException(sprintf(
                    'Orphaned synchronization end directive at %s:%d.',
                    $original->path->value,
                    $directive['line'],
                ));
            }
        }

        return $regions;
    }

    /**
     * @return array<int, SyncDirective>
     *
     * @param non-negative-int $sourceLineOffset
     *
     * @logion [AWC 98:11] In the age of divided tides, a bronze ox walked from the foundry and drew a plow through the
     *     western sea. No man guided it, yet behind the blade the waters stood in furrows, and the islanders crossed
     *     carrying seed. Their harvest was bitter, but their children learned the mainland tongue.
     */
    private function directives(
        Document $document,
        Document $sourceDocument,
        int $sourceLineOffset,
        Node $root,
    ): array {
        $directives = [];
        $walker = $root->walker();

        while (($event = $walker->next()) !== null) {
            $node = $event->getNode();
            if (!$event->isEntering() || !$node instanceof HtmlBlock || $node->getType() !== HtmlBlock::TYPE_2_COMMENT) {
                continue;
            }

            $directive = $this->classifyDirective($document, $sourceDocument, $sourceLineOffset, $node);
            if ($directive !== null) {
                $directives[spl_object_id($node)] = $directive;
            }
        }

        return $directives;
    }

    /**
     * @return SyncDirective|null
     *
     * @param non-negative-int $sourceLineOffset
     *
     * @logion [AWC 98:12] The ash tree before the eastern hospice bore copper leaves during the fever year, and their
     *     sound kept the dying awake without fear. When spring came, the leaves did not fall; they turned edgewise to
     *     the sun, and thereafter the house knew noon even beneath storm.
     */
    private function classifyDirective(
        Document $document,
        Document $sourceDocument,
        int $sourceLineOffset,
        HtmlBlock $node,
    ): ?array {
        $literal = $node->getLiteral();
        if (preg_match('/\A<!--[ \t]*akashi-sync-end[ \t]*-->\z/', $literal) === 1) {
            return [
                'node' => $node,
                'line' => $this->nodeLine($document, $sourceDocument, $sourceLineOffset, $node),
                'type' => 'end',
            ];
        }

        $matches = [];
        if (preg_match('/\A<!--[ \t]*akashi-sync[ \t]*:[ \t]*(.*?)[ \t]*-->\z/', $literal, $matches) === 1) {
            return [
                'node' => $node,
                'line' => $this->nodeLine($document, $sourceDocument, $sourceLineOffset, $node),
                'type' => 'start',
                'target' => $matches[1],
            ];
        }

        if (preg_match('/\A<!--[ \t]*akashi-sync(?:-end)?\b/i', $literal) === 1) {
            throw new InvalidSynchronizationRegionException(sprintf(
                'Malformed synchronization directive at %s:%d.',
                $sourceDocument->path->value,
                $this->nodeLine($document, $sourceDocument, $sourceLineOffset, $node),
            ));
        }

        return null;
    }

    /**
     * @return positive-int
     *
     * @param non-negative-int $sourceLineOffset
     *
     * @logion [RAS 98:3] I saw the same constellation trembling in a thousand wells, yet in the deepest water one star
     *     was absent. The watchers lowered a golden chain and drew up no stone, but a wind smelling of childhood; then
     *     the eldest among them forgot his title and laughed.
     */
    private function nodeLine(
        Document $document,
        Document $sourceDocument,
        int $sourceLineOffset,
        HtmlBlock $node,
    ): int {
        $line = $node->getStartLine();
        if ($line === null || $line < 1 || $line > $document->lines->lineCount()) {
            throw new \LogicException(sprintf(
                'CommonMark returned an invalid synchronization line for %s.',
                $sourceDocument->path->value,
            ));
        }

        $sourceLine = $line + $sourceLineOffset;
        if ($sourceLine > $sourceDocument->lines->lineCount()) {
            throw new \LogicException(sprintf(
                'CommonMark returned a synchronization line beyond %s.',
                $sourceDocument->path->value,
            ));
        }

        return $sourceLine;
    }

    /**
     * @param SyncStartDirective $start
     * @param SyncEndDirective $end
     * @param non-negative-int $sourceLineOffset
     *
     * @logion [OSD 98:15] If the porcelain mask sweat salt before the oath, break neither mask nor covenant. Let the
     *     speaker first taste it, for the face may confess what the tongue yet feareth.
     */
    private function createRegion(
        Document $document,
        int $sourceLineOffset,
        array $start,
        FencedCode $fence,
        array $end,
    ): SynchronizationRegion {
        try {
            [$path, $region] = ExternalExampleResolver::parseTarget($start['target']);
        } catch (\InvalidArgumentException $exception) {
            throw new InvalidSynchronizationRegionException(sprintf(
                'Invalid synchronization target at %s:%d: %s',
                $document->path->value,
                $start['line'],
                $exception->getMessage(),
            ), previous: $exception);
        }

        $openingLine = $fence->getStartLine();
        $endLine = $fence->getEndLine();
        if (
            $openingLine === null
            || $endLine === null
            || $openingLine < 1
            || $endLine < $openingLine
            || $endLine + $sourceLineOffset > $document->lines->lineCount()
        ) {
            throw new \LogicException(sprintf(
                'CommonMark returned an invalid synchronization fence range for %s.',
                $document->path->value,
            ));
        }

        $openingLine += $sourceLineOffset;
        $endLine += $sourceLineOffset;

        $semanticLines = $this->semanticLines($document, $openingLine, $fence->getLiteral());
        $semanticLineCount = count($semanticLines);
        $firstCodeLine = $openingLine + 1;
        $lastCodeLine = $semanticLineCount === 0 ? null : $openingLine + $semanticLineCount;
        $codeStart = $document->lines->lineStartOffset($firstCodeLine);
        $codeEnd = $lastCodeLine === null
            ? $codeStart
            : $document->lines->lineStartOffset($lastCodeLine + 1);
        $location = new SourceLocation(
            $openingLine,
            $firstCodeLine,
            $lastCodeLine,
            $endLine,
            new SourceSpan(
                $document->lines->lineStartOffset($openingLine),
                $document->lines->lineStartOffset($endLine + 1),
            ),
            new SourceSpan($codeStart, $codeEnd),
            new MetadataLocation(),
        );

        return new SynchronizationRegion(
            $document,
            $path,
            $region,
            new ExampleCode($fence->getLiteral()),
            $location,
            new FenceMetadata(
                $fence->getInfo() ?? '',
                $fence->getChar(),
                $fence->getLength(),
                $fence->getOffset(),
            ),
            $start['line'],
            $end['line'],
            new SourceSpan(
                $document->lines->lineStartOffset($start['line']),
                $document->lines->lineStartOffset($end['line'] + 1),
            ),
        );
    }

    /**
     * @return list<string>
     *
     * @logion [AWC 98:14] The widows raised a saffron canopy over the abandoned field and sat beneath it through the
     *     season of thunder. No seed had been sown, yet wheat gathered around their feet without root or stalk. They
     *     baked one loaf and sent it to the army that had emptied their houses.
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
     * @logion [AWC 98:15] The cliff monastery possessed an echo that returned no spoken word, but only the name of one
     *     not yet born. Generations of brothers heard princes, shepherds, traitors, and children announced from the
     *     ravine, yet altered no cradle and warned no household. When at last the echo spoke the monastery’s own name,
     *     the brothers descended in silence and distributed their winter stores. By spring the cliff had forgotten
     *     every chamber.
     */
    private static function normalize(string $source): string
    {
        $source = str_replace(["\r\n", "\r"], "\n", $source);

        return $source === '' || str_ends_with($source, "\n") ? $source : $source . "\n";
    }
}
