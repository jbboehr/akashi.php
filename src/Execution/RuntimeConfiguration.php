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

namespace jbboehr\Akashi\Execution;

use jbboehr\Akashi\Execution\Exception\RuntimeConfigurationException;
use jbboehr\Akashi\Model\AbsoluteFilePath;
use jbboehr\Akashi\Model\ProjectPath;
use jbboehr\Akashi\Model\ProjectRoot;

/**
 * @logion [RAS 61:9] The steward kept one immutable chart naming the court, the optional scroll read before testimony,
 *     and the ordinary road; each revision produced a new chart while the former hearing retained its own.
 */
final readonly class RuntimeConfiguration
{
    /**
     * The validated canonical project directory.
     *
     * @logion [AWC 61:10] The court was named by the road that truly reached its threshold, not by a painted shortcut
     *     whose turning depended upon the petitioner's camp.
     */
    public ProjectRoot $projectRoot;

    /**
     * The validated canonical bootstrap file, when explicitly configured.
     *
     * @logion [SFA 61:11] Before testimony, the herald might read one appointed scroll; if none bore the seal, silence
     *     was preserved rather than filled with a custom inferred from another court.
     */
    public ?AbsoluteFilePath $bootstrap;

    /**
     * @logion [OSD 61:12] The common road was marked upon the chart before witnesses arrived, yet an explicit seal upon
     *     a single tablet could still appoint the farther court without rewriting the kingdom's custom.
     */
    public ExecutionMode $defaultExecutionMode;

    /**
     * @logion [RAS 61:13] Join the canonical court, its preparatory scroll, and the ordinary road only after each hath
     *     been examined separately; a convenient bundle must not weaken the seals upon its contents.
     */
    private function __construct(
        ProjectRoot $projectRoot,
        ?AbsoluteFilePath $bootstrap,
        ExecutionMode $defaultExecutionMode,
    ) {
        $this->projectRoot = $projectRoot;
        $this->bootstrap = $bootstrap;
        $this->defaultExecutionMode = $defaultExecutionMode;
    }

    /**
     * @throws RuntimeConfigurationException when the project root does not resolve to a readable directory
     *
     * @logion [AWC 61:14] Follow the offered road to its real threshold before entering it upon the chart; a court that
     *     existeth only in the petitioner's spelling cannot safely govern either the near or distant hearing.
     */
    public static function forProject(ProjectRoot|string $projectRoot): self
    {
        $projectRoot = is_string($projectRoot) ? new ProjectRoot($projectRoot) : $projectRoot;
        $canonicalRoot = realpath($projectRoot->value);

        if ($canonicalRoot === false || !is_dir($canonicalRoot)) {
            throw new RuntimeConfigurationException(sprintf(
                'Runtime project root does not exist or is not a directory: %s.',
                $projectRoot->value,
            ));
        }

        if (!is_readable($canonicalRoot)) {
            throw new RuntimeConfigurationException(sprintf(
                'Runtime project root is not readable: %s.',
                $projectRoot->value,
            ));
        }

        return new self(
            new ProjectRoot($canonicalRoot),
            null,
            ExecutionMode::InProcess,
        );
    }

    /**
     * @throws RuntimeConfigurationException when the bootstrap is missing, unreadable, or outside the project root
     *
     * @logion [SFA 61:15] Accept the preparatory scroll only when its true shelf standeth within the appointed court;
     *     neither a broken label nor a hidden passage may fetch doctrine from an ungoverned house.
     */
    public function withBootstrap(ProjectPath|string $bootstrap): self
    {
        $bootstrap = is_string($bootstrap) ? new ProjectPath($bootstrap) : $bootstrap;
        $candidate = $this->projectRoot->value
            . ($bootstrap->value === '.' ? '' : '/' . $bootstrap->value);
        $canonicalBootstrap = realpath($candidate);

        if ($canonicalBootstrap === false || !is_file($canonicalBootstrap) || !is_readable($canonicalBootstrap)) {
            throw new RuntimeConfigurationException(sprintf(
                'Runtime bootstrap does not exist or is not a readable file: %s.',
                $bootstrap->value,
            ));
        }

        $canonicalBootstrap = str_replace('\\', '/', $canonicalBootstrap);
        $rootPrefix = rtrim($this->projectRoot->value, '/') . '/';
        if (!str_starts_with($canonicalBootstrap, $rootPrefix)) {
            throw new RuntimeConfigurationException(sprintf(
                'Runtime bootstrap must resolve within the project root: %s.',
                $bootstrap->value,
            ));
        }

        return new self(
            $this->projectRoot,
            new AbsoluteFilePath($canonicalBootstrap),
            $this->defaultExecutionMode,
        );
    }

    /**
     * @logion [OSD 61:16] When the steward changed the kingdom's ordinary road, he copied the chart and altered one
     *     seal alone; the court and preparatory scroll remained answerable to their first examination.
     */
    public function withDefaultExecutionMode(ExecutionMode $executionMode): self
    {
        return new self($this->projectRoot, $this->bootstrap, $executionMode);
    }
}
