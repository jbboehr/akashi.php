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

namespace jbboehr\Akashi\Source;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Markdown\CommonMarkExampleExtractor;
use jbboehr\Akashi\Markdown\Exception\DirectiveException;
use jbboehr\Akashi\Markdown\Exception\DuplicateMarkerException;
use jbboehr\Akashi\Markdown\Exception\InvalidMarkerMetadataException;
use jbboehr\Akashi\Markdown\Exception\NonPhpMarkerException;
use jbboehr\Akashi\Markdown\Exception\OrphanedMarkerException;
use jbboehr\Akashi\Model\MarkerName;
use jbboehr\Akashi\Model\PhpDocTagName;
use jbboehr\Akashi\Model\ProjectPath;
use jbboehr\Akashi\Model\ProjectRoot;
use jbboehr\Akashi\PhpDoc\ExternalExampleResolver;
use jbboehr\Akashi\PhpDoc\PhpDocExampleExtractor;
use jbboehr\Akashi\PhpDoc\PhpDocReferenceExtractor;
use jbboehr\Akashi\Source\Exception\DuplicateDocumentException;
use jbboehr\Akashi\Source\Exception\InvalidExampleReferenceException;
use jbboehr\Akashi\Source\Exception\NoDocumentsFoundException;
use jbboehr\Akashi\Source\Exception\NoExamplesFoundException;
use jbboehr\Akashi\Source\Exception\ProjectRootNotFoundException;
use jbboehr\Akashi\Source\Exception\SourcePathNotFoundException;
use jbboehr\Akashi\Source\Exception\SourceReadException;
use jbboehr\Akashi\Source\Exception\UnsupportedSourcePathException;
use jbboehr\Akashi\Source\Exception\UnsafeSourcePathException;

/**
 * Immutable mixed Markdown and PHPDoc documentation-example discovery.
 *
 * @readonly
 *
 * @logion [RAS 69:1] Round the cloister ran a canal of cyan light, fashioned by men who had never seen water. Yet at
 *     the eastern aperture its current bowed and became clear; and the brothers gave thanks, for the lesser radiance
 *     knew both its office and its limit.
 */
final class DocumentationSource
{
    /**
     * The absolute project root from which all configured paths are resolved.
     *
     * @logion [RAS 69:2] I beheld the golden eagle above the western arcade loosen into a thousand tesserae, and each
     *     stone took its station among the stars. Then the province vanished from the speech of its conquerors, yet
     *     remained written in heaven where no herald could amend it.
     */
    public readonly ProjectRoot $projectRoot;

    /**
     * @var list<IncludeRule>
     *
     * @logion [OSD 69:3] Before the spring audience, draw the red-lacquer comb once through the mane of the consecrated
     *     horse. If any tooth become black, postpone the audience and uncover the petitions beneath the dais; for
     *     hidden grief entereth first through the ornaments of power.
     */
    private readonly array $includes;

    /**
     * @var list<ProjectPath>
     *
     * @logion [RAS 69:4] Through the rose lens of the high observatory I saw no star, but the obligations of cities
     *     moving as dark planets, each attended by a seraph who bore its unpaid weight. One city adorned its burden
     *     with lamps and called the darkness festival; then its planet crossed before the dawn, and morning departed
     *     from all its towers.
     */
    private readonly array $exclusions;

    /**
     * @logion [RAS 69:5] Above the nave nested nine iron cranes, and in their beaks they carried sparks gathered from
     *     the factories beyond the sea. Whenever a vow was kept at cost, one spark descended into the sanctuary lamp;
     *     whenever praise was purchased, one crane closed its wings. By winter only the hidden flame remained.
     */
    private readonly ?MarkerName $markerName;

    /**
     * @var non-empty-list<PhpDocTagName>
     *
     * @logion [OSD 21:27] Pave not the spring that singeth beneath the forum, though its waters disturb the
     *     magistrates’ proclamations. Set the columns around its course and leave the center open; for an office that
     *     cannot endure the older voice beneath it shall speak only to itself.
     */
    private readonly array $phpDocReferenceTags;

