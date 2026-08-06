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
use jbboehr\Akashi\Execution\InProcess\InProcessExecutor;
use jbboehr\Akashi\Model\Directive;
use jbboehr\Akashi\Transform\Exception\TransformException;
use jbboehr\Akashi\Transform\Exception\UnsupportedExampleException;
use jbboehr\Akashi\Transform\InProcessTransformer;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * @logion [OSD 60:19] The petitioner named one witness at the outer door; unseen stewards prepared the chamber, heard
 *     the testimony, and returned one public judgment without requiring him to traverse each office.
 */
final readonly class PhpUnitRuntime
{
    /**
     * @throws ExecutionInfrastructureException when the in-process environment cannot be established or measured
     * @throws ExpectationFailedException when execution or cleanup failed
     * @throws TransformException when the example is invalid or unsupported in-process
     *
     * @logion [AWC 60:20] Send the witness by the road appointed upon its tablet; where that road is not yet opened,
     *     confess the closed gate with name and place rather than turning the traveler toward a more dangerous path.
     */
    public static function assertExample(Example $example): void
    {
        if ($example->directives->contains(Directive::SeparateProcess)) {
            throw new UnsupportedExampleException(sprintf(
                'Example %s at %s:%d requests separate-process execution, but that backend is not implemented.',
                $example->id->value,
                $example->document->path->value,
                $example->location->metadata->separateProcessDirectiveLine ?? $example->location->firstCodeLine,
            ));
        }

        $preparedExample = (new InProcessTransformer())->transform($example);
        $result = (new InProcessExecutor())->execute($preparedExample);
        (new PhpUnitResultAsserter())->assertResult($result);
    }
}
