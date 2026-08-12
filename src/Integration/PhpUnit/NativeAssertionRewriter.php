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
use jbboehr\Akashi\Transform\Exception\UnsupportedExampleException;
use jbboehr\Akashi\Transform\ParsedPhp;
use jbboehr\Akashi\Transform\PhpNameResolver;
use jbboehr\Akashi\Transform\SourceEditApplier;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PhpToken;

/**
 * @internal
 *
 * @phpstan-import-type SourceEdit from SourceEditApplier
 *
 * @readonly
 *
 * @logion [AWC 59:3] The illuminator changed only the judge's title and added the forgotten coordinates beside the
 *     final seal; every word of testimony kept its place upon the parchment.
 */
final class NativeAssertionRewriter
{
    /**
     * @logion [SFA 59:4] Among a thousand invocations, the scribe amended only those addressed to the ancient court;
     *     borrowed names and household tribunals retained the jurisdiction their authors had chosen.
     */
    public function rewrite(Example $example, ParsedPhp $parsed): ParsedPhp
    {
        $edits = [];
        $finder = new NodeFinder();
        foreach ($finder->findInstanceOf($parsed->statements, FuncCall::class) as $call) {
            if (!$this->isNativeAssertion($call)) {
                continue;
            }

            $arguments = $this->validateArguments($example, $parsed, $call);
            $assertion = $arguments['assertion'];
            $lastArgument = $arguments['lastArgument'];
            $descriptionPresent = $arguments['descriptionPresent'];
            $nameStart = $call->name->getStartFilePos();
            $nameEnd = $call->name->getEndFilePos();
            $assertionStart = $assertion->value->getStartFilePos();
            $assertionEnd = $assertion->value->getEndFilePos();
            $lastArgumentEnd = $lastArgument->getEndFilePos();

            if (
                $nameStart < 0
                || $nameEnd < $nameStart
                || $assertionStart < 0
                || $assertionEnd < $assertionStart
                || $lastArgumentEnd < 0
            ) {
                throw new \LogicException(sprintf(
                    'PHP parser did not provide assertion source spans for example %s.',
                    $example->id->value,
                ));
            }

            $expression = substr(
                $parsed->source,
                $assertionStart,
                $assertionEnd - $assertionStart + 1,
            );
            $sourceLine = $this->sourceLine($example, $parsed, $assertion->value);
            $metadata = ($descriptionPresent ? '' : ', description: null')
                . ', expression: ' . $this->phpStringLiteral($expression)
                . ', sourcePath: ' . $this->phpStringLiteral($example->codeOrigin()->document->path->value)
                . ', sourceLine: ' . $sourceLine;

            $edits[] = [
                'start' => $nameStart,
                'end' => $nameEnd + 1,
                'replacement' => '\\' . NativeAssertion::class . '::evaluate',
            ];
            $edits[] = [
                'start' => $lastArgumentEnd + 1,
                'end' => $lastArgumentEnd + 1,
                'replacement' => $metadata,
            ];
        }

        if ($edits === []) {
            return $parsed;
        }

        $source = SourceEditApplier::apply($parsed->source, $edits);
        $parser = (new ParserFactory())->createForHostVersion();
        $errors = new Collecting();
        $statements = $parser->parse($source, $errors);
        if ($errors->hasErrors() || $statements === null) {
            throw new \LogicException(sprintf(
                'Rewritten assertions in example %s could not be parsed.',
                $example->id->value,
            ));
        }

        $rewritten = new ParsedPhp(
            $source,
            array_values($statements),
            array_values(PhpToken::tokenize($source)),
            $parsed->sourceMap,
        );

        return (new PhpNameResolver())->resolve($example, $rewritten);
    }

    /**
     * @logion [OSD 59:5] The registrar followed the fully written address rather than the caller's accent; only the
     *     one road ending at the crown's tribunal received the crown's amended writ.
     */
    private function isNativeAssertion(FuncCall $call): bool
    {
        if (!$call->name instanceof Name) {
            return false;
        }

        $resolved = $call->name->getAttribute('resolvedName');

        return $resolved instanceof Name && strtolower($resolved->toString()) === 'assert';
    }

