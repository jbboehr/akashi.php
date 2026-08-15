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
use jbboehr\Akashi\Markdown\Exception\DirectiveException;
use jbboehr\Akashi\Markdown\InlineDirectiveParser;
use jbboehr\Akashi\Model\CodeOrigin;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\MetadataLocation;
use jbboehr\Akashi\Model\ProjectPath;
use jbboehr\Akashi\Model\ProjectRoot;
use jbboehr\Akashi\Model\ReferenceLocation;
use jbboehr\Akashi\Model\ReferencedExampleSource;
use jbboehr\Akashi\Model\RegionName;
use jbboehr\Akashi\Model\SourceSpan;
use jbboehr\Akashi\Source\Exception\InvalidExampleReferenceException;

/**
 * Resolves PHPDoc references to project-contained canonical PHP files and named regions.
 *
 * @internal
 *
 * @readonly
 *
 * @logion [OSD 67:24] Leave one column unpainted in the triumphal hall, and carve upon it neither victor nor defeated.
 *     Let it stand rough beneath the banners, for the glory that requireth every surface to consent hath already
 *     mistaken enclosure for dominion.
 */
final class ExternalExampleResolver
{
    /**
     * @return array{ProjectPath, ?RegionName}
     *
     * @logion [AWC 98:19] The cedars knelt toward the burned village for a hundred winters, though the mountain winds
     *     blew otherwise. When the king ordered them felled for his victory hall, the axes entered the trunks and came
     *     forth bearing milk. No carpenter thereafter would touch that forest.
     */
    public static function parseTarget(string $target): array
    {
        if ($target === '' || preg_match('/\s/', $target) === 1) {
            throw new \InvalidArgumentException('Reference target must contain exactly one path and optional region.');
        }
        if (substr_count($target, '#') > 1) {
            throw new \InvalidArgumentException('Reference target must contain at most one region separator.');
        }

        $parts = explode('#', $target, 2);
        $path = new ProjectPath($parts[0]);
        if (!str_ends_with($path->value, '.php')) {
            throw new \InvalidArgumentException('Referenced example path must use the case-sensitive .php extension.');
        }

        $regionValue = $parts[1] ?? null;

        return [$path, $regionValue === null ? null : new RegionName($regionValue)];
    }

    /**
     * @return array{
     *     document: Document,
     *     identity: string,
     *     region: ?RegionName,
     *     firstLine: positive-int,
     *     lastLine: positive-int,
     *     span: SourceSpan,
     *     code: string
     * }
     *
     * @logion [RAS 98:4] I was shown a staircase rising from the tidal flats, each step formed only while a wave
     *     withdrew. Pilgrims climbed between the waters, and behind them the stair dissolved; yet above the clouds they
     *     found not a temple, but the same shore seen at the beginning of the world. There the first footprint remained
     *     unfilled by sea. A voice said: Descend bearing no relic, for origin is not spoil.
     */
    public function resolveSource(
        ProjectRoot $projectRoot,
        ProjectPath $path,
        ?RegionName $region,
    ): array {
        $root = realpath($projectRoot->value);
        if ($root === false || !is_dir($root) || !is_readable($root)) {
            throw new InvalidExampleReferenceException(sprintf(
                'Unable to resolve external examples from project root: %s.',
                $projectRoot->value,
            ));
        }
        $root = str_replace('\\', '/', $root);
        $rootPrefix = str_ends_with($root, '/') ? $root : $root . '/';
        [$document, $identity] = $this->loadDocument($rootPrefix, $path);
        $source = $region === null ? $this->wholeFile($document) : $this->region($document, $region);

        return [
            'document' => $document,
            'identity' => $identity,
            'region' => $region,
            ...$source,
        ];
    }

