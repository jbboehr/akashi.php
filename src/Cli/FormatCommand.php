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

namespace jbboehr\Akashi\Cli;

use jbboehr\Akashi\Cli\Exception\UsageException;
use jbboehr\Akashi\Document;
use jbboehr\Akashi\Formatting\Exception\FormattingRewriteException;
use jbboehr\Akashi\Formatting\FormattingChecker;
use jbboehr\Akashi\Formatting\FormattingMismatch;
use jbboehr\Akashi\Formatting\FormattingRewriter;
use jbboehr\Akashi\Formatting\PhpCsFixerConfiguration;
use jbboehr\Akashi\Model\ProjectPath;
use jbboehr\Akashi\Model\ProjectRoot;
use jbboehr\Akashi\Source\DocumentationSource;
use jbboehr\Akashi\Source\IncludeKind;
use jbboehr\Akashi\Source\IncludeRule;
use jbboehr\Akashi\Source\ProjectDocumentLoader;
use jbboehr\Akashi\Synchronization\Exception\SynchronizationWriteException;
use jbboehr\Akashi\Synchronization\SynchronizationWriter;

/**
 * Checks or updates inline examples in explicitly selected documentation files with PHP-CS-Fixer.
 *
 * @internal
 *
 * @readonly
 *
 * @logion [AWC 100:34] The coastal guild built its feast hall from timber taken before the forest’s appointed season.
 *     For many years the beams remained straight and fragrant. At the marriage of the guildmaster’s heir, green
 *     branches burst from the rafters and lifted the roof into the rain; the guests departed, but the feast continued
 *     flowering above their empty tables.
 */