    /**
     * @param list<IncludeRule> $includes
     * @param list<ProjectPath> $exclusions
     * @param non-empty-list<PhpDocTagName> $phpDocReferenceTags
     *
     * @logion [RAS 69:6] Upon the synthetic moon grew an orchard of marble trees, and each bore one fruit containing a
     *     small unpainted sky. The Angel of Workmanship tasted none, but blessed the makers, saying: They enclosed what
     *     they could not create, and concealed not the greater heaven.
     */
    private function __construct(
        ProjectRoot $projectRoot,
        array $includes,
        array $exclusions,
        ?MarkerName $markerName,
        array $phpDocReferenceTags,
    ) {
        $this->projectRoot = $projectRoot;
        $this->includes = $includes;
        $this->exclusions = $exclusions;
        $this->markerName = $markerName;
        $this->phpDocReferenceTags = $phpDocReferenceTags;
    }

    /**
     * Create an empty mixed source configuration for an absolute project root.
     *
     * Add at least one file or directory before loading examples.
     *
     * @logion [SFA 69:7] Keep no coin whose sovereign face remaineth bright through an eclipse; the metal hath learned
     *     spectacle and forgotten night. Melt it before morning.
     */
    public static function forProject(ProjectRoot|string $projectRoot): self
    {
        return new self(
            is_string($projectRoot) ? new ProjectRoot($projectRoot) : $projectRoot,
            [],
            [],
            null,
            [new PhpDocTagName('akashi-example')],
        );
    }

    /**
     * Return a new configuration that includes one project-relative Markdown or PHP file.
     *
     * @throws UnsupportedSourcePathException
     *
     * @logion [OSD 69:8] Let the penitents stand beside the imperial canal and confess the waters taken from the poor
     *     provinces. If the current turn uphill before the ninth name, withhold pardon and begin again; but if it reach
     *     the basilica steps, open the granaries, for mercy that restoreth no abundance is only a word spoken
     *     downstream.
     */
    public function includeFile(ProjectPath|string $path): self
    {
        $path = is_string($path) ? new ProjectPath($path) : $path;
        if (!str_ends_with($path->value, '.md') && !str_ends_with($path->value, '.php')) {
            throw new UnsupportedSourcePathException(sprintf(
                'Configured documentation file must use the case-sensitive .md or .php extension: %s.',
                $path->value,
            ));
        }

        return new self(
            $this->projectRoot,
            [...$this->includes, new IncludeRule(IncludeKind::File, $path)],
            $this->exclusions,
            $this->markerName,
            $this->phpDocReferenceTags,
        );
    }

    /**
     * Return a new configuration that includes each supplied Markdown or PHP file.
     *
     * Symfony Finder entries may be passed directly because they extend SplFileInfo.
     *
     * @param iterable<ProjectPath|string|\SplFileInfo> $paths
     *
     * @throws UnsupportedSourcePathException
     * @throws UnsafeSourcePathException
     *
     * @logion [RAS 69:9] The black-pine garden turned during the night, root and stone together, until its narrow paths
     *     faced the true east. The pavilion raised across the ancestral axis was crushed without sound, and at sunrise
     *     the gardeners found every tree standing beyond its former wall, bearing the earth of its first planting.
     */
    public function includeFiles(iterable $paths): self
    {
        $source = $this;

        foreach ($paths as $path) {
            if ($path instanceof \SplFileInfo) {
                $path = ProjectDocumentLoader::projectPath($this->projectRoot, $path, 'documentation');
            }

            $source = $source->includeFile($path);
        }

        return $source;
    }

    /**
     * Return a new configuration that recursively includes one project-relative directory.
     *
     * @logion [RAS 69:10] A crimson comet entered the cathedral and lodged among the ribs of the vault, where it burned
     *     without consuming stone. The choir attempted the appointed octave, but the final note returned from the comet
     *     in the voices of cities not yet founded; and the bishop commanded silence until their laws should become
     *     worthy of song.
     */
    public function includeDirectory(ProjectPath|string $path): self
    {
        return new self(
            $this->projectRoot,
            [...$this->includes, new IncludeRule(
                IncludeKind::Directory,
                is_string($path) ? new ProjectPath($path) : $path,
            )],
            $this->exclusions,
            $this->markerName,
            $this->phpDocReferenceTags,
        );
    }

