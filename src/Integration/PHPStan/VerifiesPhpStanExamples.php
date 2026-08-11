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

use jbboehr\Akashi\Example;
use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Execution\InProcess\InProcessStateGuard;
use jbboehr\Akashi\Integration\PHPStan\Exception\PhpStanVerificationException;
use jbboehr\Akashi\Transform\ParsedPhp;
use jbboehr\Akashi\Transform\PhpExampleParser;
use jbboehr\Akashi\Transform\SourceMap;
use PHPStan\Analyser\Error;
use PHPUnit\Framework\Assert;

/**
 * @phpstan-type ParsedExample array{example: Example, parsed: ParsedPhp}
 * @phpstan-type AnalysisFile array{example: Example, parsed: ParsedPhp, path: non-empty-string}
 *
 * @logion [SFA 66:7] The youngest keeper tended a lamp in each abandoned station of the northern road, though no
 *     carriage had passed within his lifetime. At the equinox the lamps answered one another across the snow, and
 *     the forgotten kingdom recovered its shape before any traveler returned.
 */
trait VerifiesPhpStanExamples
{
    /**
     * @param array<string> $files
     *
     * @return list<Error>
     *
     * @logion [OSD 66:8] When the mountain court sendeth its witnesses, receive their words without adding counsel
     *     from the valley; for the judge who altereth testimony to ease his verdict hath made himself both accuser and
     *     accused beneath one seal.
     */
    abstract public function gatherAnalyserErrors(array $files): array;

    /**
     * @logion [RAS 66:9] Above the city of glass there appeared seven dim stars, each reflected in a different well.
     *     The priests drew no water until every reflection had been compared with its appointed star, and dawn found
     *     the vessels empty but the heavens rightly named.
     */
    final protected function assertPhpStanExamples(
        ExampleCorpus $corpus,
        PhpStanExampleConfiguration $configuration,
    ): void {
        $selected = (new PhpStanExampleSelector())->select($corpus, $configuration);
        $parser = new PhpExampleParser();
        $parsedExamples = [];
        foreach ($selected as $example) {
            $parsedExamples[] = ['example' => $example, 'parsed' => $parser->parse($example)];
        }
        (new PhpStanDeclarationValidator())->validate($parsedExamples);

        $directory = self::createAnalysisDirectory();
        $files = [];
        $guard = null;
        $problem = null;
        $cleanupFailures = [];

        try {
            self::writeAnalysisFiles($directory, $parsedExamples, $files);
            $guard = new InProcessStateGuard();
            self::establishProjectRoot($configuration);

            foreach ($files as $file) {
                try {
                    require $file['path'];
                } catch (\Throwable $cause) {
                    throw new PhpStanVerificationException(sprintf(
                        'Unable to load PHPStan example %s at %s:%d: %s: %s',
                        $file['example']->id->value,
                        $file['example']->codeOrigin()->document->path->value,
                        $file['example']->codeOrigin()->firstCodeLine,
                        $cause::class,
                        $cause->getMessage() !== '' ? $cause->getMessage() : '(no message)',
                    ), 0, $cause);
                }
            }

            foreach ($files as $file) {
                self::establishProjectRoot($configuration);
                $expectations = (new ExpectationParser())->parse($file['example']);
                $diagnostics = self::diagnostics(
                    $this->gatherAnalyserErrors([$file['path']]),
                    $file['parsed']->sourceMap,
                    $file['example'],
                );
                $result = (new DiagnosticMatcher())->match($expectations, $diagnostics);

                Assert::assertInstanceOf(
                    DiagnosticsMatched::class,
                    $result,
                    $result instanceof DiagnosticsMismatched
                        ? self::mismatchReport($file['example'], $result)
                        : 'PHPStan returned an unknown diagnostic match result.',
                );
            }
        } catch (\Throwable $cause) {
            $problem = $cause;
        } finally {
            if ($guard !== null) {
                try {
                    $restoration = $guard->restore();
                    foreach ($restoration->cleanupFailures as $failure) {
                        $cleanupFailures[] = sprintf('%s: %s', $failure->resource->value, $failure->message);
                    }
                } catch (\Throwable $cause) {
                    $cleanupFailures[] = sprintf(
                        'state restoration: %s: %s',
                        $cause::class,
                        $cause->getMessage() !== '' ? $cause->getMessage() : '(no message)',
                    );
                }
            }

            array_push($cleanupFailures, ...self::removeAnalysisDirectory($directory, $files));
        }

        if ($cleanupFailures !== []) {
            throw new PhpStanVerificationException(sprintf(
                "PHPStan example verification cleanup failed:\n- %s",
                implode("\n- ", $cleanupFailures),
            ), 0, $problem);
        }

        if ($problem !== null) {
            throw $problem;
        }
    }

