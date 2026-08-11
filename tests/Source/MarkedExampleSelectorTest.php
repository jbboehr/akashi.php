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

namespace jbboehr\Akashi\Tests\Source;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Example;
use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\InvalidMarkerException;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\MarkerId;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use jbboehr\Akashi\Source\Exception\MarkerNotFoundException;
use jbboehr\Akashi\Source\MarkedExampleSelector;
use PHPUnit\Framework\TestCase;

final class MarkedExampleSelectorTest extends TestCase
{
    private MarkedExampleSelector $selector;
    private ExampleCorpus $corpus;

    protected function setUp(): void
    {
        $this->selector = new MarkedExampleSelector();
        $this->corpus = new ExampleCorpus(
            $this->example('example-a-01', 1),
            $this->example('example-a-02', 2, 'first'),
            $this->example('example-a-03', 3, 'second'),
        );
    }

    public function testSelectsAnExampleUsingAStringOrTypedMarkerId(): void
    {
        self::assertSame('example-a-02', $this->selector->select($this->corpus, 'first')->id->value);
        self::assertSame(
            'example-a-03',
            $this->selector->select($this->corpus, new MarkerId('second'))->id->value,
        );
    }

    public function testRejectsAnInvalidMarkerIdBeforeSelection(): void
    {
        $this->expectException(InvalidMarkerException::class);
        $this->expectExceptionMessage('Marker ID must use lowercase kebab-case.');

        $this->selector->select($this->corpus, 'Invalid_ID');
    }

    public function testRejectsAMissingMarkerId(): void
    {
        $this->expectException(MarkerNotFoundException::class);
        $this->expectExceptionMessage('Marker ID missing was not found in the example corpus.');

        $this->selector->select($this->corpus, 'missing');
    }

    /**
     * @param positive-int $ordinal
     */
    private function example(string $id, int $ordinal, ?string $markerId = null): Example
    {
        $contents = "```php\necho 1;\n```\n";

        return Example::fromInline(
            id: new ExampleId($id),
            label: sprintf('docs/a.md PHP example %d', $ordinal),
            document: new Document('docs/a.md', $contents),
            location: new SourceLocation(1, 2, 2, 3, new SourceSpan(0, 19), new SourceSpan(7, 15)),
            language: new Language('php'),
            code: new ExampleCode("echo 1;\n"),
            fence: new FenceMetadata('php', '`', 3, 0),
            ordinal: $ordinal,
            explicitMarkerId: $markerId === null ? null : new MarkerId($markerId),
        );
    }
}
