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
use jbboehr\Akashi\Integration\PHPStan\Exception\ExpectationParseException;
use jbboehr\Akashi\Model\CodeOrigin;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ReferencedExampleSource;
use jbboehr\Akashi\Model\SourceSpan;
use jbboehr\Akashi\Transform\Exception\PhpParseException;
use jbboehr\Akashi\Transform\PhpExampleParser;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Nop;
use PhpParser\NodeFinder;

/**
 * @readonly
 *
 * @logion [RAS 64:14] The clerk walked the testimony from first line to last, lifting only marks placed at the lawful
 *     margin and preserving their order even when no accusation stood between them.
 */
final class ExpectationParser
{
    /**
     * @return list<DiagnosticExpectation>
     *
     * @throws ExpectationParseException when an expectation marker is empty, malformed, or misplaced
     * @throws PhpParseException when an identifier expectation occurs in invalid PHP
     *
     * @logion [AWC 64:15] Read each marked margin against its maintained stair, remove only surrounding emptiness, and
     *     reject a silent mark with the witness's name and road plainly declared.
     */
    public function parse(Example $example): array
    {
        $selectedOrigin = $example->codeOrigin();
        $selectedLastLine = $selectedOrigin->lastCodeLine ?? $selectedOrigin->firstCodeLine;
        $parseExample = $example;
        if ($example->source instanceof ReferencedExampleSource && $example->source->region !== null) {
            $document = $selectedOrigin->document;
            $lineCount = $document->lines->lineCount();
            if ($lineCount < 1) {
                throw new \LogicException('A referenced PHP document must contain at least one source line.');
            }

            $contextOrigin = new CodeOrigin(
                $document,
                1,
                $lineCount,
                new SourceSpan(0, strlen($document->contents)),
            );
            $parseExample = new Example(
                id: $example->id,
                label: $example->label,
                source: new ReferencedExampleSource(
                    $contextOrigin,
                    null,
                    $example->source->references,
                ),
                language: $example->language,
                code: new ExampleCode($document->contents),
                ordinal: $example->ordinal,
                explicitMarkerId: $example->explicitMarkerId,
                directives: $example->directives,
                expectedException: $example->expectedException,
                expectedOutput: $example->expectedOutput,
            );
        }

        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $example->code->source));
        $tokenSource = $parseExample->code->source;
        if (preg_match('/\A<\?php(?:\s|$)/i', $tokenSource) !== 1) {
            $tokenSource = "<?php\n" . $tokenSource;
        }

        $hasIdentifierDirective = false;
        foreach (\PhpToken::tokenize($tokenSource) as $token) {
            if (
                $token->id === T_COMMENT
                && preg_match('/\A\/\/\h*@akashi-phpstan-error(?:\h|:|\z)/', $token->text) === 1
            ) {
                $hasIdentifierDirective = true;
                break;
            }
        }

        $sourceMap = null;
        $statementRanges = [];
        $commentLines = [];
        $identifierCommentLines = [];
        if ($hasIdentifierDirective) {
            $parsed = (new PhpExampleParser())->parse($parseExample);
            $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $parsed->source));
            $sourceMap = $parsed->sourceMap;

            foreach ($parsed->tokens as $token) {
                if ($token->id === T_COMMENT && str_starts_with(ltrim($token->text), '//')) {
                    $commentLines[$token->line] = true;
                    if (preg_match('/\A\/\/\h*@akashi-phpstan-error(?:\h|:|\z)/', $token->text) === 1) {
                        $identifierCommentLines[$token->line] = true;
                    }
                }
            }

            $statements = array_values(array_filter(
                (new NodeFinder())->findInstanceOf($parsed->statements, Stmt::class),
                static fn (Stmt $statement): bool => !$statement instanceof Nop,
            ));
            usort($statements, static fn (Stmt $left, Stmt $right): int =>
                $left->getStartLine() <=> $right->getStartLine()
                ?: $right->getEndLine() <=> $left->getEndLine());

            foreach ($statements as $statement) {
                $mappedLines = [];
                foreach (range($statement->getStartLine(), $statement->getEndLine()) as $generatedLine) {
                    if ($generatedLine < 1 || $generatedLine > $sourceMap->generatedLineCount()) {
                        continue;
                    }

                    $mappedLine = $sourceMap->sourceLineFor($generatedLine);
                    if ($mappedLine !== null) {
                        $mappedLines[] = $mappedLine;
                    }
                }

                if ($mappedLines !== []) {
                    $firstMappedLine = min($mappedLines);
                    $lastMappedLine = max($mappedLines);
                    if (
                        $firstMappedLine < $selectedOrigin->firstCodeLine
                        || $lastMappedLine > $selectedLastLine
                    ) {
                        continue;
                    }

                    $statementRanges[] = [
                        'generated' => $statement->getStartLine(),
                        'first' => $firstMappedLine,
                        'last' => $lastMappedLine,
                    ];
                }
            }
        }

        $expectations = [];
        foreach ($lines as $offset => $line) {
            $generatedLine = $offset + 1;
            if ($hasIdentifierDirective) {
                $mappedSourceLine = $sourceMap->sourceLineFor($generatedLine);
                if (
                    $mappedSourceLine === null
                    || $mappedSourceLine < $selectedOrigin->firstCodeLine
                    || $mappedSourceLine > $selectedLastLine
                    || !isset($commentLines[$generatedLine])
                ) {
                    continue;
                }
                $sourceLine = $mappedSourceLine;
            } else {
                $sourceLine = $selectedOrigin->firstCodeLine + $offset;
            }

            if (preg_match('/\A\h*\/\/!(.*)\z/', $line, $legacyMatch) === 1) {
                $text = trim($legacyMatch[1]);
                if ($text === '') {
                    throw new ExpectationParseException(sprintf(
                        'Example %s at %s:%d contains an empty PHPStan diagnostic expectation.',
                        $example->id->value,
                        $example->codeOrigin()->document->path->value,
                        $sourceLine,
                    ));
                }

                $expectations[] = new DiagnosticExpectation($text, $sourceLine);

                continue;
            }

            if (!$hasIdentifierDirective) {
                continue;
            }

            if (preg_match('/\A\h*\/\/\h*@akashi-phpstan-error(?:\h|:|\z)/', $line) !== 1) {
                if (isset($identifierCommentLines[$generatedLine])) {
                    throw new ExpectationParseException(sprintf(
                        'Example %s at %s:%d contains a misplaced PHPStan diagnostic identifier expectation; '
                        . 'the directive must occupy a standalone line.',
                        $example->id->value,
                        $selectedOrigin->document->path->value,
                        $sourceLine,
                    ));
                }

                continue;
            }

            if (preg_match(
                '/\A\h*\/\/\h*@akashi-phpstan-error\h+([^\s:]+)(?:\h*:\h*(\S(?:.*\S)?))?\h*\z/',
                $line,
                $identifierMatch,
            ) !== 1) {
                throw new ExpectationParseException(sprintf(
                    'Example %s at %s:%d contains a malformed PHPStan diagnostic identifier expectation.',
                    $example->id->value,
                    $example->codeOrigin()->document->path->value,
                    $sourceLine,
                ));
            }

            $statementRange = null;
            foreach ($statementRanges as $candidateRange) {
                if ($candidateRange['generated'] > $generatedLine) {
                    $statementRange = $candidateRange;
                    break;
                }
            }
            if ($statementRange === null) {
                throw new ExpectationParseException(sprintf(
                    'Example %s at %s:%d contains a PHPStan diagnostic identifier expectation that is not followed by a statement.',
                    $example->id->value,
                    $example->codeOrigin()->document->path->value,
                    $sourceLine,
                ));
            }

            for ($lineNumber = $generatedLine + 1; $lineNumber < $statementRange['generated']; ++$lineNumber) {
                $interveningLine = $lines[$lineNumber - 1];
                $isIdentifierDirectiveLine = isset($identifierCommentLines[$lineNumber])
                    && preg_match(
                        '/\A\h*\/\/\h*@akashi-phpstan-error(?:\h|:|\z)/',
                        $interveningLine,
                    ) === 1;
                if (
                    trim($interveningLine) !== ''
                    && !$isIdentifierDirectiveLine
                ) {
                    throw new ExpectationParseException(sprintf(
                        'Example %s at %s:%d contains a PHPStan diagnostic identifier expectation that does not immediately precede a statement.',
                        $example->id->value,
                        $example->codeOrigin()->document->path->value,
                        $sourceLine,
                    ));
                }
            }

            $expectations[] = new DiagnosticExpectation(
                isset($identifierMatch[2]) ? $identifierMatch[2] : null,
                $sourceLine,
                $identifierMatch[1],
                ['first' => $statementRange['first'], 'last' => $statementRange['last']],
            );
        }

        return $expectations;
    }
}
