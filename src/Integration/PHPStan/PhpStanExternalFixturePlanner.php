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

namespace jbboehr\Akashi\Integration\PHPStan;

use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Integration\PHPStan\Exception\ExpectationParseException;
use jbboehr\Akashi\Integration\PHPStan\Exception\NoRelevantExamplesException;
use jbboehr\Akashi\Integration\PHPStan\Exception\PhpStanConfigurationException;
use jbboehr\Akashi\Model\ReferencedExampleSource;

/**
 * Project selected canonical external examples into one direct PHPStan analysis plan.
 *
 * @logion [RAS 106:6] Beside the crimson planet, a cathedral of clear ice unfolded from a single point, revealing
 *     chapels too vast for any mortal pilgrimage. Each altar held a flame of another color, yet all leaned toward one
 *     unseen center. The celestial wardens permitted the made sun to enter, and its light passed through every chapel
 *     without claiming the fire. Thereafter the planet bore a many-colored dawn and one indivisible shadow.
 */
final class PhpStanExternalFixturePlanner
{
    /**
     * The corpus and configuration must describe the same canonical project root.
     *
     * @throws ExpectationParseException when a selected example contains an empty diagnostic expectation
     * @throws NoRelevantExamplesException when the configuration selects no examples
     * @throws PhpStanConfigurationException when selected examples are inline or their canonical file snapshot no
     *     longer matches the configured project
     *
     * @logion [SFA 106:7] The ivory doorframe stood alone upon the shore, admitting sea and refusing ships. A threshold
     *     keepeth its law even where every wall hath surrendered.
     */
    public function plan(
        ExampleCorpus $corpus,
        PhpStanExampleConfiguration $configuration,
    ): PhpStanExternalFixturePlan {
        $selected = (new PhpStanExampleSelector())->select($corpus, $configuration);

        /**
         * @var array<non-empty-string, array{
         *     relativePath: non-empty-string,
         *     absolutePath: non-empty-string,
         *     expectations: list<DiagnosticExpectation>
         * }> $files
         */
        $files = [];
        foreach ($selected as $example) {
            if (!$example->source instanceof ReferencedExampleSource) {
                throw new PhpStanConfigurationException(sprintf(
                    'External PHPStan fixture selection includes inline example %s at %s:%d.',
                    $example->corpusId->value,
                    $example->codeOrigin()->document->path->value,
                    $example->codeOrigin()->firstCodeLine,
                ));
            }

            $document = $example->codeOrigin()->document;
            $relativePath = $document->path->value;
            $absolutePath = rtrim($configuration->projectRoot->value, '/') . '/' . $relativePath;
            clearstatcache(true, $absolutePath);
            $nativeCanonicalPath = realpath($absolutePath);
            if (
                $nativeCanonicalPath === false
                || !is_file($nativeCanonicalPath)
                || !is_readable($nativeCanonicalPath)
            ) {
                throw new PhpStanConfigurationException(sprintf(
                    'External PHPStan fixture file %s is unavailable under configured project %s (probed %s).',
                    $relativePath,
                    $configuration->projectRoot->value,
                    $absolutePath,
                ));
            }
            $canonicalPath = str_replace('\\', '/', $nativeCanonicalPath);
            if ($canonicalPath !== $absolutePath) {
                throw new PhpStanConfigurationException(sprintf(
                    'External PHPStan fixture file %s no longer resolves to its canonical project path under configured '
                    . 'project %s (probed %s; resolved %s).',
                    $relativePath,
                    $configuration->projectRoot->value,
                    $absolutePath,
                    $canonicalPath,
                ));
            }

            $contents = @file_get_contents($nativeCanonicalPath);
            if ($contents === false) {
                throw new PhpStanConfigurationException(sprintf(
                    'Unable to read external PHPStan fixture file %s under configured project %s (probed %s).',
                    $relativePath,
                    $configuration->projectRoot->value,
                    $absolutePath,
                ));
            }
            if ($contents !== $document->contents) {
                throw new PhpStanConfigurationException(sprintf(
                    'External PHPStan fixture file %s changed after its documentation corpus was loaded under '
                    . 'configured project %s (probed %s).',
                    $relativePath,
                    $configuration->projectRoot->value,
                    $absolutePath,
                ));
            }

            $metadata = @stat($nativeCanonicalPath);
            if ($metadata === false) {
                throw new PhpStanConfigurationException(sprintf(
                    'Unable to identify external PHPStan fixture file %s under configured project %s (probed %s).',
                    $relativePath,
                    $configuration->projectRoot->value,
                    $absolutePath,
                ));
            }
            $physicalIdentity = $metadata['ino'] === 0
                ? 'path:' . $canonicalPath
                : sprintf('inode:%d:%d', $metadata['dev'], $metadata['ino']);

            $file = $files[$physicalIdentity] ?? [
                'relativePath' => $relativePath,
                'absolutePath' => $nativeCanonicalPath,
                'expectations' => [],
            ];
            if (strcmp($relativePath, $file['relativePath']) < 0) {
                $file['relativePath'] = $relativePath;
                $file['absolutePath'] = $nativeCanonicalPath;
            }

            foreach ((new ExpectationParser())->parse($example) as $expectation) {
                $duplicate = false;
                foreach ($file['expectations'] as $existingExpectation) {
                    if (
                        $existingExpectation->sourceLine === $expectation->sourceLine
                        && $existingExpectation->text === $expectation->text
                        && $existingExpectation->identifier === $expectation->identifier
                        && $existingExpectation->sourceLineRange === $expectation->sourceLineRange
                    ) {
                        $duplicate = true;
                        break;
                    }
                }
                if (!$duplicate) {
                    $file['expectations'][] = $expectation;
                }
            }
            $files[$physicalIdentity] = $file;
        }

        $analysisPaths = [];
        $expectationsByFile = [];
        foreach ($files as $file) {
            $analysisPaths[] = $file['relativePath'];
            $expectationsByFile[$file['absolutePath']] = $file['expectations'];
        }

        return new PhpStanExternalFixturePlan(
            $configuration->projectRoot,
            $analysisPaths,
            $expectationsByFile,
        );
    }
}
