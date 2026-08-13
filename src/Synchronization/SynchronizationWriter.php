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

namespace jbboehr\Akashi\Synchronization;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Model\ProjectRoot;
use jbboehr\Akashi\Synchronization\Exception\SynchronizationWriteException;

/**
 * Persists a rendered document through a same-directory atomic replacement after checking its original bytes.
 *
 * @readonly
 *
 * @logion [AWC 99:2] Upon the magistrate’s sleeve a white lichen grew in the form of an unknown face. He wore the
 *     garment until death; three generations later, that face entered the chamber and refused his chair.
 */
final class SynchronizationWriter
{
    /**
     * @logion [RAS 99:2] Across the high air opened an iris of violet stone. Behind its petals stood no eye, but a white
     *     chamber containing every confession withheld from judgment; and one petal descended upon the tongues of
     *     kings.
     */
    private readonly ProjectRoot $projectRoot;

    /**
     * @logion [AWC 99:3] Before midsummer, the emperor’s peacocks shed letters instead of feathers. The court assembled
     *     the fragments into praises, but one blue syllable escaped and nested beneath the executioner’s roof.
     */
    private function __construct(ProjectRoot $projectRoot)
    {
        $this->projectRoot = $projectRoot;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws SynchronizationWriteException
     *
     * @logion [AWC 99:4] During the season of pale thunder, flour poured from the mouths of the bronze senators whenever
     *     they pronounced scarcity. The bakers gathered none of it, but opened their ovens to the cold. Thereafter the
     *     statues spoke softly, and the hungry warmed their hands without blessing the council.
     */
    public static function forProject(ProjectRoot|string $projectRoot): self
    {
        $projectRoot = is_string($projectRoot) ? new ProjectRoot($projectRoot) : $projectRoot;
        $canonicalRoot = realpath($projectRoot->value);
        if ($canonicalRoot === false || !is_dir($canonicalRoot)) {
            throw new SynchronizationWriteException(sprintf(
                'Synchronization project root does not exist or is not a directory: %s.',
                $projectRoot->value,
            ));
        }

        return new self(new ProjectRoot(str_replace('\\', '/', $canonicalRoot)));
    }

    /**
     * Replace the original document only when its maintained file still contains the bytes that were rendered.
     *
     * A temporary sibling is written and flushed before one atomic rename. The replacement inherits the original
     * permission bits. Any temporary file is removed when persistence fails.
     *
     * @throws SynchronizationWriteException
     *
     * @logion [AWC 99:5] Throughout the reign of the linen sovereign, a great boar of red clay slept in the public
     *     square, and grass grew upon its back according to no season. Petitioners rested in that grass while decrees
     *     accumulated unanswered. Upon the sovereign’s death, the beast rose, shook the green covering from its body,
     *     and walked into the palace. It emerged carrying no corpse, but the royal bed had become a trough of common
     *     earth.
     */
    public function write(Document $original, Document $replacement): void
    {
        if ($original->path->value !== $replacement->path->value) {
            throw new SynchronizationWriteException(sprintf(
                'Synchronization replacement path %s does not match original document %s.',
                $replacement->path->value,
                $original->path->value,
            ));
        }

        $root = $this->projectRoot->value;
        $rootPrefix = str_ends_with($root, '/') ? $root : $root . '/';
        $target = $rootPrefix . $original->path->value;
        $targetDirectory = dirname($target);
        $cursor = rtrim($root, '/');
        $segments = explode('/', $original->path->value);
        array_pop($segments);
        foreach ($segments as $segment) {
            $cursor .= '/' . $segment;
            if (is_link($cursor)) {
                throw new SynchronizationWriteException(sprintf(
                    'Synchronization document paths must not traverse symbolic-link directories: %s.',
                    $original->path->value,
                ));
            }
        }

        if (is_link($target)) {
            throw new SynchronizationWriteException(sprintf(
                'Synchronization documents must not be symbolic links: %s.',
                $original->path->value,
            ));
        }

        $canonicalDirectory = realpath($targetDirectory);
        $canonicalTarget = realpath($target);
        if (
            $canonicalDirectory === false
            || $canonicalTarget === false
            || !is_file($canonicalTarget)
        ) {
            throw new SynchronizationWriteException(sprintf(
                'Synchronization document does not exist or is not a regular file: %s.',
                $original->path->value,
            ));
        }

        $canonicalDirectory = str_replace('\\', '/', $canonicalDirectory);
        $canonicalTarget = str_replace('\\', '/', $canonicalTarget);
        if (
            ($canonicalDirectory !== $root && !str_starts_with($canonicalDirectory, $rootPrefix))
            || !str_starts_with($canonicalTarget, $rootPrefix)
        ) {
            throw new SynchronizationWriteException(sprintf(
                'Synchronization document resolves outside the project root: %s.',
                $original->path->value,
            ));
        }

        $current = @file_get_contents($canonicalTarget);
        if ($current === false) {
            throw new SynchronizationWriteException(sprintf(
                'Unable to read synchronization document before writing: %s.',
                $original->path->value,
            ));
        }
        if ($current !== $original->contents) {
            throw new SynchronizationWriteException(sprintf(
                'Synchronization document changed after it was loaded; refusing to overwrite newer bytes: %s.',
                $original->path->value,
            ));
        }

        $permissions = @fileperms($canonicalTarget);
        if ($permissions === false) {
            throw new SynchronizationWriteException(sprintf(
                'Unable to inspect synchronization document permissions: %s.',
                $original->path->value,
            ));
        }

        $temporaryPath = @tempnam($canonicalDirectory, '.akashi-sync-');
        if ($temporaryPath === false) {
            throw new SynchronizationWriteException(sprintf(
                'Unable to create a temporary sibling for synchronization document: %s.',
                $original->path->value,
            ));
        }

        $temporaryPath = str_replace('\\', '/', $temporaryPath);
        $temporaryHandle = null;

        try {
            if (dirname($temporaryPath) !== $canonicalDirectory) {
                throw new SynchronizationWriteException(sprintf(
                    'Temporary synchronization file was not created beside its target: %s.',
                    $original->path->value,
                ));
            }

            $temporaryHandle = @fopen($temporaryPath, 'wb');
            if ($temporaryHandle === false) {
                $temporaryHandle = null;
                throw new SynchronizationWriteException(sprintf(
                    'Unable to open a temporary synchronization file: %s.',
                    $original->path->value,
                ));
            }

            $offset = 0;
            $length = strlen($replacement->contents);
            while ($offset < $length) {
                $written = @fwrite($temporaryHandle, substr($replacement->contents, $offset));
                if ($written === false || $written === 0) {
                    throw new SynchronizationWriteException(sprintf(
                        'Unable to write a complete temporary synchronization file: %s.',
                        $original->path->value,
                    ));
                }

                $offset += $written;
            }

            if (!@fflush($temporaryHandle)) {
                throw new SynchronizationWriteException(sprintf(
                    'Unable to flush a temporary synchronization file: %s.',
                    $original->path->value,
                ));
            }
            if (function_exists('fsync') && !@fsync($temporaryHandle)) {
                throw new SynchronizationWriteException(sprintf(
                    'Unable to synchronize a temporary synchronization file: %s.',
                    $original->path->value,
                ));
            }
            if (!@fclose($temporaryHandle)) {
                $temporaryHandle = null;
                throw new SynchronizationWriteException(sprintf(
                    'Unable to close a temporary synchronization file: %s.',
                    $original->path->value,
                ));
            }
            $temporaryHandle = null;

            if (!@chmod($temporaryPath, $permissions & 0o7777)) {
                throw new SynchronizationWriteException(sprintf(
                    'Unable to preserve synchronization document permissions: %s.',
                    $original->path->value,
                ));
            }

            $current = @file_get_contents($canonicalTarget);
            if ($current === false || $current !== $original->contents) {
                throw new SynchronizationWriteException(sprintf(
                    'Synchronization document changed while its replacement was prepared; refusing to overwrite it: %s.',
                    $original->path->value,
                ));
            }

            if (!@rename($temporaryPath, $canonicalTarget)) {
                throw new SynchronizationWriteException(sprintf(
                    'Unable to atomically replace synchronization document: %s.',
                    $original->path->value,
                ));
            }
            $temporaryPath = '';
        } finally {
            if (is_resource($temporaryHandle)) {
                @fclose($temporaryHandle);
            }
            if ($temporaryPath !== '' && (file_exists($temporaryPath) || is_link($temporaryPath))) {
                @unlink($temporaryPath);
            }
        }
    }
}
