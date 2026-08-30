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

namespace jbboehr\Akashi\Tests\Model;

use jbboehr\Akashi\Model\AbsoluteFilePath;
use jbboehr\Akashi\Model\DocumentPath;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\MarkerId;
use jbboehr\Akashi\Model\MarkerName;
use jbboehr\Akashi\Model\PhpDocTagName;
use jbboehr\Akashi\Model\ProjectPath;
use jbboehr\Akashi\Model\ProjectRoot;
use jbboehr\Akashi\Model\RegionName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StringValueObjectTest extends TestCase
{
    #[DataProvider('stringValueProvider')]
    public function testProjectsValidatedValuesAsStrings(object $value, string $expected): void
    {
        self::assertInstanceOf(\Stringable::class, $value);
        self::assertSame($expected, (string) $value);
    }

    /**
     * @return iterable<string, array{object, string}>
     */
    public static function stringValueProvider(): iterable
    {
        yield 'absolute file path' => [new AbsoluteFilePath('/project/file.php/'), '/project/file.php'];
        yield 'document path' => [new DocumentPath('docs\\./guide.md'), 'docs/guide.md'];
        yield 'example ID' => [new ExampleId('example-one'), 'example-one'];
        yield 'language' => [new Language(' PHP '), 'php'];
        yield 'marker ID' => [new MarkerId('example-one'), 'example-one'];
        yield 'marker name' => [new MarkerName('akashi-example'), 'akashi-example'];
        yield 'PHPDoc tag name' => [new PhpDocTagName('akashi-example'), 'akashi-example'];
        yield 'project path' => [new ProjectPath('docs\\./guide.md'), 'docs/guide.md'];
        yield 'project root' => [new ProjectRoot('/project/'), '/project'];
        yield 'region name' => [new RegionName('example-one'), 'example-one'];
    }
}
