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

require_once __DIR__ . '/DocumentationSeo.php';

$projectRoot = dirname(__DIR__);
$outputRoot = $projectRoot . '/build/docs';
$metadataPath = $projectRoot . '/docs/seo.json';

try {
    if (!DocumentationSeo::isFinalized($outputRoot, $metadataPath)) {
        DocumentationSeo::finalize($outputRoot, $metadataPath);
    }
} catch (Throwable $exception) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Documentation SEO finalization failed: ', $exception->getMessage(), "\n";

    return true;
}

return false;
