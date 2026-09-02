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

namespace jbboehr\Akashi\Tools;

final class DocumentationSeo
{
    private const NON_CONTENT_PAGES = [
        '404.html',
        'print.html',
        'toc.html',
    ];

    public static function finalize(string $outputRoot, string $metadataPath): void
    {
        $outputRoot = rtrim($outputRoot, '/\\');
        if (!is_dir($outputRoot)) {
            throw new \RuntimeException(sprintf('Documentation output directory %s does not exist.', $outputRoot));
        }

        $configuration = self::configuration($metadataPath);
        $configuredPages = array_keys($configuration['pages']);
        $builtPages = self::contentPages($outputRoot);
        $sortedConfiguredPages = $configuredPages;
        sort($sortedConfiguredPages);

        if ($sortedConfiguredPages !== $builtPages) {
            $missing = array_values(array_diff($builtPages, $sortedConfiguredPages));
            $extra = array_values(array_diff($sortedConfiguredPages, $builtPages));
            $details = [];

            if ([] !== $missing) {
                $details[] = 'missing metadata for ' . implode(', ', $missing);
            }
            if ([] !== $extra) {
                $details[] = 'metadata without a built page for ' . implode(', ', $extra);
            }

            throw new \RuntimeException('Documentation SEO metadata is incomplete: ' . implode('; ', $details) . '.');
        }

        $locations = [];
        foreach ($configuration['pages'] as $relativePath => $metadata) {
            $canonical = $configuration['base-url'] . ('index.html' === $relativePath ? '' : $relativePath);
            self::finalizePage($outputRoot . '/' . $relativePath, $metadata, $canonical);
            $locations[] = $canonical;
        }

        $sitemap = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];
        foreach ($locations as $location) {
            $sitemap[] = sprintf(
                '  <url><loc>%s</loc></url>',
                htmlspecialchars($location, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
            );
        }
        $sitemap[] = '</urlset>';

        if (false === file_put_contents($outputRoot . '/sitemap.xml', implode("\n", $sitemap) . "\n")) {
            throw new \RuntimeException('Unable to write the documentation sitemap.');
        }

        self::writeFinalizationMarker($outputRoot . '/index.html', $configuration['fingerprint']);
    }

    public static function isFinalized(string $outputRoot, string $metadataPath): bool
    {
        $outputRoot = rtrim($outputRoot, '/\\');
        $index = file_get_contents($outputRoot . '/index.html');
        $metadata = file_get_contents($metadataPath);

        if (false === $index || false === $metadata || !is_file($outputRoot . '/sitemap.xml')) {
            return false;
        }

        return str_contains($index, '<!-- akashi-seo:' . hash('sha256', $metadata) . ' -->');
    }

