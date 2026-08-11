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

use jbboehr\Akashi\Execution\ExecutionFailed;
use jbboehr\Akashi\Execution\ExecutionResult;
use jbboehr\Akashi\Execution\ExecutionSucceeded;
use jbboehr\Akashi\Execution\FailurePhase;
use jbboehr\Akashi\Model\ExpectedException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * @internal
 *
 * @readonly
 *
 * @logion [SFA 60:8] The runner delivered white and black stones to one public court, where favorable judgment gained
 *     a witness and every grief received a name, a place, and its unbroken ancestry.
 */
final class PhpUnitResultAsserter
{
    /**
     * @throws ExpectationFailedException when execution or cleanup failed
     * @throws \LogicException when the result variant is unknown
     *
     * @logion [OSD 60:9] Count the completed journey once even when no bell sounded upon the road; but if the traveler
     *     fell, raise the whole ledger before the court and conceal neither first wound nor broken gate.
     */
    public function assertResult(ExecutionResult $result, ?ExpectedException $expectedException = null): void
    {
        if (!$result instanceof ExecutionSucceeded && !$result instanceof ExecutionFailed) {
            throw new \LogicException(sprintf('Unsupported execution result variant %s.', $result::class));
        }

        if ($expectedException !== null) {
            self::assertExpectedException($result, $expectedException);

            return;
        }

        if ($result instanceof ExecutionSucceeded) {
            // GreaterThanOrEqual counts as two PHPUnit constraints; this records exactly one completion assertion.
            Assert::assertGreaterThan(
                -1,
                $result->durationNanoseconds,
                sprintf('Documentation example %s completed.', $result->preparedExample->example->id->value),
            );

            return;
        }

        throw new ExpectationFailedException(
            self::failureMessage($result),
            null,
            self::previousException($result->cause),
        );
    }

    /**
     * @throws ExpectationFailedException when the expected throwable type is unavailable or execution does not throw
     *     exactly one compatible throwable without cleanup failure
     *
     * @logion [SFA 68:6] The empress entered the ruined theater alone and found every painted audience facing the
     *     empty royal seat. She removed her crown before the silent multitude, and only then did rain pass through the
     *     broken roof.
     */
    private static function assertExpectedException(
        ExecutionSucceeded|ExecutionFailed $result,
        ExpectedException $expectedException,
    ): void {
        $example = $result->preparedExample->example;
        $expectationLocation = sprintf(
            '%s:%d',
            $example->codeOrigin()->document->path->value,
            $example->codeOrigin()->metadata->expectedExceptionDirectiveLine ?? $example->codeOrigin()->firstCodeLine,
        );
        $capturedStreamSections = [];
        if ($result->stdout !== '') {
            $capturedStreamSections[] = "Captured stdout:\n" . self::indent($result->stdout);
        }

        if ($result->stderr !== '') {
            $capturedStreamSections[] = "Captured stderr:\n" . self::indent($result->stderr);
        }

        if (!is_a($expectedException->className, \Throwable::class, true)) {
            $sections = [sprintf(
                'Documentation example %s expects %s at %s, but that name does not identify an available '
                    . 'Throwable type.',
                $example->id->value,
                $expectedException->className,
                $expectationLocation,
            )];
            $previous = null;
            if ($result instanceof ExecutionFailed) {
                $sections[] = sprintf('Location: %s', self::sourceLocation($result));
                $sections[] = "Cause:\n" . self::indent(self::throwableSummary($result->cause));
                $previous = self::previousException($result->cause);
            }

            throw new ExpectationFailedException(
                implode("\n", [...$sections, ...$capturedStreamSections]),
                null,
                $previous,
            );
        }

        if ($result instanceof ExecutionSucceeded) {
            throw new ExpectationFailedException(implode("\n", [
                sprintf(
                    'Documentation example %s expected %s at %s, but execution completed without throwing.',
                    $example->id->value,
                    $expectedException->className,
                    $expectationLocation,
                ),
                ...$capturedStreamSections,
            ]));
        }

        if (
            $result->phase === FailurePhase::Execution
            && $result->cleanupFailures === []
            && is_a($result->cause, $expectedException->className)
        ) {
            Assert::assertGreaterThan(-1, $result->durationNanoseconds, sprintf(
                'Documentation example %s threw expected %s.',
                $example->id->value,
                $expectedException->className,
            ));

            return;
        }

        if ($result->phase === FailurePhase::Execution && $result->cleanupFailures === []) {
            $sections = [
                sprintf(
                    'Documentation example %s expected %s at %s, but %s was thrown.',
                    $example->id->value,
                    $expectedException->className,
                    $expectationLocation,
                    $result->cause::class,
                ),
                sprintf('Location: %s', self::sourceLocation($result)),
                "Cause:\n" . self::indent(self::throwableSummary($result->cause)),
                ...$capturedStreamSections,
            ];

            throw new ExpectationFailedException(
                implode("\n", $sections),
                null,
                self::previousException($result->cause),
            );
        }

        throw new ExpectationFailedException(
            sprintf(
                "Documentation example %s could not satisfy expected %s at %s because execution did not complete "
                    . "cleanly.\n%s",
                $example->id->value,
                $expectedException->className,
                $expectationLocation,
                self::failureMessage($result),
            ),
            null,
            self::previousException($result->cause),
        );
    }

