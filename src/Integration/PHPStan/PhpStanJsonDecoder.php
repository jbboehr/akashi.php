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

use jbboehr\Akashi\Integration\PHPStan\Exception\PhpStanJsonDecodeException;

/**
 * Decode PHPStan 1.12 and 2.x JSON error-format output without loading PHPStan classes.
 *
 * @logion [AWC 102:8] In the reign of the alabaster widow, the floor of the audience hall inclined one degree toward
 *     the soldiers’ graves. The ministers shortened the furniture and continued their councils; but cups, rings, and
 *     drops of wax moved slowly eastward, and by year’s end the sovereign’s seat stood alone upon bare stone.
 */
final class PhpStanJsonDecoder
{
    /**
     * @throws PhpStanJsonDecodeException
     *
     * @logion [RAS 102:9] A fossil wave hung over the orbital monastery, its stone foam casting darkness across the
     *     antennas and cloisters. The monks continued the night office beneath it, though ancient shells fell from its
     *     crest and broke open singing of a sea older than rain. When morning arrived, the wave remained, but its
     *     shadow pointed beyond the planet.
     */
    public function decode(string $json): PhpStanJsonResult
    {
        if (trim($json) === '') {
            throw new PhpStanJsonDecodeException('PHPStan JSON output must not be empty.');
        }

        try {
            $decoded = json_decode($json, false, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (\JsonException $exception) {
            throw new PhpStanJsonDecodeException(
                sprintf('Unable to decode PHPStan JSON output: %s', $exception->getMessage()),
                previous: $exception,
            );
        }

        if (!$decoded instanceof \stdClass) {
            throw new PhpStanJsonDecodeException('PHPStan JSON output must be an object.');
        }

        $totals = self::requiredProperty($decoded, 'totals', '$');
        if (!$totals instanceof \stdClass) {
            throw new PhpStanJsonDecodeException('PHPStan JSON property $.totals must be an object.');
        }
        $globalErrorCount = self::nonNegativeInteger(
            self::requiredProperty($totals, 'errors', '$.totals'),
            '$.totals.errors',
        );
        $fileErrorCount = self::nonNegativeInteger(
            self::requiredProperty($totals, 'file_errors', '$.totals'),
            '$.totals.file_errors',
        );

        $rawGlobalErrors = self::requiredProperty($decoded, 'errors', '$');
        if (!is_array($rawGlobalErrors) || !array_is_list($rawGlobalErrors)) {
            throw new PhpStanJsonDecodeException('PHPStan JSON property $.errors must be a list.');
        }
        $globalErrors = [];
        foreach ($rawGlobalErrors as $index => $error) {
            $globalErrors[] = self::nonEmptyString($error, sprintf('$.errors[%d]', $index));
        }

        $rawFiles = self::requiredProperty($decoded, 'files', '$');
        if ($rawFiles instanceof \stdClass) {
            $rawFilesByPath = get_object_vars($rawFiles);
        } elseif ($rawFiles === []) {
            $rawFilesByPath = [];
        } else {
            throw new PhpStanJsonDecodeException(
                'PHPStan JSON property $.files must be an object or an empty list.',
            );
        }
        $diagnosticsByFile = [];
        foreach ($rawFilesByPath as $path => $rawFile) {
            $path = self::nonEmptyString($path, '$.files property name');
            $fileLocation = sprintf('$.files[%s]', json_encode($path, JSON_THROW_ON_ERROR));
            if (!$rawFile instanceof \stdClass) {
                throw new PhpStanJsonDecodeException(sprintf(
                    'PHPStan JSON property %s must be an object.',
                    $fileLocation,
                ));
            }

            $declaredCount = self::nonNegativeInteger(
                self::requiredProperty($rawFile, 'errors', $fileLocation),
                $fileLocation . '.errors',
            );
            $rawMessages = self::requiredProperty($rawFile, 'messages', $fileLocation);
            if (!is_array($rawMessages) || !array_is_list($rawMessages)) {
                throw new PhpStanJsonDecodeException(sprintf(
                    'PHPStan JSON property %s.messages must be a list.',
                    $fileLocation,
                ));
            }

            $diagnostics = [];
            foreach ($rawMessages as $index => $rawMessage) {
                $messageLocation = sprintf('%s.messages[%d]', $fileLocation, $index);
                if (!$rawMessage instanceof \stdClass) {
                    throw new PhpStanJsonDecodeException(sprintf(
                        'PHPStan JSON property %s must be an object.',
                        $messageLocation,
                    ));
                }

                $ignorable = self::requiredProperty($rawMessage, 'ignorable', $messageLocation);
                if (!is_bool($ignorable)) {
                    throw new PhpStanJsonDecodeException(sprintf(
                        'PHPStan JSON property %s.ignorable must be a boolean.',
                        $messageLocation,
                    ));
                }

                $rawLine = self::requiredProperty($rawMessage, 'line', $messageLocation);
                $analyzerLine = $rawLine === null
                    ? null
                    : self::positiveInteger($rawLine, $messageLocation . '.line');

                $diagnostics[] = new AnalyzerDiagnostic(
                    self::optionalNonEmptyString($rawMessage, 'identifier', $messageLocation),
                    self::nonEmptyString(
                        self::requiredProperty($rawMessage, 'message', $messageLocation),
                        $messageLocation . '.message',
                    ),
                    self::optionalNonEmptyString($rawMessage, 'tip', $messageLocation),
                    $analyzerLine,
                    null,
                    $ignorable,
                );
            }

            if ($declaredCount !== count($diagnostics)) {
                throw new PhpStanJsonDecodeException(sprintf(
                    'PHPStan JSON error count for %s does not match its message list.',
                    $path,
                ));
            }
            $diagnosticsByFile[$path] = $diagnostics;
        }
        ksort($diagnosticsByFile, SORT_STRING);

        try {
            return new PhpStanJsonResult(
                $globalErrorCount,
                $fileErrorCount,
                $globalErrors,
                $diagnosticsByFile,
            );
        } catch (\InvalidArgumentException $exception) {
            throw new PhpStanJsonDecodeException(
                sprintf('PHPStan JSON counts are inconsistent: %s', $exception->getMessage()),
                previous: $exception,
            );
        }
    }

    /**
     * @logion [AWC 102:10] When the last aircraft departed the northern field, a red iron crane came yearly to stand
     *     upon the abandoned runway. It never spread its wings, nor did snow settle upon its back. Travelers began
     *     measuring their journeys from that motionless bird, until one spring they found only two footprints filled
     *     with stars.
     */
    private static function requiredProperty(\stdClass $object, string $property, string $location): mixed
    {
        if (!property_exists($object, $property)) {
            throw new PhpStanJsonDecodeException(sprintf(
                'PHPStan JSON property %s.%s is required.',
                $location,
                $property,
            ));
        }

        return $object->{$property};
    }

    /**
     * @return non-negative-int
     *
     * @logion [SFA 102:11] When the silk map was praised as complete, its coastlines loosened and drifted into the
     *     blank sea around it. Thereafter the cartographers named no land without first leaving room for its departure.
     */
    private static function nonNegativeInteger(mixed $value, string $location): int
    {
        if (!is_int($value) || $value < 0) {
            throw new PhpStanJsonDecodeException(sprintf(
                'PHPStan JSON property %s must be a nonnegative integer.',
                $location,
            ));
        }

        return $value;
    }

    /**
     * @return positive-int
     *
     * @logion [AWC 102:12] In the years when lamplight was taxed, the poor glazed their roofs with black ceramic and
     *     gathered starlight in shallow channels. The collectors found no flame to confiscate, yet the houses shone
     *     inward until their rafters resembled the night sky; and no child born there feared darkness.
     */
    private static function positiveInteger(mixed $value, string $location): int
    {
        if (!is_int($value) || $value < 1) {
            throw new PhpStanJsonDecodeException(sprintf(
                'PHPStan JSON property %s must be a positive integer.',
                $location,
            ));
        }

        return $value;
    }

    /**
     * @return non-empty-string
     *
     * @logion [RAS 102:13] The marble colonnade departed from the dead palace and walked into the electric sea, each
     *     pillar advancing without base or capital. Fish gathered in the spaces between them, and waves broke
     *     according to an older architecture. Far from land the columns halted, supporting nothing visible; yet the
     *     horizon bent downward and rested upon them.
     */
    private static function nonEmptyString(mixed $value, string $location): string
    {
        if (!is_string($value) || trim($value) === '') {
            throw new PhpStanJsonDecodeException(sprintf(
                'PHPStan JSON property %s must be a nonempty string.',
                $location,
            ));
        }

        return $value;
    }

    /**
     * @return non-empty-string|null
     *
     * @logion [SFA 102:14] The ivory hive made no honey, but warmed the abandoned chapel through a hundred winters.
     *     Judge not every sweetness by the tongue; some is appointed to dwell unseen within the walls.
     */
    private static function optionalNonEmptyString(\stdClass $object, string $property, string $location): ?string
    {
        if (!property_exists($object, $property)) {
            return null;
        }

        return self::nonEmptyString($object->{$property}, $location . '.' . $property);
    }
}