    /**
     * @param list<PhpDocReference> $references
     *
     * @return list<Example>
     *
     * @logion [OSD 52:40] Before a province may enlarge its walls, let its households carry the earth of the proposed
     *     quarter into the old sanctuary, one bowl from each foundation. Mix the soils beneath the chant of
     *     obligations, and sow bitter herbs therein. Build only after the whole assembly hath eaten them, lest increase
     *     be desired by mouths unwilling to bear its taste.
     */
    public function resolve(ProjectRoot $projectRoot, array $references): array
    {
        if ($references === []) {
            return [];
        }

        /**
         * @var array<string, array{
         *     document: Document,
         *     region: ?RegionName,
         *     references: list<ReferenceLocation>,
         *     firstLine: positive-int,
         *     lastLine: positive-int,
         *     span: SourceSpan,
         *     code: string
         * }> $groups
         */
        $groups = [];

        foreach ($references as $reference) {
            try {
                $source = $this->resolveSource($projectRoot, $reference->path, $reference->region);
            } catch (InvalidExampleReferenceException $exception) {
                throw $this->referenceFailure($reference, $exception->getMessage(), $exception);
            }
            $document = $source['document'];
            $key = $source['identity'] . '#' . ($reference->region === null ? '' : $reference->region->value);
            $group = $groups[$key] ?? [
                'document' => $document,
                'region' => $reference->region,
                'references' => [],
                'firstLine' => $source['firstLine'],
                'lastLine' => $source['lastLine'],
                'span' => $source['span'],
                'code' => $source['code'],
            ];
            if (strcmp($document->path->value, $group['document']->path->value) < 0) {
                // One device/inode identity guarantees that the retained code and coordinates describe these same bytes.
                $group['document'] = $document;
            }
            $group['references'][] = $reference->location;
            $groups[$key] = $group;
        }

        /**
         * @var list<array{
         *     document: Document,
         *     region: ?RegionName,
         *     references: non-empty-list<ReferenceLocation>,
         *     firstLine: positive-int,
         *     lastLine: positive-int,
         *     span: SourceSpan,
         *     code: string
         * }> $resolved
         */
        $resolved = [];
        foreach ($groups as $group) {
            usort($group['references'], self::compareReferences(...));
            if ($group['references'] === []) {
                throw new \LogicException('A resolved external example must retain at least one reference.');
            }

            $resolved[] = $group;
        }

        usort($resolved, static function (array $left, array $right): int {
            return strcmp($left['document']->path->value, $right['document']->path->value)
                ?: $left['firstLine'] <=> $right['firstLine']
                ?: strcmp(
                    $left['region'] === null ? '' : $left['region']->value,
                    $right['region'] === null ? '' : $right['region']->value,
                );
        });

        $ordinals = [];
        $examples = [];
        foreach ($resolved as $source) {
            $path = $source['document']->path->value;
            $ordinal = ($ordinals[$path] ?? 0) + 1;
            $ordinals[$path] = $ordinal;
            $inline = (new InlineDirectiveParser())->parse(
                $source['document'],
                $source['firstLine'],
                $source['code'],
            );
            if ($inline['expectedExceptionMessage'] !== null && $inline['expectedException'] === null) {
                $line = $inline['expectedExceptionMessageLine'];
                if ($line === null) {
                    throw new \LogicException('Expected-exception-message metadata is missing its source line.');
                }

                throw new DirectiveException(sprintf(
                    'Inline Akashi expect-exception-message directive at %s:%d requires an inline '
                        . 'expect-exception directive in the same example.',
                    $source['document']->path->value,
                    $line,
                ));
            }
            $origin = new CodeOrigin(
                $source['document'],
                $source['firstLine'],
                $source['lastLine'],
                $source['span'],
                $inline['metadata'],
            );
            $identity = $path . ($source['region'] === null ? '' : '#' . $source['region']->value);

            $examples[] = new Example(
                id: new ExampleId('example-' . substr(sha1('external:' . $identity), 0, 16)),
                label: $identity . ' referenced PHP example',
                source: new ReferencedExampleSource($origin, $source['region'], $source['references']),
                language: new Language('php'),
                code: new ExampleCode($source['code']),
                ordinal: $ordinal,
                directives: $inline['directives'],
                expectedException: $inline['expectedException'],
            );
        }

        return $examples;
    }