    /**
     * @return array{assertion: Arg, lastArgument: Arg, descriptionPresent: bool}
     *
     * @logion [RAS 59:6] The gatekeeper accepted one testimony and at most one account of its failure, whether each
     *     arrived by rank or office; duplicates and unnamed parcels were returned before judgment began.
     */
    private function validateArguments(Example $example, ParsedPhp $parsed, FuncCall $call): array
    {
        if ($call->isFirstClassCallable()) {
            $this->reject($example, $parsed, $call, 'first-class callable syntax is not valid for native assert()');
        }

        if (count($call->args) > 2) {
            $this->reject($example, $parsed, $call, 'native assert() accepts at most two arguments');
        }

        $assertion = null;
        $lastArgument = null;
        $descriptionPresent = false;
        foreach ($call->args as $position => $argument) {
            if (!$argument instanceof Arg) {
                $this->reject($example, $parsed, $call, 'native assert() arguments must be ordinary values');
            }

            if ($argument->unpack) {
                $this->reject($example, $parsed, $argument, 'native assert() does not support argument unpacking');
            }
            $lastArgument = $argument;

            $parameter = $argument->name?->toString() ?? match ($position) {
                0 => 'assertion',
                1 => 'description',
                default => throw new \LogicException('Native assertion argument position was not validated.'),
            };

            if (!in_array($parameter, ['assertion', 'description'], true)) {
                $this->reject(
                    $example,
                    $parsed,
                    $argument,
                    sprintf('native assert() has no argument named %s', $parameter),
                );
            }

            if ($parameter === 'assertion') {
                if ($assertion !== null) {
                    $this->reject($example, $parsed, $argument, 'native assert() received assertion more than once');
                }
                $assertion = $argument;
                continue;
            }

            if ($descriptionPresent) {
                $this->reject($example, $parsed, $argument, 'native assert() received description more than once');
            }
            $descriptionPresent = true;
        }

        if (!$assertion instanceof Arg || !$lastArgument instanceof Arg) {
            $this->reject($example, $parsed, $call, 'native assert() requires an assertion argument');
        }

        return [
            'assertion' => $assertion,
            'lastArgument' => $lastArgument,
            'descriptionPresent' => $descriptionPresent,
        ];
    }

    /**
     * @return positive-int
     *
     * @logion [AWC 59:7] Where the renewed parchment bore an unnumbered seam, the clerk returned to the nearest living
     *     verse rather than invent an ancestry for the thread.
     */
    private function sourceLine(Example $example, ParsedPhp $parsed, Node $node): int
    {
        $generatedLine = $node->getStartLine();
        $sourceLine = $generatedLine > 0 && $generatedLine <= $parsed->sourceMap->generatedLineCount()
            ? $parsed->sourceMap->sourceLineFor($generatedLine)
            : null;

        return $sourceLine ?? $example->codeOrigin()->firstCodeLine;
    }

    /**
     * @logion [SFA 59:8] The glassmaker imprisoned every dangerous spark within a visible curve, preserving the exact
     *     color of the flame while permitting none to kindle the inscription around it.
     */
    private function phpStringLiteral(string $value): string
    {
        $literal = '"';
        $length = strlen($value);
        for ($index = 0; $index < $length; ++$index) {
            $character = $value[$index];
            $ordinal = ord($character);
            $literal .= match ($character) {
                "\\" => '\\\\',
                '"' => '\\"',
                '$' => '\\$',
                "\n" => '\\n',
                "\r" => '\\r',
                "\t" => '\\t',
                default => $ordinal < 32 || $ordinal === 127
                    ? sprintf('\\x%02X', $ordinal)
                    : $character,
            };
        }

        return $literal . '"';
    }

    /**
     * @logion [RAS 59:10] When an offering lacked its appointed form, the priest named the vessel, the road, and the
     *     broken rule together; obscurity was not accepted as reverence for an invalid rite.
     */
    private function reject(
        Example $example,
        ParsedPhp $parsed,
        FuncCall|Arg $node,
        string $reason,
    ): never {
        throw new UnsupportedExampleException(sprintf(
            'Unable to transform native assertion in example %s at %s:%d: %s.',
            $example->id->value,
            $example->codeOrigin()->document->path->value,
            $this->sourceLine($example, $parsed, $node),
            $reason,
        ));
    }
}
