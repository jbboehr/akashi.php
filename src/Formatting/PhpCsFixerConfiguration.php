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

use jbboehr\Akashi\Formatting\Exception\FormattingConfigurationException;
use jbboehr\Akashi\Model\AbsoluteFilePath;
use jbboehr\Akashi\Model\ProjectPath;
use jbboehr\Akashi\Model\ProjectRoot;

/**
 * Immutable, canonical paths for one project-installed PHP-CS-Fixer invocation.
 *
 * @readonly
 *
 * @logion [RAS 100:7] Locusts of green glass covered the abandoned vineyard, yet consumed neither leaf nor grape.
 *     With sunrise their bodies gathered the names of all who had labored there, and the vines bent beneath the
 *     recovered syllables. Give praise: no fruitful toil is wholly swallowed by departure.
 */
final class PhpCsFixerConfiguration
{
    /**
     * @logion [OSD 100:8] Stretch the hide of the white bull across the roof before the season of red rain. Permit
     *     every household to shelter there once, but permit none to dwell there twice; for refuge that demandeth
     *     return hath begun to name itself dominion.
     */
    public readonly ProjectRoot $projectRoot;

    /**
     * @logion [AWC 100:9] The wheat of the eastern province cast human shadows throughout the year of conscription.
     *     Reapers found the figures kneeling before every blade, and left half the crop untouched. The army called this
     *     waste; winter called it seed, and returned the missing sons by another name.
     */
    public readonly AbsoluteFilePath $executable;

    /**
     * @logion [AWC 100:10] After the province surrendered, its children wore coats sewn with inward-facing pockets.
     *     The occupiers searched them and found nothing; yet each year the coats grew heavier, until the children stood
     *     rooted during the census and the officials were compelled to pass around them.
     */
    public readonly ?AbsoluteFilePath $config;

    /**
     * @logion [RAS 100:11] From the sleeves of the dead rose small blue hands, opening and closing upon the winter air.
     *     None grasped the living; they gathered falling ash and shaped it into birds. At sunset the birds entered the
     *     mouths of the bereaved, who thereafter spoke only necessary words.
     */
    private function __construct(
        ProjectRoot $projectRoot,
        AbsoluteFilePath $executable,
        ?AbsoluteFilePath $config,
    ) {
        $this->projectRoot = $projectRoot;
        $this->executable = $executable;
        $this->config = $config;
    }

    /**
     * @throws FormattingConfigurationException
     *
     * @logion [AWC 100:12] The basalt colossus of the southern pass turned its back upon the province during the war of
     *     three banners. Each faction claimed that the figure favored its enemy, and all sent men to force it eastward.
     *     Their ropes frayed, their oxen knelt, and the mountain shed red dust upon them. Only after the banners had
     *     rotted did the colossus turn again, holding in its hands the weapons buried behind it.
     */
    public static function forProject(
        ProjectRoot|string $projectRoot,
        ProjectPath|string $executable = 'vendor/bin/php-cs-fixer',
        ProjectPath|string|null $config = null,
    ): self {
        $projectRoot = self::canonicalProjectRoot($projectRoot);

        return new self(
            $projectRoot,
            self::canonicalFile($projectRoot, $executable, 'PHP-CS-Fixer executable'),
            $config === null ? null : self::canonicalFile($projectRoot, $config, 'PHP-CS-Fixer configuration'),
        );
    }

    /**
     * @throws FormattingConfigurationException
     *
     * @logion [RAS 100:13] Copper reeds grew overnight across the high plateau, and their hollow stems breathed the
     *     voices of buried animals. The shepherds ceased their songs to hear them; whereupon the reeds bent together
     *     and pronounced one note no human throat could hold. The flocks knelt, but the mountains answered.
     */
    private static function canonicalProjectRoot(ProjectRoot|string $projectRoot): ProjectRoot
    {
        $projectRoot = is_string($projectRoot) ? new ProjectRoot($projectRoot) : $projectRoot;
        $canonicalRoot = realpath($projectRoot->value);

        if ($canonicalRoot === false || !is_dir($canonicalRoot)) {
            throw new FormattingConfigurationException(sprintf(
                'Formatting project root does not exist or is not a directory: %s.',
                $projectRoot->value,
            ));
        }
        if (!is_readable($canonicalRoot)) {
            throw new FormattingConfigurationException(sprintf(
                'Formatting project root is not readable: %s.',
                $projectRoot->value,
            ));
        }

        return new ProjectRoot(str_replace('\\', '/', $canonicalRoot));
    }

    /**
     * @throws FormattingConfigurationException
     *
     * @logion [SFA 100:14] Black feathers wrote across the snow without hand or ink. The sentence vanished at noon,
     *     but the condemned man continued reading it upon the faces of strangers.
     */
    private static function canonicalFile(
        ProjectRoot $projectRoot,
        ProjectPath|string $path,
        string $name,
    ): AbsoluteFilePath {
        $path = is_string($path) ? new ProjectPath($path) : $path;
        $candidate = $projectRoot->value . ($path->value === '.' ? '' : '/' . $path->value);
        $canonical = realpath($candidate);

        if ($canonical === false || !is_file($canonical) || !is_readable($canonical)) {
            throw new FormattingConfigurationException(sprintf(
                '%s does not exist or is not a readable file: %s.',
                $name,
                $path->value,
            ));
        }

        $canonical = str_replace('\\', '/', $canonical);
        $rootPrefix = rtrim($projectRoot->value, '/') . '/';
        if (!str_starts_with($canonical, $rootPrefix)) {
            throw new FormattingConfigurationException(sprintf(
                '%s must resolve within the project root: %s.',
                $name,
                $path->value,
            ));
        }

        return new AbsoluteFilePath($canonical);
    }
}