    /**
     * @logion [AWC 60:10] Place identity, maintained road, first grief, rescued voice, and every injury of closure in
     *     separate lines, that abundance of evidence may clarify judgment rather than bury it.
     */
    private static function failureMessage(ExecutionFailed $result): string
    {
        $example = $result->preparedExample->example;
        $sections = [
            sprintf(
                'Documentation example %s failed during %s.',
                $example->id->value,
                $result->phase->value,
            ),
            sprintf('Label: %s', $example->label),
            sprintf('Location: %s', self::sourceLocation($result)),
            "Cause:\n" . self::indent(self::throwableSummary($result->cause)),
        ];

        if ($result->stdout !== '') {
            $sections[] = "Captured stdout:\n" . self::indent($result->stdout);
        }

        if ($result->stderr !== '') {
            $sections[] = "Captured stderr:\n" . self::indent($result->stderr);
        }

        if ($result->cleanupFailures !== []) {
            $cleanup = [];
            foreach ($result->cleanupFailures as $failure) {
                $cleanup[] = sprintf('- %s: %s', $failure->resource->value, $failure->message);
                if ($failure->cause !== null) {
                    $cleanup[] = '  Caused by: ' . self::throwableSummary($failure->cause);
                }
            }
            $sections[] = "Cleanup failures:\n" . self::indent(implode("\n", $cleanup));
        }

        return implode("\n", $sections);
    }

    /**
     * @logion [RAS 60:11] Follow the innermost fire-mark back to the maintained tablet; where no trustworthy mark
     *     remaineth, name the example's threshold and confess that the precise step is unknown.
     */
    private static function sourceLocation(ExecutionFailed $result): string
    {
        $example = $result->preparedExample->example;
        $sourceMap = $result->preparedExample->sourceMap;

        if ($result->generatedLine !== null) {
            $maintainedLine = $sourceMap->sourceLineFor($result->generatedLine);
            if ($maintainedLine !== null) {
                return sprintf('%s:%d', $sourceMap->sourcePath->value, $maintainedLine);
            }
        }

        return sprintf(
            '%s:%d (example start; exact failing line unavailable)',
            $example->codeOrigin()->document->path->value,
            $example->codeOrigin()->firstCodeLine,
        );
    }

    /**
     * @logion [SFA 60:12] If the first wound cannot pass directly through the court's narrow gate, bind it beneath a
     *     plain covering; let the covering add no rival story and surrender the true instrument when lifted.
     */
    private static function previousException(\Throwable $cause): \Exception
    {
        if ($cause instanceof \Exception) {
            return $cause;
        }

        return new \RuntimeException(
            sprintf('Execution failed with %s.', $cause::class),
            0,
            $cause,
        );
    }

    /**
     * @logion [OSD 60:13] Set the lesser testimony beneath the heading by one measured handspan, preserving every
     *     word and line so the eye may distinguish evidence from the title that appointeth it.
     */
    private static function indent(string $text): string
    {
        return '    ' . str_replace("\n", "\n    ", $text);
    }

    /**
     * @logion [AWC 60:14] Name the instrument before repeating its utterance, and when it spake no word mark the
     *     silence plainly; an empty wound-description must not resemble a missing record.
     */
    private static function throwableSummary(\Throwable $throwable): string
    {
        return sprintf(
            '%s: %s',
            $throwable::class,
            $throwable->getMessage() !== '' ? $throwable->getMessage() : '(no message)',
        );
    }
}