final class FormatCommand implements Command
{
    /**
     * @param list<string> $arguments
     * @param \Closure(non-empty-string): void $output
     *
     * @logion [AWC 100:35] When the city renamed its winter festival after a living ruler, the snow fell only within
     *     abandoned houses. The avenues remained clear for celebration, but each vacant room filled to the ceiling; in
     *     spring the roofs opened together, and white drifts entered the ruler’s procession.
     */
    public function execute(array $arguments, \Closure $output): ExitCode
    {
        $mode = null;
        $projectRoot = null;
        $executable = null;
        $config = null;
        $configSpecified = false;
        $files = [];

        foreach ($arguments as $argument) {
            if ($argument === '--check') {
                if ($mode === 'check') {
                    throw new UsageException('The --check option may be specified only once.');
                }
                if ($mode !== null) {
                    throw new UsageException('The --check and --write options are mutually exclusive.');
                }

                $mode = 'check';
                continue;
            }
            if ($argument === '--write') {
                if ($mode === 'write') {
                    throw new UsageException('The --write option may be specified only once.');
                }
                if ($mode !== null) {
                    throw new UsageException('The --check and --write options are mutually exclusive.');
                }

                $mode = 'write';
                continue;
            }
            if (str_starts_with($argument, '--project-root=')) {
                if ($projectRoot !== null) {
                    throw new UsageException('The --project-root option may be specified only once.');
                }
                $projectRoot = substr($argument, strlen('--project-root='));
                continue;
            }
            if (str_starts_with($argument, '--php-cs-fixer=')) {
                if ($executable !== null) {
                    throw new UsageException('The --php-cs-fixer option may be specified only once.');
                }
                $executable = substr($argument, strlen('--php-cs-fixer='));
                continue;
            }
            if (str_starts_with($argument, '--config=')) {
                if ($configSpecified) {
                    throw new UsageException('The --config option may be specified only once.');
                }
                $config = substr($argument, strlen('--config='));
                $configSpecified = true;
                continue;
            }
            if (str_starts_with($argument, '--')) {
                throw new UsageException(sprintf('Unknown format option: %s.', $argument));
            }

            $files[] = $argument;
        }

        if ($mode === null) {
            throw new UsageException('The format command requires exactly one of --check or --write.');
        }
        if ($files === []) {
            throw new UsageException('The format command requires at least one Markdown or PHP file.');
        }

        $configuration = PhpCsFixerConfiguration::forProject(
            new ProjectRoot($this->absolutePath($projectRoot ?? '.', 'Project root')),
            new ProjectPath($executable ?? 'vendor/bin/php-cs-fixer'),
            $config === null ? null : new ProjectPath($config),
        );
        $source = DocumentationSource::forProject($configuration->projectRoot);
        $canonicalProjectRoot = $configuration->projectRoot->value;
        $selectedIncludes = [];

        foreach ($files as $file) {
            if (!str_ends_with($file, '.md') && !str_ends_with($file, '.php')) {
                throw new \InvalidArgumentException(sprintf(
                    'Formatting file must use the case-sensitive .md or .php extension: %s.',
                    $file,
                ));
            }

            $absoluteFile = $this->absolutePath($file, 'Formatting file');
            if ($mode === 'write') {
                $cursor = str_replace('\\', '/', $absoluteFile);
                while (true) {
                    if (is_link($cursor)) {
                        throw new SynchronizationWriteException(sprintf(
                            'Formatting write paths must not use symbolic links: %s.',
                            $file,
                        ));
                    }

                    $canonicalCursor = realpath($cursor);
                    if (
                        $canonicalCursor !== false
                        && (DIRECTORY_SEPARATOR === '\\'
                            ? strcasecmp(str_replace('\\', '/', $canonicalCursor), $canonicalProjectRoot) === 0
                            : str_replace('\\', '/', $canonicalCursor) === $canonicalProjectRoot)
                    ) {
                        break;
                    }

                    $parent = str_replace('\\', '/', dirname($cursor));
                    if ($parent === $cursor) {
                        break;
                    }
                    $cursor = $parent;
                }
            }

            $projectPath = ProjectDocumentLoader::projectPath(
                $configuration->projectRoot,
                new \SplFileInfo($absoluteFile),
                'documentation',
            );
            $source = $source->includeFile($projectPath);
            $selectedIncludes[] = new IncludeRule(IncludeKind::File, $projectPath);
        }

        $checker = new FormattingChecker($configuration);
        $documentLoader = new ProjectDocumentLoader('documentation', ['.md', '.php']);
        /** @var array<string, Document> $selectedDocuments */
        $selectedDocuments = [];
        if ($mode === 'write') {
            foreach ($documentLoader->load($configuration->projectRoot, $selectedIncludes, []) as $document) {
                $selectedDocuments[$document->path->value] = $document;
            }
        }

        $mismatches = $checker->check($source->load());
        if ($mode === 'write') {
            if ($mismatches === []) {
                return ExitCode::Success;
            }

            /** @var array<string, array{document: Document, mismatches: list<FormattingMismatch>}> $grouped */
            $grouped = [];
            foreach ($mismatches as $mismatch) {
                $document = $mismatch->example->codeOrigin()->document;
                $grouped[$document->path->value] ??= ['document' => $document, 'mismatches' => []];
                $grouped[$document->path->value]['mismatches'][] = $mismatch;
            }

            $rewriter = new FormattingRewriter();
            $rewritten = [];
            foreach ($grouped as $path => $group) {
                $rewritten[$path] = $rewriter->rewrite($group['document'], ...$group['mismatches']);
            }

            // Re-run every formatter before the first maintained file changes, then require an identical proposal.
            $validatedMismatches = $checker->check($source->load());
            $validatedDocuments = [];
            foreach ($documentLoader->load($configuration->projectRoot, $selectedIncludes, []) as $document) {
                $validatedDocuments[$document->path->value] = $document;
            }
            if (array_keys($validatedDocuments) !== array_keys($selectedDocuments)) {
                throw new FormattingRewriteException(
                    'Formatting inputs changed during validation; refusing to write any files.',
                );
            }
            foreach ($validatedDocuments as $path => $document) {
                if ($document->contents !== $selectedDocuments[$path]->contents) {
                    throw new FormattingRewriteException(sprintf(
                        'Formatting input changed during validation; refusing to write any files: %s.',
                        $path,
                    ));
                }
            }

            /** @var array<string, array{document: Document, mismatches: list<FormattingMismatch>}> $validatedGrouped */
            $validatedGrouped = [];
            foreach ($validatedMismatches as $mismatch) {
                $document = $mismatch->example->codeOrigin()->document;
                $validatedGrouped[$document->path->value] ??= ['document' => $document, 'mismatches' => []];
                $validatedGrouped[$document->path->value]['mismatches'][] = $mismatch;
            }

            if (array_keys($validatedGrouped) !== array_keys($grouped)) {
                throw new FormattingRewriteException(
                    'Formatting inputs or formatter results changed during validation; refusing to write any files.',
                );
            }
            foreach ($validatedGrouped as $path => $group) {
                $replacement = $rewriter->rewrite($group['document'], ...$group['mismatches']);
                if (
                    $group['document']->contents !== $grouped[$path]['document']->contents
                    || $replacement->contents !== $rewritten[$path]->contents
                ) {
                    throw new FormattingRewriteException(sprintf(
                        'Formatting input or formatter result changed during validation; refusing to write any files: %s.',
                        $path,
                    ));
                }
            }

            $writer = SynchronizationWriter::forProject($configuration->projectRoot);
            foreach ($grouped as $path => $group) {
                if ($group['document']->contents === $rewritten[$path]->contents) {
                    continue;
                }

                $writer->write($group['document'], $rewritten[$path]);
                $output(sprintf("Updated %s.\n", $path));
            }

            return ExitCode::Success;
        }

        foreach ($mismatches as $mismatch) {
            $output($this->mismatchMessage($mismatch));
        }

        if ($mismatches === []) {
            return ExitCode::Success;
        }

        $output(sprintf(
            "%d inline example%s %s formatting.\n",
            count($mismatches),
            count($mismatches) === 1 ? '' : 's',
            count($mismatches) === 1 ? 'requires' : 'require',
        ));

        return ExitCode::CommandFailure;
    }