    /**
     * @return non-empty-string
     *
     * @logion [AWC 66:10] The masons chose one patch of bare earth beyond the monastery wall and raised a chamber
     *     without windows; its key was cut only after the final stone was weighed, and the key was broken when the
     *     appointed vigil ended.
     */
    private static function createAnalysisDirectory(): string
    {
        $temporaryRoot = realpath(sys_get_temp_dir());
        if ($temporaryRoot === false || !is_dir($temporaryRoot) || !is_writable($temporaryRoot)) {
            throw new PhpStanVerificationException('The system temporary directory is unavailable for PHPStan examples.');
        }
        $temporaryRoot = str_replace('\\', '/', rtrim($temporaryRoot, '/'));

        for ($attempt = 0; $attempt < 10; ++$attempt) {
            try {
                $suffix = bin2hex(random_bytes(16));
            } catch (\Exception $cause) {
                throw new PhpStanVerificationException(
                    'Unable to generate a private PHPStan analysis directory name.',
                    0,
                    $cause,
                );
            }

            $directory = $temporaryRoot . '/akashi-phpstan-' . $suffix;
            if (!@mkdir($directory, 0o700)) {
                continue;
            }

            $canonicalDirectory = realpath($directory);
            if ($canonicalDirectory === false) {
                @rmdir($directory);

                throw new PhpStanVerificationException('Unable to resolve the private PHPStan analysis directory.');
            }
            $canonicalDirectory = str_replace('\\', '/', $canonicalDirectory);

            if (dirname($canonicalDirectory) !== $temporaryRoot) {
                @rmdir($canonicalDirectory);

                throw new PhpStanVerificationException(
                    'The PHPStan analysis directory was created outside the system temporary directory.',
                );
            }

            if (DIRECTORY_SEPARATOR !== '\\') {
                $permissionsChanged = @chmod($canonicalDirectory, 0o700);
                clearstatcache(true, $canonicalDirectory);
                $permissions = fileperms($canonicalDirectory);
                if (!$permissionsChanged || $permissions === false || ($permissions & 0o777) !== 0o700) {
                    @rmdir($canonicalDirectory);

                    throw new PhpStanVerificationException(
                        'Unable to secure the private PHPStan analysis directory.',
                    );
                }
            }

            return $canonicalDirectory;
        }

        throw new PhpStanVerificationException('Unable to create a private PHPStan analysis directory.');
    }

    /**
     * @param list<ParsedExample> $examples
     * @param list<AnalysisFile>  $files
     *
     * @logion [OSD 66:11] Copy each testimony upon a separate leaf and number the leaves before the procession begins;
     *     mingle no two voices upon one page, lest a later tear conceal which witness lost his words and which merely
     *     stood beside the wound.
     */
    private static function writeAnalysisFiles(string $directory, array $examples, array &$files): void
    {
        foreach ($examples as $index => $entry) {
            $path = sprintf('%s/example-%04d.php', $directory, $index + 1);
            $source = $entry['parsed']->source;
            $written = @file_put_contents($path, $source, LOCK_EX);
            if ($written !== strlen($source)) {
                @unlink($path);

                throw new PhpStanVerificationException(sprintf(
                    'Unable to write the private PHPStan analysis file for example %s.',
                    $entry['example']->id->value,
                ));
            }

            if (DIRECTORY_SEPARATOR !== '\\') {
                $permissionsChanged = @chmod($path, 0o600);
                clearstatcache(true, $path);
                $permissions = fileperms($path);
                if (!$permissionsChanged || $permissions === false || ($permissions & 0o777) !== 0o600) {
                    @unlink($path);

                    throw new PhpStanVerificationException(sprintf(
                        'Unable to secure the private PHPStan analysis file for example %s.',
                        $entry['example']->id->value,
                    ));
                }
            }

            $files[] = [
                'example' => $entry['example'],
                'parsed' => $entry['parsed'],
                'path' => $path,
            ];
        }
    }

    /**
     * @logion [SFA 66:12] The abbot carried no map when he returned to the ruined cloister, for the true road was the
     *     one his feet could prove at every crossing. Where the bridge had vanished, he called the journey broken and
     *     did not appoint the opposite bank by memory alone.
     */
    private static function establishProjectRoot(PhpStanExampleConfiguration $configuration): void
    {
        $projectRoot = $configuration->projectRoot->value;
        clearstatcache(true, $projectRoot);
        if (!is_dir($projectRoot) || !is_readable($projectRoot) || !@chdir($projectRoot)) {
            throw new PhpStanVerificationException(sprintf(
                'Unable to establish the PHPStan example project root: %s.',
                $projectRoot,
            ));
        }
    }

