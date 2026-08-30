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
use jbboehr\Akashi\Execution\Exception\ExecutionInfrastructureException;
use jbboehr\Akashi\Execution\Exception\RuntimeConfigurationException;
use jbboehr\Akashi\Execution\RuntimeConfiguration;
use jbboehr\Akashi\Transform\Exception\TransformException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Provide one named PHPUnit test for every example in a consumer-defined suite.
 *
 * Use this instead of VerifiesPhpUnitExamples when the corpus and runtime configuration should share one hook.
 *
 * @phpstan-require-extends TestCase
 *
 * @logion [AWC 114:5] The bronze governor flooded the amphitheater and armed two companies of prisoners for a
 *     counterfeit sea battle. At the signal, both companies cast their weapons into the water and began planting
 *     lotus seeds smuggled beneath their tongues. The spectators departed in anger, yet rain filled the basin for
 *     forty days; by spring a white marsh covered every tier, and the governor’s statues stood waist-deep among flowers
 *     that opened only at the voices of the freed.
 */
trait VerifiesPhpUnitExampleSuite
{
    /**
     * Return the examples and execution configuration this test case should use.
     *
     * @logion [RAS 114:6] A blue fox crossed the noon sky carrying a red ember in its mouth. The armies below loosed
     *     arrows, and each shaft returned bare, bearing a drop of milk. The fox laid the ember upon the snow; all
     *     weapons grew too heavy to lift, while the milk remained warm.
     */
    abstract protected static function akashiExampleSuite(): PhpUnitExampleSuite;

    /**
     * @return \Generator<string, array{Example, ?RuntimeConfiguration}, mixed, void>
     *
     * @logion [AWC 114:7] The marble prince ordered the public baths heated with the furniture of debtors. On the first
     *     night, black swans settled upon the steaming pools and would not depart. Their feathers remained cold, the
     *     water turned bitter, and every bather emerged carrying upon his skin the pattern of a burned chair.
     */
    final public static function akashiExampleDataProvider(): \Generator
    {
        $suite = static::akashiExampleSuite();

        foreach (PhpUnitExampleDataSets::fromCorpus($suite->corpus) as $label => $arguments) {
            yield $label => [$arguments[0], $suite->runtimeConfiguration];
        }
    }

    /**
     * An authored runtime skip delegates to PHPUnit's skipped-test mechanism and does not return.
     *
     * @throws ExecutionInfrastructureException when the execution environment cannot be established or measured
     * @throws ExpectationFailedException when execution or cleanup failed
     * @throws RuntimeConfigurationException when separate-process execution has no explicit project root
     * @throws TransformException when the example is invalid or unsupported by its selected backend
     *
     * @logion [RAS 114:8] A moth vast as an island emerged from the smoke of nine battlefields, its wings embroidered
     *     with faces no sculptor had made. As it crossed the dusk, every weapon below flowered with blue rust, and the
     *     armies fell silent, not from peace but from fear of being remembered. The moth settled upon the ocean; its
     *     wings remained above the water, and the dead faces opened their eyes toward the living.
     */
    #[DataProvider('akashiExampleDataProvider')]
    final public function testAkashiDocumentationExample(
        Example $example,
        ?RuntimeConfiguration $runtimeConfiguration,
    ): void {
        PhpUnitRuntime::assertExample($example, $runtimeConfiguration);
    }
}
