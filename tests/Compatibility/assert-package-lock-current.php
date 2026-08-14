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

if ($argc !== 3) {
    fwrite(STDERR, "Usage: assert-package-lock-current PACKAGE_COMPOSER_JSON CONSUMER_COMPOSER_LOCK\n");
    exit(2);
}

/**
 * @return array<string, mixed>
 */
$decode = static function (string $path): array {
    $contents = file_get_contents($path);
    if (!is_string($contents)) {
        throw new RuntimeException(sprintf('Unable to read %s.', $path));
    }

    $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($decoded)) {
        throw new RuntimeException(sprintf('%s did not contain a JSON object.', $path));
    }

    return $decoded;
};

/**
 * @param array<string, mixed> $lock
 *
 * @return list<array<string, mixed>>
 */
$packageList = static function (array $lock, string $field, string $path): array {
    $value = $lock[$field] ?? [];
    if (!is_array($value) || !array_is_list($value)) {
        throw new RuntimeException(sprintf('%s field %s must be a package list.', $path, $field));
    }

    $packages = [];
    foreach ($value as $index => $candidate) {
        if (!is_array($candidate) || array_is_list($candidate)) {
            throw new RuntimeException(sprintf('%s field %s[%d] must be a package object.', $path, $field, $index));
        }

        foreach (array_keys($candidate) as $key) {
            if (!is_string($key)) {
                throw new RuntimeException(sprintf(
                    '%s field %s[%d] contains a non-string property name.',
                    $path,
                    $field,
                    $index,
                ));
            }
        }

        /** @var array<string, mixed> $candidate */
        $packages[] = $candidate;
    }

    return $packages;
};

$canonicalize = static function (mixed &$value) use (&$canonicalize): void {
    if (!is_array($value)) {
        return;
    }

    foreach ($value as &$member) {
        $canonicalize($member);
    }
    unset($member);

    if (!array_is_list($value)) {
        ksort($value);
    }
};

$packagePath = $argv[1];
$lockPath = $argv[2];

try {
    $package = $decode($packagePath);
    $lock = $decode($lockPath);
    $packageName = $package['name'] ?? null;
    if (!is_string($packageName) || $packageName === '') {
        throw new RuntimeException(sprintf('%s has no package name.', $packagePath));
    }

    $lockedPackages = [
        ...$packageList($lock, 'packages', $lockPath),
        ...$packageList($lock, 'packages-dev', $lockPath),
    ];
    $matches = [];
    foreach ($lockedPackages as $candidate) {
        if (($candidate['name'] ?? null) === $packageName) {
            $matches[] = $candidate;
        }
    }
    if (count($matches) !== 1) {
        throw new RuntimeException(sprintf(
            '%s must contain exactly one lock entry for %s; found %d.',
            $lockPath,
            $packageName,
            count($matches),
        ));
    }

    $metadataFields = [
        'name',
        'type',
        'require',
        'conflict',
        'provide',
        'replace',
        'autoload',
        'bin',
        'include-path',
        'target-dir',
        'extra',
    ];
    $currentMetadata = [];
    $lockedMetadata = [];
    foreach ($metadataFields as $field) {
        if (array_key_exists($field, $package)) {
            $currentMetadata[$field] = $package[$field];
        }
        if (array_key_exists($field, $matches[0])) {
            $lockedMetadata[$field] = $matches[0][$field];
        }
    }

    $canonicalCurrentMetadata = $currentMetadata;
    $canonicalLockedMetadata = $lockedMetadata;
    $canonicalize($canonicalCurrentMetadata);
    $canonicalize($canonicalLockedMetadata);
    if ($canonicalCurrentMetadata !== $canonicalLockedMetadata) {
        $changedFields = [];
        foreach ($metadataFields as $field) {
            if (($currentMetadata[$field] ?? null) !== ($lockedMetadata[$field] ?? null)) {
                $changedFields[] = $field;
            }
        }

        throw new RuntimeException(sprintf(
            '%s has stale %s metadata for %s; regenerate the consumer lock.',
            $lockPath,
            implode(', ', $changedFields),
            $packageName,
        ));
    }
} catch (Throwable $exception) {
    fwrite(STDERR, sprintf("Consumer lock validation failed: %s\n", $exception->getMessage()));
    exit(1);
}