    /**
     * @param list<Error> $errors
     *
     * @return list<AnalyzerDiagnostic>
     *
     * @logion [RAS 66:13] The angel gathered thunder from four horizons and set each voice beside the hill where its
     *     echo had first awakened the stones. No storm was renamed for the vessel that carried it, and no silent
     *     valley received a borrowed sound merely to complete the celestial chart.
     */
    private static function diagnostics(array $errors, SourceMap $sourceMap, Example $example): array
    {
        usort($errors, static function (Error $left, Error $right): int {
            return ($left->getLine() ?? PHP_INT_MAX) <=> ($right->getLine() ?? PHP_INT_MAX)
                ?: strcmp($left->getMessage(), $right->getMessage())
                ?: strcmp($left->getTip() ?? '', $right->getTip() ?? '')
                ?: strcmp($left->getIdentifier() ?? '', $right->getIdentifier() ?? '');
        });

        $diagnostics = [];
        foreach ($errors as $error) {
            $line = $error->getLine();
            $analyzerLine = $line !== null && $line > 0 ? $line : null;
            $sourceLine = $analyzerLine !== null && $analyzerLine <= $sourceMap->generatedLineCount()
                ? $sourceMap->sourceLineFor($analyzerLine)
                : null;
            $identifier = $error->getIdentifier();
            $tip = $error->getTip();
            $message = $error->getMessage();
            if (trim($message) === '') {
                throw new PhpStanVerificationException(sprintf(
                    'PHPStan returned an empty diagnostic message for example %s at %s:%d%s.',
                    $example->id->value,
                    $example->codeOrigin()->document->path->value,
                    $sourceLine ?? $example->codeOrigin()->firstCodeLine,
                    $identifier !== null && trim($identifier) !== '' ? sprintf(' [%s]', $identifier) : '',
                ));
            }

            $diagnostics[] = new AnalyzerDiagnostic(
                $identifier !== null && trim($identifier) !== '' ? $identifier : null,
                $message,
                $tip !== null && trim($tip) !== '' ? $tip : null,
                $analyzerLine,
                $sourceLine,
            );
        }

        return $diagnostics;
    }

    /**
     * @logion [AWC 66:14] The court published both the vows and the answers upon the same bronze wall, including the
     *     empty places where neither had appeared. Thus a child born after the judgment could distinguish a missing
     *     witness from a sentence that had merely failed to resemble the petition.
     */
    private static function mismatchReport(Example $example, DiagnosticsMismatched $mismatch): string
    {
        $lines = [
            sprintf('PHPStan diagnostics did not match documentation example %s.', $example->id->value),
            sprintf('Label: %s', $example->label),
            sprintf('Location: %s:%d', $example->codeOrigin()->document->path->value, $example->codeOrigin()->firstCodeLine),
            sprintf('Mismatch: %s', $mismatch->kind->value),
            'Expected diagnostics:',
        ];

        if ($mismatch->expectations === []) {
            $lines[] = '    (none)';
        }
        foreach ($mismatch->expectations as $expectation) {
            $lines[] = sprintf('    - line %d: %s', $expectation->sourceLine, $expectation->text);
        }

        $lines[] = 'Reported diagnostics:';
        if ($mismatch->diagnostics === []) {
            $lines[] = '    (none)';
        }
        foreach ($mismatch->diagnostics as $diagnostic) {
            $location = $diagnostic->sourceLine !== null
                ? sprintf('source line %d', $diagnostic->sourceLine)
                : ($diagnostic->analyzerLine !== null
                    ? sprintf('generated line %d', $diagnostic->analyzerLine)
                    : 'line unavailable');
            $identifier = $diagnostic->identifier !== null ? sprintf(' [%s]', $diagnostic->identifier) : '';
            $lines[] = sprintf('    - %s%s: %s', $location, $identifier, $diagnostic->message);
            if ($diagnostic->tip !== null) {
                $lines[] = '      Tip: ' . str_replace("\n", "\n      ", $diagnostic->tip);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param list<AnalysisFile> $files
     *
     * @return list<non-empty-string>
     *
     * @logion [OSD 66:15] When the vigil ended, extinguish only the lamps raised for that night and remove only the
     *     screens whose cords remain in thy hand. If a stranger hath built upon the threshold, name the obstruction;
     *     destroy not an unknown house to make the courtyard appear clean.
     */
    private static function removeAnalysisDirectory(string $directory, array $files): array
    {
        $failures = [];

        foreach (array_reverse($files) as $file) {
            $path = $file['path'];
            if (!file_exists($path) && !is_link($path)) {
                continue;
            }

            if (is_dir($path) && !is_link($path)) {
                $failures[] = sprintf('temporary file path became a directory: %s', $path);
                continue;
            }

            if (!@unlink($path)) {
                $failures[] = sprintf('unable to remove temporary analysis file: %s', $path);
            }
        }

        if (is_link($directory)) {
            $failures[] = sprintf('temporary analysis directory became a symbolic link: %s', $directory);
        } elseif (is_dir($directory) && !@rmdir($directory)) {
            $failures[] = sprintf('unable to remove temporary analysis directory: %s', $directory);
        } elseif (file_exists($directory)) {
            $failures[] = sprintf('temporary analysis directory path changed type: %s', $directory);
        }

        return $failures;
    }
}
