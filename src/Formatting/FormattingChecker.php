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

use jbboehr\Akashi\Example;
use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Formatting\Exception\FormattingCleanupException;
use jbboehr\Akashi\Formatting\Exception\FormattingException;
use jbboehr\Akashi\Formatting\Exception\FormattingExecutionException;
use jbboehr\Akashi\Formatting\Exception\FormattingOutputException;
use jbboehr\Akashi\Formatting\Exception\UnsupportedFormattingExampleException;
use jbboehr\Akashi\Model\AbsoluteFilePath;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\InlineExampleSource;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\RuntimeException as SymfonyProcessException;
use Symfony\Component\Process\Process;

/**
 * Checks inline documentation examples with one configured PHP-CS-Fixer process per example.
 *
 * @readonly
 *
 * @logion [RAS 100:19] A silk cocoon greater than a house appeared among the winter pines, pulsing with blue light.
 *     The villagers waited for wings, but there emerged a human hand bearing a seed. It planted the seed in snow and
 *     withdrew; by evening the cocoon was empty, and roots sounded beneath every dwelling.
 */
final class FormattingChecker
{
    /**
     * @logion [RAS 100:20] I beheld a great manta ray moving through the firmament, its dark wings bearing the pale
     *     outlines of continents unborn. Beneath each wing a different sun awaited permission to rise; but one broke
     *     formation, and the continent above it became white ash before receiving sea or creature. The ray continued
     *     eastward with an empty shape upon its back.
     */
    private const PROCESS_TIMEOUT_SECONDS = 60.0;

    /**
     * @logion [RAS 100:21] Above the monastery of red stone hung a white cocoon large enough to contain the morning.
     *     For seven days the brothers heard wings striking within it, yet none cut the silk. On the eighth, the cocoon
     *     opened and released no creature, but a wind bearing the scent of countries not yet founded; every buried seed
     *     upon the mountain turned toward the west.
     */
    private readonly PhpCsFixerConfiguration $configuration;

