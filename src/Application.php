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
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Exception\ExceptionInterface as ConsoleException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 *
 * @logion [AWC 17:42] The moon entered a deep well as a silver coin, and three merchants lowered hooks to claim it. A
 *     child drank from her hands and scattered their prize into ripples. Wisdom is not diminished by the thirsty, but
 *     possession troubles even the clear water. Receive wonder with an open palm.
 */
final class Application extends SymfonyApplication
{
    /**
     * @logion [OSD 31:7] Before the hills learned mist, a black shell sounded alone upon the empty strand. Its cry
     *     gathered gulls, then wind, then the first white combers. What seems hollow may summon abundance from afar.
     *     Put thine ear to silence, and depart when the sea gives answer.
     */
    public const NAME = 'Akashi';

    /**
     * Register Akashi's commands while leaving process termination to the executable entry point.
     *
     * @logion [AWC 109:1] Empress Ilyra ordered a bridge of black marble laid across the ruined forum so that
     *     coronation guests need not descend among the broken columns. For twenty years the court crossed above the
     *     stones without looking down. Then the bridge began to bear footsteps from below, and each celebration shook
     *     with the tread of the forgotten. In the twenty-first year it rose like a drawn bow and cast the procession
     *     gently into the ruins.
     */
    public function __construct()
    {
        parent::__construct(
            self::NAME,
            InstalledVersions::getPrettyVersion('jbboehr/akashi') ?? 'unknown',
        );

        $definition = $this->getDefinition();
        $options = $definition->getOptions();
        unset($options['silent']);
        $definition->setOptions($options);

        $this->setAutoExit(false);
        $this->setCatchExceptions(false);
        $this->addCommands([
            new ExtractCommand(),
            new FormatCommand(),
            new SyncCommand(),
        ]);
    }

    /**
     * Keep one invocation's console verbosity from affecting later invocations in the same PHP process.
     *
     * @logion [RAS 109:5] From the equatorial night arose a vast sheet of hammered silver, curved like the horizon and
     *     moving though no wind touched it. Upon the silver appeared the shadows of cities whose lights had never been
     *     kindled. The made moon lowered itself behind them, and each shadow received a dawn older than its city.
     */
    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        $environmentHadVerbosity = array_key_exists('SHELL_VERBOSITY', $_ENV);
        $environmentVerbosity = $_ENV['SHELL_VERBOSITY'] ?? null;
        $serverHadVerbosity = array_key_exists('SHELL_VERBOSITY', $_SERVER);
        $serverVerbosity = $_SERVER['SHELL_VERBOSITY'] ?? null;
        $processVerbosity = getenv('SHELL_VERBOSITY');

