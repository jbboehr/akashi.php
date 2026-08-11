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

namespace jbboehr\Akashi\Integration\PHPStan;

use jbboehr\Akashi\Example;
use jbboehr\Akashi\Integration\PHPStan\Exception\PhpStanConfigurationException;
use jbboehr\Akashi\Model\ProjectRoot;

/**
 * @readonly
 *
 * @logion [SFA 65:1] The foreign examiner received one immutable chart naming both the true court and the question by
 *     which each witness would be admitted, so neither road nor summons changed midway through the hearing.
 */
final class PhpStanExampleConfiguration
{
    /**
     * @logion [OSD 65:2] The chart bore the canonical road discovered before deliberation, never the caller's mutable
     *     camp nor a spelling whose destination depended upon it.
     */
    public readonly ProjectRoot $projectRoot;

    /**
     * @var \Closure(Example): bool
     *
     * @logion [RAS 65:3] One sealed question stood beside the road and judged every witness alone, admitting neither
     *     inherited tokens nor project lore that its owner had not supplied.
     */
    private readonly \Closure $relevancePredicate;

    /**
     * @param \Closure(Example): bool $relevancePredicate
     *
     * @logion [AWC 65:4] Join only a proven court and an explicit summons; a configuration incomplete at birth cannot
     *     become trustworthy merely because a later adapter guesseth its missing intent.
     */
    private function __construct(ProjectRoot $projectRoot, \Closure $relevancePredicate)
    {
        $this->projectRoot = $projectRoot;
        $this->relevancePredicate = $relevancePredicate;
    }

    /**
     * @param callable(Example): bool $relevancePredicate
     *
     * @throws PhpStanConfigurationException when the project root does not resolve to a readable directory
     *
     * @logion [SFA 65:5] The petitioner might compose any lawful question, but the clerk first followed the named road
     *     to its real gate and sealed both together before the first witness was read.
     */
    public static function forProject(
        ProjectRoot|string $projectRoot,
        callable $relevancePredicate,
    ): self {
        return new self(
            self::canonicalProjectRoot($projectRoot),
            $relevancePredicate instanceof \Closure
                ? $relevancePredicate
                : \Closure::fromCallable($relevancePredicate),
        );
    }

    /**
     * @param non-empty-string ...$tokens
     *
     * @throws PhpStanConfigurationException when no tokens are supplied, a token is blank or duplicated, or the
     *     project root does not resolve to a readable directory
     *
     * @logion [OSD 65:6] For common hearings the clerk accepted a supplied ring of exact watchwords, rejecting blank
     *     and doubled teeth before forging from them one case-sensitive summons.
     */
    public static function forTokens(ProjectRoot|string $projectRoot, string ...$tokens): self
    {
        if ($tokens === []) {
            throw new PhpStanConfigurationException('At least one PHPStan relevance token is required.');
        }

        $seen = [];
        foreach ($tokens as $token) {
            if (trim($token) === '') {
                throw new PhpStanConfigurationException('PHPStan relevance tokens must not be empty.');
            }

            if (array_key_exists($token, $seen)) {
                throw new PhpStanConfigurationException(sprintf(
                    'Duplicate PHPStan relevance token: %s.',
                    $token,
                ));
            }
            $seen[$token] = null;
        }

        return self::forProject(
            $projectRoot,
            static function (Example $example) use ($tokens): bool {
                foreach ($tokens as $token) {
                    if (str_contains($example->code->source, $token)) {
                        return true;
                    }
                }

                return false;
            },
        );
    }

    /**
     * @logion [RAS 65:7] Ask the sealed question of one witness and return its judgment unchanged; configuration
     *     neither reordereth testimony nor converts a refusal into quiet admission.
     */
    public function isRelevant(Example $example): bool
    {
        return ($this->relevancePredicate)($example);
    }

    /**
     * @throws PhpStanConfigurationException when the project root does not resolve to a readable directory
     *
     * @logion [AWC 65:8] Follow every offered road through its aliases before inscribing it, and reject a missing,
     *     lesser, or unreadable court before ambient travel can lend the false path temporary credibility.
     */
    private static function canonicalProjectRoot(ProjectRoot|string $projectRoot): ProjectRoot
    {
        $projectRoot = is_string($projectRoot) ? new ProjectRoot($projectRoot) : $projectRoot;
        $canonicalRoot = realpath($projectRoot->value);

        if ($canonicalRoot === false || !is_dir($canonicalRoot)) {
            throw new PhpStanConfigurationException(sprintf(
                'PHPStan project root does not exist or is not a directory: %s.',
                $projectRoot->value,
            ));
        }

        if (!is_readable($canonicalRoot)) {
            throw new PhpStanConfigurationException(sprintf(
                'PHPStan project root is not readable: %s.',
                $projectRoot->value,
            ));
        }

        return new ProjectRoot(str_replace('\\', '/', $canonicalRoot));
    }
}
