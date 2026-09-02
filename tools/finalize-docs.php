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

use jbboehr\Akashi\Tools\DocumentationSeo;

require __DIR__ . '/DocumentationSeo.php';

$projectRoot = dirname(__DIR__);
$outputRoot = $argv[1] ?? $projectRoot . '/build/docs';
$metadataPath = $argv[2] ?? $projectRoot . '/docs/seo.json';

try {
    DocumentationSeo::finalize($outputRoot, $metadataPath);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Documentation SEO finalization failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