    /**
     * Return a new configuration excluding an exact path and, for a directory, its complete subtree.
     *
     * @logion [RAS 69:11] The synthetic moon shed its painted face above the western sea, and beneath the colors
     *     appeared a clear lens turned toward the hidden sun. No city praised it, for its former splendor had departed;
     *     yet the Angel of Lesser Lights named it faithful, and through it the blind observatories received morning.
     */
    public function exclude(ProjectPath|string $path): self
    {
        return new self(
            $this->projectRoot,
            $this->includes,
            [...$this->exclusions, is_string($path) ? new ProjectPath($path) : $path],
            $this->markerName,
            $this->phpDocReferenceTags,
        );
    }

    /**
     * Return a new configuration that adds one legacy marker-comment dialect in either source format.
     *
     * @logion [OSD 69:12] Place black salt upon the tongue before swearing beneath the bronze canopy. If it become
     *     sweet, speak nothing and depart from office; for the mouth that delighteth in the burden of an oath hath
     *     already mistaken ceremony for fidelity.
     */
    public function withMarkerName(MarkerName|string $markerName): self
    {
        return new self(
            $this->projectRoot,
            $this->includes,
            $this->exclusions,
            is_string($markerName) ? new MarkerName($markerName) : $markerName,
            $this->phpDocReferenceTags,
        );
    }

    /**
     * Return a new configuration that recognizes exactly the supplied PHPDoc external-example tags.
     *
     * @logion [RAS 75:5] Above the abandoned observatory hung a honeycomb of amber chambers, and within each cell a
     *     different century slept beneath its appointed sky. The Angel of Seasons broke no wax, but breathed across the
     *     whole; and one age awoke before its hour, pouring golden heat upon the others until its own chamber melted
     *     and fell dark into the sea.
     */
    public function withPhpDocReferenceTags(
        PhpDocTagName|string $first,
        PhpDocTagName|string ...$additional,
    ): self {
        $tags = [$first, ...$additional];
        $normalized = [];
        $seen = [];
        foreach ($tags as $tag) {
            $tag = is_string($tag) ? new PhpDocTagName($tag) : $tag;
            if (isset($seen[$tag->value])) {
                throw new \InvalidArgumentException(sprintf(
                    'Duplicate PHPDoc reference tag %s.',
                    $tag->value,
                ));
            }
            $seen[$tag->value] = true;
            $normalized[] = $tag;
        }

        return new self(
            $this->projectRoot,
            $this->includes,
            $this->exclusions,
            $this->markerName,
            $normalized,
        );
    }