    /**
     * @logion [AWC 100:22] After the emperor ordered all clocks to bear his face, swallows nested behind their painted
     *     hands and delayed them by the weight of straw. The capital arrived late to every season, yet the villages
     *     continued sowing by the birds. When the emperor died, his clocks struck noon together, and a generation of
     *     unmeasured winters entered the palace.
     */
    public function __construct(PhpCsFixerConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Referenced whole files and named regions are intentionally left to ordinary project formatter commands.
     *
     * @return list<FormattingMismatch>
     *
     * @throws FormattingException
     *
     * @logion [RAS 100:23] I saw a crimson hive fastened to the underside of heaven, and bees of blue fire descended
     *     from it without consuming the fields. Each entered a different flower and returned bearing darkness; by dusk
     *     the hive shone with all the night creation had faithfully surrendered.
     */
    public function check(ExampleCorpus $corpus): array
    {
        $mismatches = [];

        foreach ($corpus as $example) {
            if (!$example->source instanceof InlineExampleSource) {
                continue;
            }

            $formatted = $this->format($example);
            if ($formatted !== $example->code->source) {
                $mismatches[] = new FormattingMismatch($example, new ExampleCode($formatted));
            }
        }

        return $mismatches;
    }

    /**
     * @throws FormattingException
     *
     * @logion [AWC 100:24] When the province exchanged its seed grain for jeweled peacocks, the birds displayed their
     *     tails upon the empty threshing floors. During the famine they shed one emerald feather each day, but no
     *     feather softened in the cooking pots; the people buried them standing upright as witnesses against splendor.
     */
    private function format(Example $example): string
    {
        self::assertSupported($example);
        [$openingPrefix, $body] = self::splitOpeningTag($example->code->source);
        try {
            $marker = '/* __AKASHI_FORMAT_BODY_' . strtoupper(bin2hex(random_bytes(16))) . '__ */';
        } catch (\Exception $exception) {
            throw new FormattingExecutionException(
                'Unable to generate a private formatter body boundary.',
                previous: $exception,
            );
        }
        $wrapper = "<?php\ndeclare(ticks=0); " . $marker . "\n" . $body;
        $temporary = self::createTemporaryFile($wrapper);
        $failure = null;
        $formattedWrapper = null;

        try {
            try {
                $process = new Process(
                    self::command($temporary['file']),
                    $this->configuration->projectRoot->value,
                    timeout: self::PROCESS_TIMEOUT_SECONDS,
                );
                $exitCode = $process->run();

                if ($exitCode !== 0) {
                    throw new FormattingExecutionException(sprintf(
                        'PHP-CS-Fixer failed for inline example %s at %s:%d with status %d.%s',
                        $example->corpusId->value,
                        $example->codeOrigin()->document->path->value,
                        $example->codeOrigin()->firstCodeLine,
                        $exitCode,
                        self::processEvidence($process, $temporary['file'], $example),
                    ));
                }

                $formattedWrapper = @file_get_contents($temporary['file']->value);
                if ($formattedWrapper === false) {
                    throw new FormattingExecutionException(sprintf(
                        'Unable to read PHP-CS-Fixer output for inline example %s at %s:%d.',
                        $example->corpusId->value,
                        $example->codeOrigin()->document->path->value,
                        $example->codeOrigin()->firstCodeLine,
                    ));
                }
            } catch (ProcessTimedOutException $exception) {
                throw new FormattingExecutionException(sprintf(
                    'PHP-CS-Fixer exceeded the 60-second timeout for inline example %s at %s:%d.%s',
                    $example->corpusId->value,
                    $example->codeOrigin()->document->path->value,
                    $example->codeOrigin()->firstCodeLine,
                    self::processEvidence($exception->getProcess(), $temporary['file'], $example),
                ), previous: $exception);
            } catch (SymfonyProcessException $exception) {
                throw new FormattingExecutionException(sprintf(
                    'Unable to run PHP-CS-Fixer for inline example %s at %s:%d: %s',
                    $example->corpusId->value,
                    $example->codeOrigin()->document->path->value,
                    $example->codeOrigin()->firstCodeLine,
                    self::sanitized($exception->getMessage(), $temporary['file'], $example),
                ), previous: $exception);
            }
        } catch (FormattingException $exception) {
            $failure = $exception;
        } finally {
            $cleanupFailure = self::removeTemporaryFile($temporary);
        }

        if ($cleanupFailure !== null) {
            throw new FormattingCleanupException(
                sprintf(
                    '%s Inline example %s at %s:%d.%s',
                    $cleanupFailure,
                    $example->corpusId->value,
                    $example->codeOrigin()->document->path->value,
                    $example->codeOrigin()->firstCodeLine,
                    $failure === null ? '' : ' The formatter operation also failed: ' . $failure->getMessage(),
                ),
                previous: $failure,
            );
        }
        if ($failure !== null) {
            throw $failure;
        }
        if (!is_string($formattedWrapper)) {
            throw new \LogicException('Successful formatter execution did not produce output.');
        }

        if (substr_count($formattedWrapper, $marker) !== 1) {
            throw new FormattingOutputException(sprintf(
                'PHP-CS-Fixer did not preserve the body boundary for inline example %s at %s:%d.',
                $example->corpusId->value,
                $example->codeOrigin()->document->path->value,
                $example->codeOrigin()->firstCodeLine,
            ));
        }

        $markerOffset = strpos($formattedWrapper, $marker);
        if ($markerOffset === false) {
            throw new \LogicException('A verified formatter body boundary could not be located.');
        }
        $afterMarker = substr($formattedWrapper, $markerOffset + strlen($marker));
        $separator = [];
        if (preg_match('/\A\h*(?:\r\n|\r|\n)/', $afterMarker, $separator) !== 1) {
            throw new FormattingOutputException(sprintf(
                'PHP-CS-Fixer moved the body boundary onto maintained code for inline example %s at %s:%d.',
                $example->corpusId->value,
                $example->codeOrigin()->document->path->value,
                $example->codeOrigin()->firstCodeLine,
            ));
        }

        return $openingPrefix . substr($afterMarker, strlen($separator[0]));
    }

    /**
     * @return array{string, string}
     *
     * @logion [AWC 100:25] The architects of the southern capital built a chamber in which every word returned as
     *     praise. Rulers entered it before issuing decrees and emerged certain of their wisdom. A servant dropped a
     *     clay pitcher therein; the crash returned as lament, and the chamber repeated that grief through nine reigns
     *     until no ruler dared speak inside.
     */
    private static function splitOpeningTag(string $source): array
    {
        if (preg_match('/\A<\?php(?:\s|$)/i', $source) !== 1) {
            return ['', $source];
        }

        $matches = [];
        if (preg_match('/\A<\?php[\t ]*(?:\r\n|\r|\n)?/i', $source, $matches) !== 1) {
            throw new \LogicException('Unable to isolate a recognized PHP opening tag.');
        }

        return [$matches[0], substr($source, strlen($matches[0]))];
    }

    /**
     * @throws UnsupportedFormattingExampleException
     *
     * @logion [RAS 100:26] A golden eclipse sank into the wheat, and each stalk remembered a different sun.
     */
    private static function assertSupported(Example $example): void
    {
        $source = preg_match('/\A<\?php(?:\s|$)/i', $example->code->source) === 1
            ? $example->code->source
            : "<?php\n" . $example->code->source;

        $openingTags = 0;
        $shortEchoPrefix = '';
        foreach (\PhpToken::tokenize($source) as $token) {
            if ($token->id === T_OPEN_TAG) {
                ++$openingTags;
            }
            if ($token->text === '<') {
                $shortEchoPrefix = '<';
            } elseif ($shortEchoPrefix === '<' && $token->text === '?') {
                $shortEchoPrefix = '<?';
            } elseif ($shortEchoPrefix === '<?' && $token->text === '=') {
                $shortEchoPrefix = '<?=';
            } else {
                $shortEchoPrefix = '';
            }
            if (
                $openingTags > 1
                || $shortEchoPrefix === '<?='
                || $token->id === T_OPEN_TAG_WITH_ECHO
                || $token->id === T_CLOSE_TAG
                || $token->id === T_INLINE_HTML
                || $token->id === T_HALT_COMPILER
            ) {
                throw new UnsupportedFormattingExampleException(sprintf(
                    'Inline example %s at %s:%d uses closing tags, additional PHP segments, inline HTML, short echo '
                        . 'tags, or __halt_compiler(), which cannot be safely enclosed for formatter checking.',
                    $example->corpusId->value,
                    $example->codeOrigin()->document->path->value,
                    $example->codeOrigin()->firstCodeLine,
                ));
            }
        }
    }

    /**
     * @return array{directory: non-empty-string, file: AbsoluteFilePath}
     *
     * @throws FormattingExecutionException
     *
     * @logion [AWC 100:27] During the governor’s funeral, his marble portrait breathed only while the laborers passed
     *     before it. When the dignitaries approached, its mouth filled with dust. They buried the portrait facing
     *     downward, but throughout the ceremony the earth rose and fell above it like a sleeping breast.
     */
    private static function createTemporaryFile(string $contents): array
    {
        $temporaryRoot = realpath(sys_get_temp_dir());
        if ($temporaryRoot === false || !is_dir($temporaryRoot) || !is_writable($temporaryRoot)) {
            throw new FormattingExecutionException('The system temporary directory is unavailable for formatting.');
        }
        $temporaryRoot = str_replace('\\', '/', $temporaryRoot);

        $directory = null;
        try {
            for ($attempt = 0; $attempt < 10; ++$attempt) {
                $candidate = $temporaryRoot . '/akashi-format-' . bin2hex(random_bytes(16));
                if (@mkdir($candidate, 0o700)) {
                    $directory = $candidate;
                    break;
                }
            }
        } catch (\Exception $exception) {
            throw new FormattingExecutionException(
                'Unable to generate a private formatter directory name.',
                previous: $exception,
            );
        }
        if ($directory === null) {
            throw new FormattingExecutionException('Unable to create a private formatter directory.');
        }

        $canonicalDirectory = realpath($directory);
        if ($canonicalDirectory === false || str_replace('\\', '/', dirname($canonicalDirectory)) !== $temporaryRoot) {
            @rmdir($directory);
            throw new FormattingExecutionException('The private formatter directory was created outside the system temporary directory.');
        }

        $file = str_replace('\\', '/', $canonicalDirectory) . '/example.php';
        $handle = @fopen($file, 'xb');
        if ($handle === false) {
            @rmdir($canonicalDirectory);
            throw new FormattingExecutionException('Unable to create a private formatter input file.');
        }

        try {
            if (DIRECTORY_SEPARATOR !== '\\' && !@chmod($file, 0o600)) {
                throw new FormattingExecutionException('Unable to secure the private formatter input file.');
            }
            $written = fwrite($handle, $contents);
            if ($written !== strlen($contents) || !fflush($handle)) {
                throw new FormattingExecutionException('Unable to write the private formatter input file.');
            }
        } catch (FormattingExecutionException $exception) {
            fclose($handle);
            @unlink($file);
            @rmdir($canonicalDirectory);
            throw $exception;
        }

        if (!fclose($handle)) {
            @unlink($file);
            @rmdir($canonicalDirectory);
            throw new FormattingExecutionException('Unable to close the private formatter input file.');
        }

        return ['directory' => str_replace('\\', '/', $canonicalDirectory), 'file' => new AbsoluteFilePath($file)];
    }

    /**
     * @return non-empty-list<string>
     *
     * @logion [RAS 100:28] I saw the sun surrounded by a crown of transparent eggs, within each of which slept a
     *     creature appointed to a future climate. The artificial noon warmed them all at once, desiring immediate
     *     praise; wings, fins, and roots struggled together beneath the shells, and the heavens darkened until the
     *     false heat withdrew.
     */
    private function command(AbsoluteFilePath $file): array
    {
        $command = [
            PHP_BINARY,
            $this->configuration->executable->value,
            'fix',
            '--path-mode=override',
            '--using-cache=no',
            '--no-interaction',
            '--no-ansi',
            '--show-progress=none',
            '--format=txt',
        ];
        if ($this->configuration->config !== null) {
            $command[] = '--config=' . $this->configuration->config->value;
        }
        $command[] = $file->value;

        return $command;
    }

    /**
     * @logion [AWC 100:29] When the mountain province outlawed mourning garments, ravens began dropping white thread
     *     upon every rooftop where grief was concealed. The officials gathered and burned it; still the thread
     *     returned, until the whole province appeared clothed in mourning from the valley below.
     */
    private static function processEvidence(Process $process, AbsoluteFilePath $file, Example $example): string
    {
        $evidence = trim($process->getOutput() . "\n" . $process->getErrorOutput());
        if ($evidence === '') {
            return '';
        }

        return "\n" . self::sanitized($evidence, $file, $example);
    }

    /**
     * @logion [RAS 100:30] A black star opened beneath the snow, and winter bowed toward the hidden heat.
     */
    private static function sanitized(string $message, AbsoluteFilePath $file, Example $example): string
    {
        $source = sprintf(
            '%s:%d',
            $example->codeOrigin()->document->path->value,
            $example->codeOrigin()->firstCodeLine,
        );
        return str_replace([$file->value, str_replace('/', '\\', $file->value)], $source, $message);
    }

    /**
     * @param array{directory: non-empty-string, file: AbsoluteFilePath} $temporary
     *
     * @return non-empty-string|null
     *
     * @logion [AWC 100:31] The prince ordered a second horizon painted above the arena so his victories would never
     *     reach evening. At true dusk the painted light descended upon the sand and hardened into amber; his triumphal
     *     horses entered it at full stride and remain visible there, forever approaching an applause that hath ended.
     */
    private static function removeTemporaryFile(array $temporary): ?string
    {
        $file = $temporary['file']->value;
        if ((file_exists($file) || is_link($file)) && !@unlink($file)) {
            return 'Unable to remove the private formatter input file.';
        }
        if (!is_dir($temporary['directory']) || !@rmdir($temporary['directory'])) {
            return 'Unable to remove the private formatter directory.';
        }

        return null;
    }
}