    /** @return array{base-url: non-empty-string, fingerprint: non-empty-string, pages: array<non-empty-string, array{title: non-empty-string, description: non-empty-string}>} */
    private static function configuration(string $metadataPath): array
    {
        $encoded = file_get_contents($metadataPath);
        if (false === $encoded) {
            throw new \RuntimeException(sprintf('Unable to read documentation SEO metadata from %s.', $metadataPath));
        }

        try {
            $decoded = json_decode($encoded, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('Documentation SEO metadata is not valid JSON.', 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new \RuntimeException('Documentation SEO metadata must be an object.');
        }

        $baseUrl = $decoded['base-url'] ?? null;
        if (!is_string($baseUrl)
            || !str_starts_with($baseUrl, 'https://')
            || !str_ends_with($baseUrl, '/')
            || false === filter_var($baseUrl, FILTER_VALIDATE_URL)
        ) {
            throw new \RuntimeException('Documentation SEO base-url must be an absolute HTTPS URL ending in /.');
        }

        $configuredPages = $decoded['pages'] ?? null;
        if (!is_array($configuredPages) || [] === $configuredPages) {
            throw new \RuntimeException('Documentation SEO pages must be a nonempty object.');
        }

        $pages = [];
        $titles = [];
        $descriptions = [];
        foreach ($configuredPages as $relativePath => $metadata) {
            if (!is_string($relativePath)
                || '' === $relativePath
                || str_starts_with($relativePath, '/')
                || str_contains($relativePath, '\\')
                || in_array('..', explode('/', $relativePath), true)
                || !str_ends_with($relativePath, '.html')
                || in_array($relativePath, self::NON_CONTENT_PAGES, true)
            ) {
                throw new \RuntimeException('Documentation SEO contains an invalid page path.');
            }
            if (!is_array($metadata)) {
                throw new \RuntimeException(sprintf('Documentation SEO metadata for %s must be an object.', $relativePath));
            }

            $title = $metadata['title'] ?? null;
            $description = $metadata['description'] ?? null;
            if (!is_string($title) || '' === trim($title)) {
                throw new \RuntimeException(sprintf('Documentation SEO title for %s must not be empty.', $relativePath));
            }
            if (!is_string($description) || '' === trim($description)) {
                throw new \RuntimeException(
                    sprintf('Documentation SEO description for %s must not be empty.', $relativePath),
                );
            }
            if (isset($titles[$title])) {
                throw new \RuntimeException(sprintf('Documentation SEO title is duplicated by %s.', $relativePath));
            }
            if (isset($descriptions[$description])) {
                throw new \RuntimeException(sprintf('Documentation SEO description is duplicated by %s.', $relativePath));
            }

            $titles[$title] = true;
            $descriptions[$description] = true;
            $pages[$relativePath] = [
                'title' => $title,
                'description' => $description,
            ];
        }

        return [
            'base-url' => $baseUrl,
            'fingerprint' => hash('sha256', $encoded),
            'pages' => $pages,
        ];
    }

    /** @return list<string> */
    private static function contentPages(string $outputRoot): array
    {
        $pages = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($outputRoot, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || 'html' !== $file->getExtension()) {
                continue;
            }

            $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($outputRoot) + 1));
            if (!in_array($relativePath, self::NON_CONTENT_PAGES, true)) {
                $pages[] = $relativePath;
            }
        }

        sort($pages);

        return $pages;
    }

    /** @param array{title: non-empty-string, description: non-empty-string} $metadata */
    private static function finalizePage(string $path, array $metadata, string $canonical): void
    {
        $html = file_get_contents($path);
        if (false === $html) {
            throw new \RuntimeException(sprintf('Unable to read built documentation page %s.', $path));
        }

        $html = self::replaceOne(
            $html,
            '/<title>.*?<\/title>/s',
            '<title>' . htmlspecialchars($metadata['title'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8') . '</title>',
            $path . ' title',
        );
        $html = preg_replace('/\R\h*<link rel="canonical" href="[^"]+">/', '', $html) ?? $html;
        $description = '<meta name="description" content="'
            . htmlspecialchars($metadata['description'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8')
            . '">';
        $html = self::replaceOne(
            $html,
            '/<meta name="description" content="[^"]*">/',
            $description . "\n        <link rel=\"canonical\" href=\""
                . htmlspecialchars($canonical, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8')
                . '">',
            $path . ' description',
        );

        if (false === file_put_contents($path, $html)) {
            throw new \RuntimeException(sprintf('Unable to write finalized documentation page %s.', $path));
        }
    }

    private static function replaceOne(string $subject, string $pattern, string $replacement, string $label): string
    {
        $count = preg_match_all($pattern, $subject);
        if (1 !== $count) {
            throw new \RuntimeException(sprintf('Built documentation %s must occur exactly once.', $label));
        }

        $result = preg_replace_callback($pattern, static fn(): string => $replacement, $subject);
        if (null === $result) {
            throw new \RuntimeException(sprintf('Unable to replace built documentation %s.', $label));
        }

        return $result;
    }

    private static function writeFinalizationMarker(string $indexPath, string $fingerprint): void
    {
        $html = file_get_contents($indexPath);
        if (false === $html) {
            throw new \RuntimeException('Unable to read the documentation index for finalization.');
        }

        $html = preg_replace('/\R\h*<!-- akashi-seo:[a-f0-9]{64} -->/', '', $html) ?? $html;
        $html = self::replaceOne(
            $html,
            '/<\/head>/',
            '<!-- akashi-seo:' . $fingerprint . ' -->' . "\n</head>",
            'index finalization marker',
        );

        if (false === file_put_contents($indexPath, $html)) {
            throw new \RuntimeException('Unable to mark the documentation index as finalized.');
        }
    }
}
