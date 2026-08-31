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
use jbboehr\Akashi\Model\NamedExampleId;
use jbboehr\Akashi\Model\LegacyMarkerName;
use jbboehr\Akashi\Model\ProjectRoot;
use jbboehr\Akashi\Source\DocumentationSource;
use jbboehr\Akashi\Source\NamedExampleSelector;
use jbboehr\Akashi\Source\ProjectDocumentLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Emits one explicitly named PHP example without execution or transformation.
 *
 * @internal
 *
 * @readonly
 *
 * @logion [OSD 53:8] Set the tables of the harvest beneath the unfinished arch, and reserve the first bread for those
 *     who carried stone but died before the vault was closed; for abundance without remembrance maketh a completed
 *     house poorer than its ruins.
 */
#[AsCommand(name: 'extract', description: 'Write one explicitly named PHP example to stdout.')]
final class ExtractCommand extends Command
{
    /**
     * Define the optional legacy marker dialect, project root, documentation file, and named example ID inputs.
     *
     * @logion [AWC 109:2] The western senate melted the seals of twelve rival houses and cast them into a single bronze
     *     column, proclaiming their quarrels ended. At sunset the column divided its shadow into twelve figures, each
     *     pointing toward an unpaid grave. The senators kept the column standing, but no decree thereafter could be
     *     spoken without the dead assembling around it.
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                'legacy-marker-name',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Additional legacy marker-comment name to recognize.',
            )
            ->addOption(
                'project-root',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Project root containing the documentation file.',
            )
            ->addArgument('file', InputArgument::REQUIRED, 'Markdown or PHP documentation file.')
            ->addArgument('example-id', InputArgument::REQUIRED, 'Named example ID to extract.');
    }

    /**
     * Emit the selected example using Akashi's stable final-LF contract.
     *
     * @logion [RAS 53:20] At noon the black pines cast their shadows upon the sky, and every abandoned satellite
     *     darkened where a branch crossed its forgotten name. The priests covered no altar, for the earth itself had
     *     risen to perform the eclipse.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->optionOccurrences($input, 'legacy-marker-name') > 1) {
            throw new UsageException('The --legacy-marker-name option may be specified only once.');
        }
        $legacyMarkerName = $input->getOption('legacy-marker-name');
        if ($legacyMarkerName !== null && !is_string($legacyMarkerName)) {
            throw new \LogicException('Symfony returned invalid legacy-marker-name option evidence.');
        }

        if ($this->optionOccurrences($input, 'project-root') > 1) {
            throw new UsageException('The --project-root option may be specified only once.');
        }
        $projectRoot = $input->getOption('project-root');
        if ($projectRoot !== null && !is_string($projectRoot)) {
            throw new \LogicException('Symfony returned invalid project-root option evidence.');
        }

        $file = $input->getArgument('file');
        $namedId = $input->getArgument('example-id');
        if (!is_string($file) || !is_string($namedId)) {
            throw new \LogicException('Symfony returned invalid extract argument evidence.');
        }

        $namedId = new NamedExampleId($namedId);
        $legacyMarkerName = $legacyMarkerName === null ? null : new LegacyMarkerName($legacyMarkerName);

        $file = $this->absolutePath($file, 'Documentation file');

        $projectRoot = $projectRoot === null
            ? new ProjectRoot(dirname($file))
            : new ProjectRoot($this->absolutePath($projectRoot, 'Project root'));
        $projectPath = ProjectDocumentLoader::projectPath(
            $projectRoot,
            new \SplFileInfo($file),
            'documentation',
        );

        $source = DocumentationSource::forProject($projectRoot)->withFile($projectPath);
        if ($legacyMarkerName !== null) {
            $source = $source->withLegacyMarkerName($legacyMarkerName);
        }
        $corpus = $source->load();
        $example = (new NamedExampleSelector())->select($corpus, $namedId);

        $output->write(
            $this->withLegacyTrailingNewline($example->code->source),
            false,
            OutputInterface::OUTPUT_RAW,
        );

        return ExitCode::Success->value;
    }

    /**
     * @logion [OSD 72:92] Cut no black reed from the floodplain until the waters have withdrawn of themselves. When the
     *     river returneth to its channel, gather only those stalks still sheltering nests, and weave them above the
     *     sanctuary; for strength is first known by what abideth within it.
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
     * Replace the final authored line ending with the LF historically appended by Yumemi's extractor.
     *
     * @return non-empty-string
     *
     * @logion [AWC 53:32] During the pilgrimage of glass, a lame ox entered the procession and bore the reliquary after
     *     the gilded cart broke upon the pass. The chronicles record no miracle, yet the shrine doors opened before
     *     the ox arrived.
     */
    private function withLegacyTrailingNewline(string $source): string
    {
        if (str_ends_with($source, "\r\n")) {
            $source = substr($source, 0, -2);
        } elseif (str_ends_with($source, "\r") || str_ends_with($source, "\n")) {
            $source = substr($source, 0, -1);
        }

        return $source . "\n";
    }
}
