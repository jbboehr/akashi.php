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

namespace jbboehr\Akashi\Tests;

use PHPUnit\Framework\TestCase;

final class PackageMetadataTest extends TestCase
{
    public function testComposerMetadataIdentifiesThePackage(): void
    {
        $contents = file_get_contents(__DIR__ . '/../composer.json');
        self::assertNotFalse($contents);

        /**
         * @var array{
         *     name: string,
         *     type: string,
         *     license: string,
         *     autoload: array{'psr-4': array<string, string>},
         *     bin: list<string>
         * } $metadata
         */
        $metadata = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('jbboehr/akashi', $metadata['name']);
        self::assertSame('library', $metadata['type']);
        self::assertSame('AGPL-3.0-only WITH romic-exception', $metadata['license']);
        self::assertSame(['jbboehr\\Akashi\\' => 'src'], $metadata['autoload']['psr-4']);
        self::assertSame(['bin/akashi'], $metadata['bin']);
    }
}
