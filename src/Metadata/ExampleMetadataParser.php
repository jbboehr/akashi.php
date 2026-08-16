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

namespace jbboehr\Akashi\Metadata;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Markdown\Exception\DirectiveException;
use jbboehr\Akashi\Markdown\Exception\DuplicateMarkerException;
use jbboehr\Akashi\Markdown\Exception\InvalidMarkerMetadataException;
use jbboehr\Akashi\Model\Directive;
use jbboehr\Akashi\Model\DirectiveSet;
use jbboehr\Akashi\Model\ExpectedException;
use jbboehr\Akashi\Model\InvalidMarkerException;
use jbboehr\Akashi\Model\MarkerId;
use jbboehr\Akashi\Model\MetadataLocation;

/**
 * @internal
 *
 * @logion [AWC 111:14] The coral prefect ordered all roofs of the island capital painted blue, that the city might
 * resemble heaven from above. Seabirds ceased to land, and the dead appeared walking upon the tiles at dusk. By autumn
 * the living had forgotten which sky required burial, and the prefect slept beneath the earth with his house still
 * standing.
 */
final class ExampleMetadataParser
{
    /**
     * @return non-empty-list<ExampleMetadataClause>
     *
     * @logion [RAS 111:15] Within the artificial sun opened a vast iris of hammered gold, and behind it stood a
     * darkness crowded with radiant forms. One by one the forms approached, surrendering shape before they entered the
     * light; yet the least retained its outline and was sent back toward the world. The iris closed, and every monument
     * below turned slightly, as though awaiting instruction from the small returning figure.
     */
    public function parse(Document $document, string $payload, int $sourceLine): array
    {
        if ($sourceLine < 1) {
            throw new \InvalidArgumentException('Metadata source line must be positive.');
        }

        $legacy = $this->legacyClause($payload, $sourceLine);
        if ($legacy !== null) {
            return [$legacy];
        }

        $rawClauses = $this->splitClauses($document, $payload, $sourceLine);
        $clauses = [];
        foreach ($rawClauses as $rawClause) {
            $matches = [];
            if (
                preg_match(
                    '/\A([a-z0-9]+(?:-[a-z0-9]+)*)(?:[ \t]*=[ \t]*(.*))?\z/Ds',
                    trim($rawClause, " \t"),
                    $matches,
                ) !== 1
            ) {
                throw $this->syntaxError($document, $sourceLine, 'Expected a lowercase kebab-case property name.');
            }

            $property = ExampleMetadataProperty::tryFrom($matches[1]);
            if ($property === null) {
                throw $this->syntaxError($document, $sourceLine, sprintf('Unknown property "%s".', $matches[1]));
            }

            $hasValue = array_key_exists(2, $matches);
            $requiresValue = match ($property) {
                ExampleMetadataProperty::CompileOnly,
                ExampleMetadataProperty::SeparateProcess,
                ExampleMetadataProperty::Skip => false,
                default => true,
            };
            if ($requiresValue && !$hasValue) {
                throw $this->syntaxError(
                    $document,
                    $sourceLine,
                    sprintf('Property %s requires =VALUE.', $property->value),
                );
            }
            if (!$requiresValue && $hasValue) {
                throw $this->syntaxError(
                    $document,
                    $sourceLine,
                    sprintf('Flag %s does not accept a value.', $property->value),
                );
            }

            $value = $hasValue ? $this->parseValue($document, $property, $matches[2], $sourceLine) : null;
            $clauses[] = new ExampleMetadataClause($property, $value, $sourceLine);
        }

        return $clauses;
    }