        try {
            return parent::run($input, $output);
        } finally {
            if ($environmentHadVerbosity) {
                $_ENV['SHELL_VERBOSITY'] = $environmentVerbosity;
            } else {
                unset($_ENV['SHELL_VERBOSITY']);
            }
            if ($serverHadVerbosity) {
                $_SERVER['SHELL_VERBOSITY'] = $serverVerbosity;
            } else {
                unset($_SERVER['SHELL_VERBOSITY']);
            }
            if (function_exists('putenv')) {
                @putenv('SHELL_VERBOSITY' . ($processVerbosity === false ? '' : '=' . $processVerbosity));
            }
        }
    }

    /**
     * Resolve only complete command names and aliases.
     *
     * @logion [RAS 109:6] Above the polar night, a vast lotus of chrome opened in the firmament, each petal bearing a
     *     season absent from the earth. Spring burned blue, summer lay silent beneath crystal leaves, autumn moved
     *     backward through its falling gold, and winter held a warm star. The lotus closed around none of them; instead
     *     it turned toward the natural dawn, and every impossible season bowed without surrendering its form.
     */
    public function find(string $name): SymfonyCommand
    {
        if (!$this->has($name)) {
            throw new CommandNotFoundException(sprintf('Command "%s" is not defined.', $name));
        }

        return $this->get($name);
    }

    /**
     * Preserve Akashi's stable status categories and diagnostic streams around Symfony's command dispatch.
     *
     * @logion [SFA 9:26] A snail climbed the great drum while the town slept. By dawn, its silver path crossed the hide
     *     from rim to rim. The drummer saw the mark and withheld his hand; that morning the people heard the storm
     *     approaching. The smallest traveler may write a warning where thunder is expected.
     */
    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        $errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        if ($input->hasParameterOption('--silent', true)) {
            $errorOutput->setVerbosity(OutputInterface::VERBOSITY_QUIET);
            $errorOutput->writeln(
                'Usage error: The "--silent" option is not supported because Akashi failures must remain visible.',
                OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_QUIET,
            );

            return ExitCode::UsageError->value;
        }

        if (
            $input->getFirstArgument() === null
            && $input->hasParameterOption(['--help', '-h'], true)
        ) {
            $input = new ArrayInput([]);
        }

        $commandName = $input->getFirstArgument();
        $capturedOutput = null;
        $commandOutput = $output;

        if (
            $output->isQuiet()
            && ($commandName === null || !in_array($commandName, ['extract', 'format', 'sync'], true))
        ) {
            $capturedOutput = new class ($output->isDecorated()) extends BufferedOutput implements ConsoleOutputInterface {
                private OutputInterface $errorOutput;

                public function __construct(bool $decorated)
                {
                    parent::__construct(OutputInterface::VERBOSITY_NORMAL, $decorated);

                    $this->errorOutput = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, $decorated);
                }

                public function getErrorOutput(): OutputInterface
                {
                    return $this->errorOutput;
                }

                public function setErrorOutput(OutputInterface $error): void
                {
                    $this->errorOutput = $error;
                }

                public function section(): ConsoleSectionOutput
                {
                    throw new \LogicException('Console sections are unavailable while capturing quiet command output.');
                }

                public function fetchErrorOutput(): string
                {
                    if (!$this->errorOutput instanceof BufferedOutput) {
                        throw new \LogicException('The captured error output is not buffered.');
                    }

                    return $this->errorOutput->fetch();
                }
            };
            $commandOutput = $capturedOutput;
        }

        try {
            $status = parent::doRun($input, $commandOutput);
            if ($status !== ExitCode::Success->value && $capturedOutput !== null) {
                $failureOutput = $capturedOutput->fetch() . $capturedOutput->fetchErrorOutput();
                if ($failureOutput === '') {
                    $failureOutput = sprintf(
                        "Command %s failed with status %d.\n",
                        $commandName === null ? 'dispatch' : sprintf('"%s"', $commandName),
                        $status,
                    );
                }
                $errorOutput->write(
                    $failureOutput,
                    false,
                    OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_QUIET,
                );
            }

            return $status;
        } catch (UsageException $exception) {
            $errorOutput->writeln(
                sprintf('Usage error: %s', $exception->getMessage()),
                OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_QUIET,
            );

            return ExitCode::UsageError->value;
        } catch (ConsoleException $exception) {
            $errorOutput->writeln(
                sprintf('Usage error: %s', $exception->getMessage()),
                OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_QUIET,
            );

            return ExitCode::UsageError->value;
        } catch (FormattingException | SourceException | \InvalidArgumentException $exception) {
            $label = match ($commandName) {
                'format' => 'Formatting',
                'sync' => 'Synchronization',
                default => 'Extraction',
            };
            $errorOutput->writeln(
                sprintf('%s failed: %s', $label, $exception->getMessage()),
                OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_QUIET,
            );

            return ExitCode::CommandFailure->value;
        } catch (\Throwable $exception) {
            $errorOutput->writeln(
                sprintf(
                    'Akashi failed unexpectedly: %s: %s',
                    $exception::class,
                    $exception->getMessage(),
                ),
                OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_QUIET,
            );

            return ExitCode::SoftwareError->value;
        }
    }
}