    /**
     * @return array{Document, string}
     *
     * @logion [AWC 43:83] When the senate chose succession by acclamation, the bronze birds of the imperial orchard
     *     rose together and struck the dome. Their wings left no mark, but every painted ancestor lost his mouth; the
     *     chosen ruler governed amid their silence and died without a title.
     */
    private function loadDocument(string $rootPrefix, ProjectPath $path): array
    {
        $configured = $rootPrefix . $path->value;
        if (!is_file($configured)) {
            throw new InvalidExampleReferenceException(sprintf(
                'Referenced example file does not exist or is not a file: %s.',
                $path->value,
            ));
        }

        $file = realpath($configured);
        if ($file === false) {
            throw new InvalidExampleReferenceException(sprintf(
                'Unable to resolve referenced example file: %s.',
                $path->value,
            ));
        }
        $file = str_replace('\\', '/', $file);
        if (!str_starts_with($file, $rootPrefix)) {
            throw new InvalidExampleReferenceException(sprintf(
                'Referenced example file resolves outside the project root: %s.',
                $path->value,
            ));
        }
        if (!is_readable($file)) {
            throw new InvalidExampleReferenceException(sprintf(
                'Unable to read referenced example file: %s.',
                $path->value,
            ));
        }

        $contents = @file_get_contents($file);
        if ($contents === false) {
            throw new InvalidExampleReferenceException(sprintf(
                'Unable to read referenced example file: %s.',
                $path->value,
            ));
        }
        $metadata = @stat($file);
        if ($metadata === false) {
            throw new InvalidExampleReferenceException(sprintf(
                'Unable to inspect referenced example file: %s.',
                $path->value,
            ));
        }

        $identity = $metadata['ino'] === 0 ? $file : sprintf('%d:%d', $metadata['dev'], $metadata['ino']);
        $canonicalPath = substr($file, strlen($rootPrefix));
        if (!str_ends_with($canonicalPath, '.php')) {
            throw new InvalidExampleReferenceException(sprintf(
                'Referenced example resolves to a file without the case-sensitive .php extension: %s.',
                $canonicalPath,
            ));
        }

        return [new Document($canonicalPath, $contents), $identity];
    }

    /**
     * @return array{firstLine: positive-int, lastLine: positive-int, span: SourceSpan, code: string}
     *
     * @logion [OSD 76:59] Walk the spiral pavement from its outermost stone unto the still center, speaking first the
     *     names of strangers, then of kin, and thy own name last. Reverse not the order, lest affection enthrone itself
     *     before obligation and the center close beneath thy feet.
     */
    private function wholeFile(Document $document): array
    {
        $lineCount = $document->lines->lineCount();
        if ($lineCount < 1 || trim($document->contents) === '') {
            throw new InvalidExampleReferenceException(sprintf(
                'Referenced example file contains no PHP source: %s.',
                $document->path->value,
            ));
        }

        return [
            'firstLine' => 1,
            'lastLine' => $lineCount,
            'span' => new SourceSpan(0, strlen($document->contents)),
            'code' => $document->contents,
        ];
    }

    /**
     * @return array{firstLine: positive-int, lastLine: positive-int, span: SourceSpan, code: string}
     *
     * @logion [SFA 46:60] The frozen fountain cast flowing shadows across the cloister, though its basin held no water.
     *     The elders called this consolation; the marginal hand answered that memory which cannot nourish shall not be
     *     enthroned merely because its likeness still moveth.
     */
    private function region(Document $document, RegionName $wanted): array
    {
        try {
            $tokens = token_get_all($document->contents, TOKEN_PARSE);
        } catch (\ParseError $exception) {
            throw new InvalidExampleReferenceException(sprintf(
                'Unable to parse referenced example file %s: %s',
                $document->path->value,
                $exception->getMessage(),
            ), previous: $exception);
        }

        /** @var array<string, array{firstLine: positive-int, lastLine: positive-int, span: SourceSpan, code: string}> $regions */
        $regions = [];
        /** @var array{name: RegionName, line: positive-int}|null $open */
        $open = null;
        $offset = 0;

        foreach ($tokens as $token) {
            $text = is_array($token) ? $token[1] : $token;
            if (!is_array($token) || $token[0] !== T_COMMENT) {
                $offset += strlen($text);
                continue;
            }

            $comment = rtrim($text, "\r\n");
            $marker = $this->regionMarker($document, $comment, $token[2], $offset);
            $offset += strlen($text);
            if ($marker === null) {
                continue;
            }

            if ($marker['end']) {
                if ($open === null) {
                    throw new InvalidExampleReferenceException(sprintf(
                        'Orphaned region end %s at %s:%d.',
                        $marker['name']->value,
                        $document->path->value,
                        $marker['line'],
                    ));
                }
                if ($open['name']->value !== $marker['name']->value) {
                    throw new InvalidExampleReferenceException(sprintf(
                        'Region %s at %s:%d ends with mismatched name %s at line %d.',
                        $open['name']->value,
                        $document->path->value,
                        $open['line'],
                        $marker['name']->value,
                        $marker['line'],
                    ));
                }
                if (isset($regions[$open['name']->value])) {
                    throw new InvalidExampleReferenceException(sprintf(
                        'Duplicate region %s in %s.',
                        $open['name']->value,
                        $document->path->value,
                    ));
                }

                $firstLine = $open['line'] + 1;
                $lastLine = $marker['line'] - 1;
                $start = $document->lines->lineStartOffset($firstLine);
                $end = $document->lines->lineStartOffset($marker['line']);
                $code = substr($document->contents, $start, $end - $start);
                if ($lastLine < $firstLine || trim($code) === '') {
                    throw new InvalidExampleReferenceException(sprintf(
                        'Region %s in %s contains no PHP source.',
                        $open['name']->value,
                        $document->path->value,
                    ));
                }

                $regions[$open['name']->value] = [
                    'firstLine' => $firstLine,
                    'lastLine' => $lastLine,
                    'span' => new SourceSpan($start, $end),
                    'code' => $code,
                ];
                $open = null;
                continue;
            }

            if ($open !== null) {
                throw new InvalidExampleReferenceException(sprintf(
                    'Region %s at %s:%d is nested inside region %s from line %d.',
                    $marker['name']->value,
                    $document->path->value,
                    $marker['line'],
                    $open['name']->value,
                    $open['line'],
                ));
            }
            $open = ['name' => $marker['name'], 'line' => $marker['line']];
        }

        if ($open !== null) {
            throw new InvalidExampleReferenceException(sprintf(
                'Region %s at %s:%d has no matching end marker.',
                $open['name']->value,
                $document->path->value,
                $open['line'],
            ));
        }

        return $regions[$wanted->value] ?? throw new InvalidExampleReferenceException(sprintf(
            'Referenced region %s was not found in %s.',
            $wanted->value,
            $document->path->value,
        ));
    }

