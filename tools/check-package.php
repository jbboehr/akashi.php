#!/usr/bin/env php
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
 */

declare(strict_types=1);

use Symfony\Component\Process\Process;

require dirname(__DIR__) . '/vendor/autoload.php';

$projectRoot = dirname(__DIR__);
$arguments = $_SERVER['argv'] ?? null;
if (!is_array($arguments) || !array_is_list($arguments)) {
    fwrite(STDERR, "Package check failed: command-line arguments are unavailable.\n");
    exit(1);
}

foreach ($arguments as $argument) {
    if (!is_string($argument)) {
        fwrite(STDERR, "Package check failed: a command-line argument is not a string.\n");
        exit(1);
    }
}

array_shift($arguments);
$temporaryRoot = rtrim(sys_get_temp_dir(), '/\\') . '/akashi-package-check-' . bin2hex(random_bytes(16));
$archive = $temporaryRoot . '/akashi-package-check.tar';
$exitCode = 0;

$removeTemporaryRoot = static function () use ($temporaryRoot): void {
    if (!is_dir($temporaryRoot)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($temporaryRoot, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $entry) {
        if (!$entry instanceof SplFileInfo) {
            continue;
        }

        if ($entry->isFile() || $entry->isLink()) {
            if (!unlink($entry->getPathname())) {
                throw new RuntimeException(sprintf('Unable to remove temporary package file %s.', $entry->getPathname()));
            }
        } elseif (!rmdir($entry->getPathname())) {
            throw new RuntimeException(sprintf('Unable to remove temporary package directory %s.', $entry->getPathname()));
        }
    }

    if (!rmdir($temporaryRoot)) {
        throw new RuntimeException(sprintf('Unable to remove temporary package root %s.', $temporaryRoot));
    }
};

try {
    if (count($arguments) > 1) {
        throw new InvalidArgumentException('Usage: php tools/check-package.php [archive.tar]');
    }

    if ($arguments === []) {
        if (!mkdir($temporaryRoot, 0o700)) {
            throw new RuntimeException(sprintf('Unable to create temporary package root %s.', $temporaryRoot));
        }

        $composerBinary = getenv('COMPOSER_BINARY');
        $process = new Process([
            is_string($composerBinary) && $composerBinary !== '' ? $composerBinary : 'composer',
            'archive',
            '--format=tar',
            '--dir=' . $temporaryRoot,
            '--file=akashi-package-check',
            '--no-interaction',
        ], $projectRoot);
        $process->setTimeout(60.0);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf(
                "Composer archive creation failed.\n%s%s",
                $process->getOutput(),
                $process->getErrorOutput(),
            ));
        }

        if (!is_file($archive)) {
            throw new RuntimeException('Composer did not create the expected package archive.');
        }
    } else {
        $archiveArgument = $arguments[0];
        $archiveCandidate = preg_match('~\A(?:[A-Za-z]:[\\\\/]|[\\\\/]{2}|/)~', $archiveArgument) === 1
            ? $archiveArgument
            : $projectRoot . '/' . $archiveArgument;
        $resolvedArchive = realpath($archiveCandidate);

        if ($resolvedArchive === false || !is_file($resolvedArchive)) {
            throw new RuntimeException(sprintf('Package archive %s does not exist or is not a file.', $archiveArgument));
        }

        $archive = $resolvedArchive;
    }

    $package = new PharData($archive);
    $archivePrefix = 'phar://' . str_replace('\\', '/', $archive) . '/';
    $files = [];

    foreach (new RecursiveIteratorIterator($package) as $entry) {
        if (!$entry instanceof PharFileInfo) {
            throw new RuntimeException('Composer archive contained an uninspectable entry.');
        }

        $normalizedPath = str_replace('\\', '/', $entry->getPathname());
        if (!str_starts_with($normalizedPath, $archivePrefix)) {
            throw new RuntimeException(sprintf('Composer archive returned an unexpected path %s.', $normalizedPath));
        }

        $relativePath = substr($normalizedPath, strlen($archivePrefix));
        if (
            $relativePath === ''
            || str_starts_with($relativePath, '/')
            || in_array('..', explode('/', $relativePath), true)
        ) {
            throw new RuntimeException(sprintf('Composer archive contained an unsafe path %s.', $relativePath));
        }

        if ($entry->isLink()) {
            throw new RuntimeException(sprintf('Composer archive contained symbolic link %s.', $relativePath));
        }

        $files[$relativePath] = $entry;
    }

    $requiredFiles = [
        'CHANGELOG.md',
        'LICENSE.md',
        'README.md',
        'bin/akashi',
        'composer.json',
        'docs/LICENSE_EXCEPTION.md',
    ];
    foreach ($requiredFiles as $requiredFile) {
        if (!array_key_exists($requiredFile, $files)) {
            throw new RuntimeException(sprintf('Composer archive omitted required file %s.', $requiredFile));
        }
    }

    $publicDocumentationRoot = realpath($projectRoot . '/docs/pages');
    if ($publicDocumentationRoot === false || !is_dir($publicDocumentationRoot)) {
        throw new RuntimeException('Public documentation source tree docs/pages does not exist.');
    }

    $publicDocumentationRoot = str_replace('\\', '/', $publicDocumentationRoot);
    $requiredPublicDocumentation = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($publicDocumentationRoot, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $entry) {
        if (!$entry instanceof SplFileInfo || !$entry->isFile()) {
            continue;
        }

        $sourcePath = str_replace('\\', '/', $entry->getPathname());
        $requiredPublicDocumentation[] = 'docs/pages/' . substr($sourcePath, strlen($publicDocumentationRoot) + 1);
    }

    if ($requiredPublicDocumentation === []) {
        throw new RuntimeException('Public documentation source tree docs/pages is empty.');
    }

    foreach ($requiredPublicDocumentation as $requiredFile) {
        if (!array_key_exists($requiredFile, $files)) {
            throw new RuntimeException(sprintf('Composer archive omitted public documentation file %s.', $requiredFile));
        }
    }

    $hasSourceFile = false;
    foreach (array_keys($files) as $path) {
        if (str_starts_with($path, 'src/')) {
            $hasSourceFile = true;
            break;
        }
    }

    if (!$hasSourceFile) {
        throw new RuntimeException('Composer archive omitted the source tree.');
    }

    $forbiddenPrefixes = [
        '.codex',
        '.github',
        'build',
        'coverage',
        'nix',
        'tests',
        'tmp',
        'tools',
        'vendor',
    ];
    foreach (array_keys($files) as $path) {
        foreach ($forbiddenPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                throw new RuntimeException(sprintf('Composer archive contained forbidden path %s.', $path));
            }
        }
    }

    if (DIRECTORY_SEPARATOR !== '\\' && ($files['bin/akashi']->getPerms() & 0o111) === 0) {
        throw new RuntimeException('Composer archive made bin/akashi non-executable.');
    }

    fwrite(STDOUT, sprintf("Composer package archive is valid (%d files).\n", count($files)));
} catch (Throwable $exception) {
    fwrite(STDERR, sprintf("Package check failed: %s\n", $exception->getMessage()));
    $exitCode = 1;
} finally {
    try {
        $removeTemporaryRoot();
    } catch (Throwable $exception) {
        fwrite(STDERR, sprintf("Package check cleanup failed: %s\n", $exception->getMessage()));
        $exitCode = 1;
    }
}

exit($exitCode);