    /**
     * @logion [OSD 100:36] Count no victory before the wounded have slept; triumph must wait outside their rest.
     */
    private function absolutePath(string $path, string $name): string
    {
        if (trim($path) === '') {
            throw new \InvalidArgumentException(sprintf('%s path must not be empty.', $name));
        }

        $path = str_replace('\\', '/', $path);
        if (str_starts_with($path, '/') || preg_match('/\A[a-zA-Z]:\//', $path) === 1) {
            return $path;
        }

        $workingDirectory = getcwd();
        if ($workingDirectory === false) {
            throw new \RuntimeException('Unable to determine the current working directory.');
        }

        return $workingDirectory . '/' . $path;
    }

    /**
     * @return non-empty-string
     *
     * @logion [RAS 100:37] I saw a black horse grazing upon the aurora, and each mouthful revealed one hidden star.
     *     The animal bore no bridle and departed before the heavens were bare; thereafter the constellations kept one
     *     dark pasture among them, honoring the appetite that had uncovered their number without consuming their
     *     order.
     */
    private function mismatchMessage(FormattingMismatch $mismatch): string
    {
        $origin = $mismatch->example->codeOrigin();
        $description = sprintf(
            "%s:%d: inline example differs from PHP-CS-Fixer output.\n",
            $origin->document->path->value,
            $origin->firstCodeLine,
        );
        $header = sprintf(
            "--- %s:%d (authored)\n+++ %s:%d (formatted)\n",
            $origin->document->path->value,
            $origin->firstCodeLine,
            $origin->document->path->value,
            $origin->firstCodeLine,
        );

        return $description . UnifiedDiffRenderer::render(
            $header,
            $mismatch->example->code->source,
            $mismatch->formattedCode->source,
        );
    }
}
