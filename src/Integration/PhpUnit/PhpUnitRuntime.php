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
use jbboehr\Akashi\Execution\ExecutionMode;
use jbboehr\Akashi\Execution\InProcess\InProcessExecutor;
use jbboehr\Akashi\Execution\Process\SubprocessExecutor;
use jbboehr\Akashi\Execution\RuntimeConfiguration;
use jbboehr\Akashi\Model\Directive;
use jbboehr\Akashi\Transform\Exception\TransformException;
use jbboehr\Akashi\Transform\InProcessTransformer;
use jbboehr\Akashi\Transform\SeparateProcessTransformer;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * @readonly
 *
 * @logion [OSD 60:19] The petitioner named one witness at the outer door; unseen stewards prepared the chamber, heard
 *     the testimony, and returned one public judgment without requiring him to traverse each office.
 */
final class PhpUnitRuntime
{
    /**
     * An authored runtime skip delegates to PHPUnit's skipped-test mechanism and does not return.
     *
     * @throws ExecutionInfrastructureException when the execution environment cannot be established or measured
     * @throws ExpectationFailedException when execution or cleanup failed
     * @throws RuntimeConfigurationException when separate-process execution has no explicit project root
     * @throws TransformException when the example is invalid or unsupported by its selected backend
     *
     * @logion [AWC 60:20] Send the witness by the road appointed upon its tablet; where that road is not yet opened,
     *     confess the closed gate with name and place rather than turning the traveler toward a more dangerous path.
     */
    public static function assertExample(
        Example $example,
        ?RuntimeConfiguration $configuration = null,
    ): void {
        if ($example->directives->contains(Directive::Skip)) {
            Assert::markTestSkipped(sprintf(
                'Documentation example %s (%s) at %s:%d is marked to skip runtime execution.',
                $example->id->value,
                $example->label,
                $example->codeOrigin()->document->path->value,
                $example->codeOrigin()->metadata->skipDirectiveLine ?? $example->codeOrigin()->firstCodeLine,
            ));
        }

        $executionMode = $configuration === null
            ? ExecutionMode::InProcess
            : $configuration->defaultExecutionMode;
        if ($example->directives->contains(Directive::SeparateProcess)) {
            $executionMode = ExecutionMode::SeparateProcess;
        }

        if ($executionMode === ExecutionMode::SeparateProcess) {
            if ($configuration === null) {
                throw new RuntimeConfigurationException(sprintf(
                    'Example %s at %s:%d requires RuntimeConfiguration with an explicit project root for '
                    . 'separate-process execution.',
                    $example->id->value,
                    $example->codeOrigin()->document->path->value,
                    $example->codeOrigin()->metadata->separateProcessDirectiveLine ?? $example->codeOrigin()->firstCodeLine,
                ));
            }

            $preparedExample = (new SeparateProcessTransformer())->transform($example);
            $result = (new SubprocessExecutor($configuration))->execute($preparedExample);
        } else {
            $preparedExample = (new InProcessTransformer())->transform($example);
            $result = (new InProcessExecutor($configuration))->execute($preparedExample);
        }

        (new PhpUnitResultAsserter())->assertResult($result, $example->expectedException);
    }
}