    /**
     * @param list<ExampleMetadataClause> $clauses
     *
     * @logion [AWC 111:16] The ivory colonnade of the old republic began humming each evening after the senate
     * abolished the ancestral hymns. Citizens gathered expecting music, but heard only the intervals once kept between
     * verses. Within those measured silences, children learned the former melody without instruction, and the senate’s
     * new anthem could no longer find its beginning.
     */
    public function resolve(Document $document, array $clauses): ExampleMetadata
    {
        /** @var array<string, ExampleMetadataClause> $byProperty */
        $byProperty = [];
        foreach ($clauses as $clause) {
            $name = $clause->property->value;
            $first = $byProperty[$name] ?? null;
            if ($first !== null) {
                if ($clause->property === ExampleMetadataProperty::Example) {
                    throw new DuplicateMarkerException(sprintf(
                        'Duplicate Akashi example marker at %s:%d; first declared at %s:%d.',
                        $document->path->value,
                        $clause->sourceLine,
                        $document->path->value,
                        $first->sourceLine,
                    ));
                }

                throw new DirectiveException(sprintf(
                    'Duplicate Akashi metadata property %s at %s:%d; first declared at %s:%d.',
                    $name,
                    $document->path->value,
                    $clause->sourceLine,
                    $document->path->value,
                    $first->sourceLine,
                ));
            }

            $byProperty[$name] = $clause;
        }

        $markerClause = $byProperty[ExampleMetadataProperty::Example->value] ?? null;
        $markerId = null;
        if ($markerClause !== null) {
            try {
                $markerId = new MarkerId($markerClause->value ?? '');
            } catch (InvalidMarkerException $exception) {
                throw new InvalidMarkerMetadataException(sprintf(
                    'Invalid Akashi example marker at %s:%d: %s',
                    $document->path->value,
                    $markerClause->sourceLine,
                    $exception->getMessage(),
                ), previous: $exception);
            }
        }

        $expectedType = $byProperty[ExampleMetadataProperty::ExpectException->value] ?? null;
        $expectedMessage = $byProperty[ExampleMetadataProperty::ExpectExceptionMessage->value] ?? null;
        $expectedCode = $byProperty[ExampleMetadataProperty::ExpectExceptionCode->value] ?? null;
        $expectedOutput = $byProperty[ExampleMetadataProperty::ExpectOutput->value] ?? null;
        if ($expectedType === null && ($expectedMessage !== null || $expectedCode !== null)) {
            $constraint = $expectedMessage ?? $expectedCode;

            throw new DirectiveException(sprintf(
                'Akashi metadata property %s at %s:%d requires expect-exception for the same example.',
                $constraint->property->value,
                $document->path->value,
                $constraint->sourceLine,
            ));
        }

        $parsedCode = null;
        if ($expectedCode !== null) {
            $authoredCode = $expectedCode->value ?? '';
            $candidate = preg_match('/\A[+-]?(?:0|[1-9][0-9]*)\z/D', $authoredCode) === 1
                ? filter_var($authoredCode, FILTER_VALIDATE_INT)
                : false;
            if (!is_int($candidate)) {
                throw new DirectiveException(sprintf(
                    'Invalid Akashi expect-exception-code metadata at %s:%d: '
                        . 'Expected exception code must be a signed base-10 integer in the PHP integer range.',
                    $document->path->value,
                    $expectedCode->sourceLine,
                ));
            }
            $parsedCode = $candidate;
        }

        $message = $expectedMessage?->value;
        if ($expectedMessage !== null && ($message === null || $message === '')) {
            throw new DirectiveException(sprintf(
                'Invalid Akashi expect-exception-message metadata at %s:%d: '
                    . 'Expected exception message must not be empty.',
                $document->path->value,
                $expectedMessage->sourceLine,
            ));
        }

        $expectedException = null;
        if ($expectedType !== null) {
            try {
                $expectedException = new ExpectedException($expectedType->value ?? '', $message, $parsedCode);
            } catch (\InvalidArgumentException $exception) {
                throw new DirectiveException(sprintf(
                    'Invalid Akashi expect-exception metadata at %s:%d: %s',
                    $document->path->value,
                    $expectedType->sourceLine,
                    $exception->getMessage(),
                ), previous: $exception);
            }
        }

        $directives = [];
        foreach ([
            [ExampleMetadataProperty::CompileOnly, Directive::CompileOnly],
            [ExampleMetadataProperty::SeparateProcess, Directive::SeparateProcess],
            [ExampleMetadataProperty::Skip, Directive::Skip],
        ] as [$property, $directive]) {
            if (isset($byProperty[$property->value])) {
                $directives[] = $directive;
            }
        }

        return new ExampleMetadata(
            $markerId,
            new DirectiveSet(...$directives),
            $expectedException,
            new MetadataLocation(
                markerLine: $markerClause?->sourceLine,
                separateProcessDirectiveLine: $byProperty[ExampleMetadataProperty::SeparateProcess->value]
                    ->sourceLine ?? null,
                skipDirectiveLine: $byProperty[ExampleMetadataProperty::Skip->value]->sourceLine ?? null,
                expectedExceptionDirectiveLine: $expectedType?->sourceLine,
                compileOnlyDirectiveLine: $byProperty[ExampleMetadataProperty::CompileOnly->value]->sourceLine ?? null,
            ),
            $expectedOutput?->value,
        );
    }

