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

namespace jbboehr\Akashi\Tests\Documentation;

use jbboehr\Akashi\Tools\DocumentationSeo;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/tools/DocumentationSeo.php';

final class DocumentationSeoTest extends TestCase
{
    private string $workspace;

    protected function setUp(): void
    {
        $this->workspace = rtrim(sys_get_temp_dir(), '/\\') . '/akashi-documentation-seo-' . bin2hex(random_bytes(16));
        self::assertTrue(mkdir($this->workspace . '/guide', 0o700, true));

        $this->writePage('index.html', 'Old home', 'Old home description');
        $this->writePage('guide/setup.html', 'Old setup', 'Old setup description');
        $this->writePage('404.html', 'Not found', 'Not found');
        $this->writePage('print.html', 'Print', 'Print');
        $this->writePage('toc.html', 'Contents', 'Contents');
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

        foreach ($iterator as $entry) {
            self::assertInstanceOf(\SplFileInfo::class, $entry);

            if ($entry->isDir()) {
                self::assertTrue(rmdir($entry->getPathname()));
            } else {
                self::assertTrue(unlink($entry->getPathname()));
            }
        }

        self::assertTrue(rmdir($this->workspace));
    }

    public function testFinalizesEveryContentPageAndWritesCanonicalSitemapEntries(): void
    {
        $metadataPath = $this->writeMetadata([
            'index.html' => [
                'title' => 'Akashi documentation',
                'description' => 'Test PHP examples from maintained documentation.',
            ],
            'guide/setup.html' => [
                'title' => 'Set up Akashi',
                'description' => 'Install Akashi and connect PHP examples to PHPUnit.',
            ],
        ]);

        DocumentationSeo::finalize($this->workspace, $metadataPath);

        $index = file_get_contents($this->workspace . '/index.html');
        self::assertIsString($index);
        self::assertMatchesRegularExpression('/<!-- akashi-seo:[a-f0-9]{64} -->/', $index);
        self::assertSame(
            [
                'title' => 'Akashi documentation',
                'description' => 'Test PHP examples from maintained documentation.',
                'canonical' => 'https://example.com/docs/',
            ],
            $this->pageMetadata('index.html'),
        );
        self::assertSame(
            [
                'title' => 'Set up Akashi',
                'description' => 'Install Akashi and connect PHP examples to PHPUnit.',
                'canonical' => 'https://example.com/docs/guide/setup.html',
            ],
            $this->pageMetadata('guide/setup.html'),
        );
        self::assertStringEqualsFile(
            $this->workspace . '/sitemap.xml',
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
                  <url><loc>https://example.com/docs/</loc></url>
                  <url><loc>https://example.com/docs/guide/setup.html</loc></url>
                </urlset>
                XML
                . "\n",
        );
    }

    public function testRejectsAnUnconfiguredContentPage(): void
    {
        $metadataPath = $this->writeMetadata([
            'index.html' => [
                'title' => 'Akashi documentation',
                'description' => 'Test PHP examples from maintained documentation.',
            ],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('guide/setup.html');

        DocumentationSeo::finalize($this->workspace, $metadataPath);
    }

    public function testDetectsOutputRebuiltAfterFinalization(): void
    {
        $metadataPath = $this->writeMetadata([
            'index.html' => [
                'title' => 'Akashi documentation',
                'description' => 'Test PHP examples from maintained documentation.',
            ],
            'guide/setup.html' => [
                'title' => 'Set up Akashi',
                'description' => 'Install Akashi and connect PHP examples to PHPUnit.',
            ],
        ]);
        DocumentationSeo::finalize($this->workspace, $metadataPath);
        self::assertTrue(DocumentationSeo::isFinalized($this->workspace, $metadataPath));

        $this->writePage('index.html', 'Old home', 'Old home description');

        self::assertFalse(DocumentationSeo::isFinalized($this->workspace, $metadataPath));
        DocumentationSeo::finalize($this->workspace, $metadataPath);
        self::assertTrue(DocumentationSeo::isFinalized($this->workspace, $metadataPath));

        $this->writeMetadata([
            'index.html' => [
                'title' => 'Akashi documentation updated',
                'description' => 'Test maintained PHP documentation examples.',
            ],
            'guide/setup.html' => [
                'title' => 'Set up Akashi today',
                'description' => 'Install Akashi and connect examples to PHPUnit.',
            ],
        ]);
        self::assertFalse(DocumentationSeo::isFinalized($this->workspace, $metadataPath));
    }

    /** @param array<string, array{title: string, description: string}> $pages */
    private function writeMetadata(array $pages): string
    {
        $path = $this->workspace . '/seo.json';
        $encoded = json_encode(
            [
                'base-url' => 'https://example.com/docs/',
                'pages' => $pages,
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
        self::assertNotFalse(file_put_contents($path, $encoded . "\n"));

        return $path;
    }

    private function writePage(string $relativePath, string $title, string $description): void
    {
        $html = sprintf(
            '<!DOCTYPE html><html><head><title>%s</title><meta name="description" content="%s"></head>'
                . '<body><main><h1>Page</h1></main></body></html>',
            $title,
            $description,
        );
        self::assertNotFalse(file_put_contents($this->workspace . '/' . $relativePath, $html));
    }

    /** @return array{title: string, description: string, canonical: string} */
    private function pageMetadata(string $relativePath): array
    {
        $document = new \DOMDocument();
        self::assertTrue($document->loadHTMLFile($this->workspace . '/' . $relativePath));
        $xpath = new \DOMXPath($document);

        return [
            'title' => $this->xpathString($xpath, 'string(//title)'),
            'description' => $this->xpathString($xpath, 'string(//meta[@name="description"]/@content)'),
            'canonical' => $this->xpathString($xpath, 'string(//link[@rel="canonical"]/@href)'),
        ];
    }

    private function xpathString(\DOMXPath $xpath, string $expression): string
    {
        $value = $xpath->evaluate($expression);
        self::assertIsString($value);

        return trim($value);
    }
}