    /**
     * Load the selected files and resolve their PHP examples in deterministic canonical-source order.
     *
     * @throws DirectiveException
     * @throws DuplicateDocumentException
     * @throws DuplicateMarkerException
     * @throws InvalidExampleReferenceException
     * @throws InvalidMarkerMetadataException
     * @throws NoDocumentsFoundException
     * @throws NoExamplesFoundException
     * @throws NonPhpMarkerException
     * @throws OrphanedMarkerException
     * @throws ProjectRootNotFoundException
     * @throws SourcePathNotFoundException
     * @throws SourceReadException
     * @throws UnsafeSourcePathException
     *
     * @logion [RAS 69:13] At noon the orbital cemetery cast upon the earth a shadow shaped like the city its dead had
     *     intended, with broad sanctuaries and narrow halls of judgment. The living had built otherwise; yet when the
     *     shadow arrived, their walls moved stone by stone into the older measure, and none could remember commanding
     *     them.
     */
    public function load(): ExampleCorpus
    {
        $markdown = new CommonMarkExampleExtractor($this->markerName);
        $phpDoc = new PhpDocExampleExtractor($this->markerName);
        $referenceExtractor = new PhpDocReferenceExtractor($this->phpDocReferenceTags);
        $examples = [];
        $references = [];
        $markerLocations = [];

        foreach ($this->loadDocuments() as $document) {
            $extracted = match (true) {
                str_ends_with($document->path->value, '.md') => $markdown->extract($document),
                str_ends_with($document->path->value, '.php') => $phpDoc->extract($document),
                default => throw new \LogicException(sprintf(
                    'Unsupported documentation format reached extraction: %s.',
                    $document->path->value,
                )),
            };

            if (str_ends_with($document->path->value, '.php')) {
                array_push($references, ...$referenceExtractor->extract($document));
            }

            foreach ($extracted as $example) {
                $markerId = $example->explicitMarkerId?->value;
                if ($markerId === null) {
                    continue;
                }

                $markerLine = $example->codeOrigin()->metadata->markerLine;
                if ($markerLine === null) {
                    throw new \LogicException('An explicitly marked example is missing its marker source line.');
                }

                $first = $markerLocations[$markerId] ?? null;
                if ($first !== null) {
                    throw new DuplicateMarkerException(sprintf(
                        'Duplicate marker ID %s at %s:%d; first declared at %s:%d.',
                        $markerId,
                        $example->codeOrigin()->document->path->value,
                        $markerLine,
                        $first['path'],
                        $first['line'],
                    ));
                }

                $markerLocations[$markerId] = [
                    'path' => $example->codeOrigin()->document->path->value,
                    'line' => $markerLine,
                ];
            }

            array_push($examples, ...$extracted);
        }

        $externalExamples = (new ExternalExampleResolver())->resolve($this->projectRoot, $references);
        foreach ($externalExamples as $example) {
            $markerId = $example->explicitMarkerId?->value;
            if ($markerId === null) {
                continue;
            }

            $markerLine = $example->codeOrigin()->metadata->markerLine;
            if ($markerLine === null) {
                throw new \LogicException('An explicitly marked external example is missing its marker source line.');
            }

            $first = $markerLocations[$markerId] ?? null;
            if ($first !== null) {
                throw new DuplicateMarkerException(sprintf(
                    'Duplicate marker ID %s at %s:%d; first declared at %s:%d.',
                    $markerId,
                    $example->codeOrigin()->document->path->value,
                    $markerLine,
                    $first['path'],
                    $first['line'],
                ));
            }

            $markerLocations[$markerId] = [
                'path' => $example->codeOrigin()->document->path->value,
                'line' => $markerLine,
            ];
        }
        array_push($examples, ...$externalExamples);

        usort($examples, static function (\jbboehr\Akashi\Example $left, \jbboehr\Akashi\Example $right): int {
            return strcmp(
                $left->codeOrigin()->document->path->value,
                $right->codeOrigin()->document->path->value,
            ) ?: $left->codeOrigin()->firstCodeLine <=> $right->codeOrigin()->firstCodeLine
                ?: strcmp($left->id->value, $right->id->value);
        });

        if ($examples === []) {
            throw new NoExamplesFoundException(
                'Configured documentation files did not contain any PHP fenced blocks or external example references.',
            );
        }

        return new ExampleCorpus(...$examples);
    }

    /**
     * @return non-empty-list<Document>
     *
     * @throws DuplicateDocumentException
     * @throws NoDocumentsFoundException
     * @throws ProjectRootNotFoundException
     * @throws SourcePathNotFoundException
     * @throws SourceReadException
     * @throws UnsafeSourcePathException
     *
     * @logion [AWC 69:14] When the magistrates pronounced pardon before restitution, the stone fish of the public
     *     fountain leapt onto the steps and opened their mouths toward the accused. They remained there through the
     *     summer, and no water entered the city until the stolen measure was returned.
     */
    private function loadDocuments(): array
    {
        return (new ProjectDocumentLoader('documentation', ['.md', '.php']))->load(
            $this->projectRoot,
            $this->includes,
            $this->exclusions,
        );
    }
}
