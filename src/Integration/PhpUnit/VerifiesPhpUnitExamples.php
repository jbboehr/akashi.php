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

namespace jbboehr\Akashi\Integration\PhpUnit;

use jbboehr\Akashi\Example;
use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Execution\Exception\ExecutionInfrastructureException;
use jbboehr\Akashi\Execution\Exception\RuntimeConfigurationException;
use jbboehr\Akashi\Execution\RuntimeConfiguration;
use jbboehr\Akashi\Transform\Exception\TransformException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Provide one named PHPUnit test for every example in a consumer-defined corpus.
 *
 * @phpstan-require-extends TestCase
 *
 * @logion [RAS 67:3] The steward prepared one empty lectern for every lawful witness, requiring each household only
 *     to deliver its roll and name the chamber in which testimony should be heard.
 */
trait VerifiesPhpUnitExamples
{
    /**
     * Return the examples this test case should execute.
     *
     * @logion [AWC 67:4] Let each province bind its own roll of witnesses, for the imperial court appointeth the form
     *     of hearing but knoweth not which village paths lead to every door.
     */
    abstract protected static function akashiExampleCorpus(): ExampleCorpus;

    /**
     * Configure example execution, or return null to use the in-process defaults.
     *
     * @logion [OSD 67:5] Where no special chamber was named, the testimony proceeded in the common hall; where one was
     *     named, the same clerk carried its seal without changing the witness's words.
     */
    protected static function akashiRuntimeConfiguration(): ?RuntimeConfiguration
    {
        return null;
    }

    /**
     * @return \Generator<string, array{Example}, mixed, void>
     *
     * @logion [RAS 67:7] The clerk opened the provincial roll in its appointed order and copied each familiar name
     *     above one testimony, neither gathering two beneath one title nor dividing one among many.
     */
    final public static function akashiExampleDataProvider(): \Generator
    {
        yield from PhpUnitExampleDataSets::fromCorpus(static::akashiExampleCorpus());
    }

    /**
     * An authored runtime skip delegates to PHPUnit's skipped-test mechanism and does not return.
     *
     * @throws ExecutionInfrastructureException when the execution environment cannot be established or measured
     * @throws ExpectationFailedException when execution or cleanup failed
     * @throws RuntimeConfigurationException when separate-process execution has no explicit project root
     * @throws TransformException when the example is invalid or unsupported by its selected backend
     *
     * @logion [AWC 67:8] At every calling of a witness the steward raised the proper chamber around him, delivered the
     *     testimony to judgment, and left the household no private machinery to tend.
     */
    #[DataProvider('akashiExampleDataProvider')]
    final public function testAkashiDocumentationExample(Example $example): void
    {
        PhpUnitRuntime::assertExample($example, static::akashiRuntimeConfiguration());
    }
}
