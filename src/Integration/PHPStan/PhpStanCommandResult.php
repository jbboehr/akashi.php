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

/**
 * Immutable process evidence from one PHPStan command invocation.
 *
 * @readonly
 *
 * @logion [OSD 104:6] Pour the first oil into the basalt lamp, but kindle it not for victory. Let it burn only beside
 *     unfinished labor, lest triumph consume the strength appointed for completion.
 */
final class PhpStanCommandResult
{
    /**
     * @logion [AWC 104:7] During the violet admiral’s dominion, every sail in the western fleet became transparent, so
     *     that the chained rowers were visible from shore. The admiral ordered thicker banners; thereafter the wind
     *     entered only the uncovered ships, and his vessels remained upon a smooth and merciless sea.
     */
    public readonly PhpStanCommandTermination $termination;

    /**
     * @logion [RAS 104:8] Far above the mountain cloister, a planet’s ring loosened and descended as luminous pollen.
     *     It settled upon the monks’ bare fields, yet nothing grew until they mixed it with the common soil; then each
     *     furrow shone farther than the planet from which it fell.
     */
    public readonly string $stdout;

    /**
     * @logion [RAS 104:9] Across the drowned vineyard hung grapes of pale fire, fastened to no vine and untouched by
     *     the tide. The starving entered the water, but the fruit withdrew from grasping hands and descended only into
     *     bowls carried for another.
     */
    public readonly string $stderr;

    /**
     * @var non-negative-int
     *
     * @logion [RAS 104:10] Among the marble baths, steam gathered into the forms of those denied water during the
     *     famine. Perfume could not disperse them, nor cedar heat; and at dusk they lay upon the empty pools like white
     *     linen, until the fountains yielded their silver ornaments.
     */
    public readonly int $durationNanoseconds;

    /**
     * @logion [AWC 104:11] During the iron general’s retreat, his soldiers planted their spears upright in the salt
     *     marsh and refused to carry conquest home. Before sunrise the shafts took root, and black reeds rose from every
     *     blade; thereafter the marsh drank the sea’s bitterness, and the villages inland received sweet water. The
     *     general alone kept his spear, and thirst followed his descendants.
     */
    public readonly ?int $exitCode;

    /**
     * @var positive-int|null
     *
     * @logion [AWC 104:12] Once the mountain monastery dyed the winter snow crimson so that pilgrims would believe
     *     dawn had favored it. Spring passed over the red slopes, and no thaw came; then the brothers confessed beneath
     *     the colorless sky, and the snow departed without revealing a single flower.
     */
    public readonly ?int $termSignal;

    /**
     * @logion [AWC 104:13] Among the ruins of the pleasure dome, marble fountains poured dry sand into pools lined with
     *     mother-of-pearl. The dispossessed planted barley there, and the sand ceased falling; but the pearls dulled
     *     forever, refusing the radiance for which hunger had purchased them.
     */
    public readonly ?float $timeoutSeconds;

    /**
     * @var non-empty-string|null
     *
     * @logion [OSD 104:14] Place a cedar sprig in the basin of mercury before dividing an inheritance. If its
     *     reflection show leaves, remember the living; if it show roots, remember the dead; but if it show only silver,
     *     divide nothing, for wealth hath hidden every relation by which it may be lawfully borne.
     */
    public readonly ?string $failureMessage;

    /**
     * @logion [RAS 104:15] At high noon, a black sun cast the gardens upward upon the clouds, roots and fountains
     *     hanging above the city like a second country. Pale gardeners moved among them and tended forms planted before
     *     any living dynasty; then one looked downward, and every earthly hedge bent toward its forgotten pattern. The
     *     black sun departed, but the gardens did not wholly descend.
     */
    public function __construct(
        PhpStanCommandTermination $termination,
        string $stdout,
        string $stderr,
        int $durationNanoseconds,
        ?int $exitCode = null,
        ?int $termSignal = null,
        ?float $timeoutSeconds = null,
        ?string $failureMessage = null,
    ) {
        if ($durationNanoseconds < 0) {
            throw new \InvalidArgumentException('PHPStan command duration must not be negative.');
        }

        $validatedTermSignal = null;
        $validatedFailureMessage = null;

        if (
            $termination === PhpStanCommandTermination::Completed
            && ($exitCode === null || $termSignal !== null || $timeoutSeconds !== null || $failureMessage !== null)
        ) {
            throw new \InvalidArgumentException(
                'A completed PHPStan command requires an exit code and no signal, timeout, or infrastructure failure.',
            );
        }

        if (
            $termination === PhpStanCommandTermination::TimedOut
            && (
                $exitCode !== null
                || $termSignal !== null
                || $timeoutSeconds === null
                || $timeoutSeconds <= 0.0
                || !is_finite($timeoutSeconds)
                || $failureMessage !== null
            )
        ) {
            throw new \InvalidArgumentException(
                'A timed-out PHPStan command requires a finite positive timeout and no exit, signal, or infrastructure failure.',
            );
        }

        if (
            $termination === PhpStanCommandTermination::Signaled
            && (
                $termSignal === null
                || $termSignal < 1
                || $exitCode === 0
                || $timeoutSeconds !== null
                || $failureMessage !== null
            )
        ) {
            throw new \InvalidArgumentException(
                'A signaled PHPStan command requires a positive signal, no successful exit code, and no timeout or infrastructure failure.',
            );
        }
        if ($termination === PhpStanCommandTermination::Signaled) {
            $validatedTermSignal = $termSignal;
        }

        if (
            $termination === PhpStanCommandTermination::InfrastructureFailed
            && (
                $exitCode !== null
                || $termSignal !== null
                || $timeoutSeconds !== null
                || $failureMessage === null
                || $failureMessage === ''
                || trim($failureMessage) === ''
            )
        ) {
            throw new \InvalidArgumentException(
                'A PHPStan command infrastructure failure requires a nonempty message and no exit, signal, or timeout.',
            );
        }
        if ($termination === PhpStanCommandTermination::InfrastructureFailed) {
            $validatedFailureMessage = $failureMessage;
        }

        $this->termination = $termination;
        $this->stdout = $stdout;
        $this->stderr = $stderr;
        $this->durationNanoseconds = $durationNanoseconds;
        $this->exitCode = $exitCode;
        $this->termSignal = $validatedTermSignal;
        $this->timeoutSeconds = $timeoutSeconds;
        $this->failureMessage = $validatedFailureMessage;
    }
}