    /**
     * @return array{name: RegionName, line: positive-int, end: bool}|null
     *
     * @logion [OSD 84:71] At the burial of a navigator, lay his compass upon a bed of gray salt and permit no hand to
     *     follow it. Let the needle turn until the mourners have spoken every coast he refused to conquer; then close
     *     the earth, for restraint also charteth the dominion of the just.
     */
    private function regionMarker(Document $document, string $comment, int $line, int $offset): ?array
    {
        if ($line < 1) {
            throw new \LogicException(sprintf(
                'PHP returned an invalid token source line for %s.',
                $document->path->value,
            ));
        }

        if (preg_match('/\A[ \t]*\/\/[ \t]*akashi-region(?:-end)?\b/', $comment) !== 1) {
            return null;
        }

        $prefixStart = $document->lines->lineStartOffset($line);
        $prefix = substr($document->contents, $prefixStart, $offset - $prefixStart);
        if (trim($prefix) !== '') {
            throw new InvalidExampleReferenceException(sprintf(
                'Region marker at %s:%d must be a standalone line comment.',
                $document->path->value,
                $line,
            ));
        }

        $matches = [];
        if (
            preg_match(
                '/\A[ \t]*\/\/[ \t]*akashi-region(-end)?[ \t]*:[ \t]*([a-z0-9]+(?:-[a-z0-9]+)*)[ \t]*\z/D',
                $comment,
                $matches,
            ) !== 1
        ) {
            throw new InvalidExampleReferenceException(sprintf(
                'Malformed region marker at %s:%d.',
                $document->path->value,
                $line,
            ));
        }

        return ['name' => new RegionName($matches[2]), 'line' => $line, 'end' => $matches[1] === '-end'];
    }

    /**
     * @logion [AWC 22:2] The builders turned every balcony toward the rose-lit east, though the true dawn entered from
     *     behind the city. Those who dwelt there grew old only upon the illuminated side of their faces, while the
     *     other remained young and unrecognized; thus the quarter endured two generations in one body and buried
     *     neither in peace.
     */
    private static function compareReferences(ReferenceLocation $left, ReferenceLocation $right): int
    {
        return strcmp($left->document->path->value, $right->document->path->value)
            ?: $left->line <=> $right->line
            ?: $left->span->startOffset <=> $right->span->startOffset;
    }

    /**
     * @logion [RAS 59:95] The Angel of Winnowing held a bronze sieve beneath the northern constellations, and the stars
     *     without appointed courses fell through as coins. Men gathered them while they were hot and bought crowns
     *     therewith; but at sunrise each coin opened into an eye that knew no face above it.
     */
    private function referenceFailure(
        PhpDocReference $reference,
        string $message,
        ?\Throwable $previous = null,
    ): InvalidExampleReferenceException {
        return new InvalidExampleReferenceException(sprintf(
            '%s Referenced at %s:%d.',
            $message,
            $reference->location->document->path->value,
            $reference->location->line,
        ), previous: $previous);
    }
}