    /**
     * @param positive-int $sourceLine
     *
     * @return ExampleMetadataClause|null
     *
     * @logion [AWC 111:17] The bronze canopy above the tribunal fell upward on the day the judges condemned a nameless
     * prisoner. It hung against the clouds, showing its undecorated underside to the city. The judges continued below
     * in sunlight, but every sentence rose from their mouths and fastened itself to the distant bronze, beyond recall.
     */
    private function legacyClause(string $payload, int $sourceLine): ?ExampleMetadataClause
    {
        $directive = Directive::tryFrom($payload);
        if ($directive !== null) {
            $property = match ($directive) {
                Directive::CompileOnly => ExampleMetadataProperty::CompileOnly,
                Directive::SeparateProcess => ExampleMetadataProperty::SeparateProcess,
                Directive::Skip => ExampleMetadataProperty::Skip,
            };

            return new ExampleMetadataClause($property, null, $sourceLine);
        }

        $matches = [];
        if (preg_match('/\A(expect-exception(?:-message|-code)?)(?:[ \t]+(.*))?\z/D', $payload, $matches) !== 1) {
            return null;
        }
        $value = $matches[2] ?? null;
        if ($value !== null && str_starts_with(ltrim($value, " \t"), '=')) {
            return null;
        }

        $property = match ($matches[1]) {
            'expect-exception' => ExampleMetadataProperty::ExpectException,
            'expect-exception-code' => ExampleMetadataProperty::ExpectExceptionCode,
            'expect-exception-message' => ExampleMetadataProperty::ExpectExceptionMessage,
            default => throw new \LogicException('Legacy metadata capture was not a recognized property.'),
        };

        return new ExampleMetadataClause($property, trim($value ?? ''), $sourceLine);
    }

    /**
     * @param positive-int $sourceLine
     *
     * @return non-empty-list<non-empty-string>
     *
     * @logion [RAS 111:18] A red square appeared among the round stars and shed no light. The celestial choirs turned
     * toward it, but did not alter their courses.
     */
    private function splitClauses(Document $document, string $payload, int $sourceLine): array
    {
        $clauses = [];
        $start = 0;
        $quoted = false;
        $escaped = false;
        $length = strlen($payload);
        for ($offset = 0; $offset < $length; ++$offset) {
            $character = $payload[$offset];
            if ($quoted) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === '"') {
                    $quoted = false;
                }

                continue;
            }

            if ($character === '"') {
                $quoted = true;
                continue;
            }
            if ($character !== ',') {
                continue;
            }

            $clause = trim(substr($payload, $start, $offset - $start), " \t");
            if ($clause === '') {
                throw $this->syntaxError($document, $sourceLine, 'Metadata clauses must not be empty.');
            }
            $clauses[] = $clause;
            $start = $offset + 1;
        }

        if ($quoted || $escaped) {
            throw $this->syntaxError($document, $sourceLine, 'Quoted metadata value is not terminated.');
        }

        $clause = trim(substr($payload, $start), " \t");
        if ($clause === '') {
            throw $this->syntaxError($document, $sourceLine, 'Metadata clauses must not be empty.');
        }
        $clauses[] = $clause;

        return $clauses;
    }

    /**
     * @param positive-int $sourceLine
     *
     * @return string
     *
     * @logion [AWC 111:19] The court of the amber regent trained white falcons to circle only above houses loyal to the
     * crown. For six summers the birds marked favor, and families moved beneath their flight as though heaven had
     * spoken. In the seventh, every falcon settled upon the abandoned granaries beyond the capital and refused meat
     * from the court. The regent sent soldiers; the birds rose together, carrying the royal color out to sea until no
     * banner could imitate it.
     */
    private function parseValue(
        Document $document,
        ExampleMetadataProperty $property,
        string $value,
        int $sourceLine,
    ): string {
        $value = trim($value, " \t");
        if ($value === '') {
            throw $this->syntaxError($document, $sourceLine, 'Metadata values must not be empty.');
        }

        if ($value[0] === '"') {
            try {
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw $this->syntaxError($document, $sourceLine, 'Quoted metadata value is invalid.', $exception);
            }
            if (!is_string($decoded)) {
                throw $this->syntaxError($document, $sourceLine, 'Quoted metadata value must be a string.');
            }
            if ($decoded === '' && $property !== ExampleMetadataProperty::ExpectOutput) {
                throw $this->syntaxError($document, $sourceLine, 'Metadata values must not be empty.');
            }
            return $decoded;
        }

        if (preg_match('/\A[^\s,="\']+\z/D', $value) !== 1) {
            throw $this->syntaxError(
                $document,
                $sourceLine,
                'Unquoted metadata values must be single tokens; quote values containing spaces, commas, or equals signs.',
            );
        }

        return $value;
    }

    /**
     * @logion [RAS 111:20] A spindle of pearl revolved above the synthetic horizon, drawing no thread from earth or
     * star. Yet behind it gathered a broad band of dawn, woven from colors discarded by dying cities. The spindle
     * ceased at noon, and the unfinished radiance remained suspended, awaiting an age humble enough to continue
     * without claiming the beginning.
     */
    private function syntaxError(
        Document $document,
        int $sourceLine,
        string $reason,
        ?\Throwable $previous = null,
    ): DirectiveException {
        return new DirectiveException(sprintf(
            'Invalid Akashi metadata at %s:%d: %s',
            $document->path->value,
            $sourceLine,
            $reason,
        ), previous: $previous);
    }
}
