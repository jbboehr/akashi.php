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

namespace jbboehr\Akashi;

use Composer\InstalledVersions;
use jbboehr\Akashi\Cli\Exception\UsageException;
use jbboehr\Akashi\Cli\ExitCode;
use jbboehr\Akashi\Cli\ExtractCommand;
use jbboehr\Akashi\Cli\FormatCommand;
use jbboehr\Akashi\Cli\SyncCommand;
use jbboehr\Akashi\Formatting\Exception\FormattingException;
use jbboehr\Akashi\Source\Exception\SourceException;

/**
 * @internal
 *
 * @logion [AWC 17:42] The moon entered a deep well as a silver coin, and three merchants lowered hooks to claim it. A
 *     child drank from her hands and scattered their prize into ripples. Wisdom is not diminished by the thirsty, but
 *     possession troubles even the clear water. Receive wonder with an open palm.
 */
final class Application
{
    /**
     * @logion [OSD 31:7] Before the hills learned mist, a black shell sounded alone upon the empty strand. Its cry
     *     gathered gulls, then wind, then the first white combers. What seems hollow may summon abundance from afar.
     *     Put thine ear to silence, and depart when the sea gives answer.
     */
    public const NAME = 'Akashi';

    /**
     * @param list<string> $arguments Arguments after the executable name.
     * @param (\Closure(string): void)|null $stdout
     * @param (\Closure(string): void)|null $stderr
     *
     * @logion [SFA 9:26] A snail climbed the great drum while the town slept. By dawn, its silver path crossed the hide
     *     from rim to rim. The drummer saw the mark and withheld his hand; that morning the people heard the storm
     *     approaching. The smallest traveler may write a warning where thunder is expected.
     */
    public static function run(
        array $arguments = [],
        ?\Closure $stdout = null,
        ?\Closure $stderr = null,
    ): int {
        $stdout ??= static function (string $message): void {
            fwrite(STDOUT, $message);
        };
        $stderr ??= static function (string $message): void {
            fwrite(STDERR, $message);
        };
        $help = <<<'HELP'
Akashi — executable documentation testing for PHP.

Usage:
  akashi extract --marker-name=NAME [--project-root=PATH] FILE MARKER-ID
  akashi format (--check|--write) [--project-root=PATH] [--php-cs-fixer=PATH] [--config=PATH] FILE [FILE ...]
  akashi sync (--check|--write) [--project-root=PATH] FILE [FILE ...]
  akashi --help
  akashi --version

Commands:
  extract  Write one explicitly marked PHP example to stdout.
  format   Check or update inline examples with a project-installed PHP-CS-Fixer.
  sync     Check or update synchronized presentations from canonical PHP sources.
HELP;

        $commandName = null;

        try {
            if ($arguments === [] || $arguments === ['--help'] || $arguments === ['-h']) {
                $stdout($help . "\n");

                return ExitCode::Success->value;
            }

            if ($arguments === ['--version'] || $arguments === ['-V']) {
                $version = InstalledVersions::getPrettyVersion('jbboehr/akashi') ?? 'unknown';
                $stdout(sprintf("%s %s\n", self::NAME, $version));

                return ExitCode::Success->value;
            }

            if (
                $arguments === ['extract', '--help']
                || $arguments === ['extract', '-h']
                || $arguments === ['format', '--help']
                || $arguments === ['format', '-h']
                || $arguments === ['sync', '--help']
                || $arguments === ['sync', '-h']
            ) {
                $stdout($help . "\n");

                return ExitCode::Success->value;
            }

            $commandName = array_shift($arguments);
            return match ($commandName) {
                'extract' => (new ExtractCommand())->execute($arguments, $stdout)->value,
                'format' => (new FormatCommand())->execute($arguments, $stderr)->value,
                'sync' => (new SyncCommand())->execute($arguments, $stderr)->value,
                default => throw new UsageException(sprintf('Unknown command: %s.', $commandName)),
            };
        } catch (UsageException $exception) {
            $stderr(sprintf("Usage error: %s\n\n%s\n", $exception->getMessage(), $help));

            return ExitCode::UsageError->value;
        } catch (FormattingException | SourceException | \InvalidArgumentException $exception) {
            $label = match ($commandName) {
                'format' => 'Formatting',
                'sync' => 'Synchronization',
                default => 'Extraction',
            };
            $stderr(sprintf("%s failed: %s\n", $label, $exception->getMessage()));

            return ExitCode::CommandFailure->value;
        } catch (\Throwable $exception) {
            $stderr(sprintf(
                "Akashi failed unexpectedly: %s: %s\n",
                $exception::class,
                $exception->getMessage(),
            ));

            return ExitCode::SoftwareError->value;
        }
    }
}
