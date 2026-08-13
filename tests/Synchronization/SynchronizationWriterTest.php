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

namespace jbboehr\Akashi\Tests\Synchronization;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Synchronization\Exception\SynchronizationWriteException;
use jbboehr\Akashi\Synchronization\SynchronizationWriter;
use PHPUnit\Framework\TestCase;

final class SynchronizationWriterTest extends TestCase
{
    private string $workspace;

    protected function setUp(): void
    {
        $workspace = tempnam(sys_get_temp_dir(), 'akashi-sync-writer-');
        self::assertNotFalse($workspace);
        self::assertTrue(unlink($workspace));
        self::assertTrue(mkdir($workspace, 0o700));

        $this->workspace = $workspace;
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->workspace)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->workspace, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $path) {
            if (!$path instanceof \SplFileInfo) {
                continue;
            }

            if ($path->isFile() || $path->isLink()) {
                self::assertTrue(unlink($path->getPathname()));
                continue;
            }

            self::assertTrue(rmdir($path->getPathname()));
        }

        self::assertTrue(rmdir($this->workspace));
    }

    public function testAtomicallyReplacesTheLoadedBytesAndPreservesPermissions(): void
    {
        $path = $this->workspace . '/example.md';
        self::assertNotFalse(file_put_contents($path, "old\n"));
        self::assertTrue(chmod($path, 0o640));

        SynchronizationWriter::forProject($this->workspace)->write(
            new Document('example.md', "old\n"),
            new Document('example.md', "new\n"),
        );

        self::assertSame("new\n", file_get_contents($path));
        self::assertSame([], glob($this->workspace . '/.akashi-sync-*') ?: []);
        if (DIRECTORY_SEPARATOR !== '\\') {
            self::assertSame(0o640, fileperms($path) & 0o7777);
        }
    }

    public function testRefusesToOverwriteBytesChangedSinceTheDocumentWasLoaded(): void
    {
        $path = $this->workspace . '/example.md';
        self::assertNotFalse(file_put_contents($path, "newer\n"));

        try {
            SynchronizationWriter::forProject($this->workspace)->write(
                new Document('example.md', "old\n"),
                new Document('example.md', "replacement\n"),
            );
            self::fail('Expected stale document bytes to be rejected.');
        } catch (SynchronizationWriteException $exception) {
            self::assertStringContainsString('refusing to overwrite newer bytes', $exception->getMessage());
        }

        self::assertSame("newer\n", file_get_contents($path));
        self::assertSame([], glob($this->workspace . '/.akashi-sync-*') ?: []);
    }

    public function testRejectsAReplacementForAnotherDocument(): void
    {
        self::assertNotFalse(file_put_contents($this->workspace . '/example.md', "old\n"));

        $this->expectException(SynchronizationWriteException::class);
        $this->expectExceptionMessage('does not match original document');

        SynchronizationWriter::forProject($this->workspace)->write(
            new Document('example.md', "old\n"),
            new Document('other.md', "new\n"),
        );
    }

    public function testRejectsASymbolicLinkWithoutChangingItsTarget(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('Symbolic-link creation is not generally available to Windows test users.');
        }

        self::assertNotFalse(file_put_contents($this->workspace . '/target.md', "target\n"));
        self::assertTrue(symlink($this->workspace . '/target.md', $this->workspace . '/example.md'));

        try {
            SynchronizationWriter::forProject($this->workspace)->write(
                new Document('example.md', "target\n"),
                new Document('example.md', "replacement\n"),
            );
            self::fail('Expected a symbolic-link target to be rejected.');
        } catch (SynchronizationWriteException $exception) {
            self::assertStringContainsString('must not be symbolic links', $exception->getMessage());
        }

        self::assertSame("target\n", file_get_contents($this->workspace . '/target.md'));
        self::assertTrue(is_link($this->workspace . '/example.md'));
    }

    public function testRejectsAPathThroughASymbolicLinkDirectory(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('Symbolic-link creation is not generally available to Windows test users.');
        }

        self::assertTrue(mkdir($this->workspace . '/real', 0o700));
        self::assertNotFalse(file_put_contents($this->workspace . '/real/example.md', "target\n"));
        self::assertTrue(symlink($this->workspace . '/real', $this->workspace . '/alias'));

        try {
            SynchronizationWriter::forProject($this->workspace)->write(
                new Document('alias/example.md', "target\n"),
                new Document('alias/example.md', "replacement\n"),
            );
            self::fail('Expected a symbolic-link directory to be rejected.');
        } catch (SynchronizationWriteException $exception) {
            self::assertStringContainsString('must not traverse symbolic-link directories', $exception->getMessage());
        }

        self::assertSame("target\n", file_get_contents($this->workspace . '/real/example.md'));
    }

    public function testRejectsANonexistentProjectRoot(): void
    {
        $this->expectException(SynchronizationWriteException::class);
        $this->expectExceptionMessage('does not exist or is not a directory');

        SynchronizationWriter::forProject($this->workspace . '/missing');
    }
}
