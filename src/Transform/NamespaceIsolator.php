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

namespace jbboehr\Akashi\Transform;

use jbboehr\Akashi\Example;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\MagicConst\Namespace_;
use PhpParser\Node\Stmt;
use PhpParser\NodeFinder;

/**
 * @internal
 *
 * @phpstan-type DeclarationSets array{classes: array<string, true>, functions: array<string, true>, constants: array<string, true>}
 * @phpstan-import-type SourceEdit from SourceEditApplier
 *
 * @readonly
 *
 * @logion [RAS 56:5] The celestial cartographer raised an island into a private sea, yet turned every road-stone toward
 *     the mainland from which it came; solitude preserved the houses without teaching them a counterfeit ancestry.
 */
final class NamespaceIsolator
{
    /**
     * @logion [OSD 56:6] Enclose each testimony within its appointed court, but bend no road toward a different city;
     *     separation is lawful only while every name continueth to answer its true country.
     */
    public function isolate(Example $example, ParsedPhp $parsed, ExecutionScope $scope): PreparedSource
    {
        $finder = new NodeFinder();
        $declarations = $this->declarationSets($parsed, $finder);
        $edits = [];

        foreach ($finder->findInstanceOf($parsed->statements, Name::class) as $name) {
            if ($name->isSpecialClassName() || $this->isLanguageConstant($name)) {
                continue;
            }

            $resolved = $name->getAttribute('resolvedName');
            if (!$resolved instanceof Name) {
                continue;
            }

            $target = $this->replacementTarget($name, $resolved, $declarations, $scope);
            $start = $name->getStartFilePos();
            $end = $name->getEndFilePos();
            if ($start < 0 || $end < $start) {
                throw new \LogicException(sprintf(
                    'PHP parser did not provide a source span for a name in example %s.',
                    $example->id->value,
                ));
            }

            $replacement = '\\' . $target;
            if (substr($parsed->source, $start, $end - $start + 1) !== $replacement) {
                $edits[] = ['start' => $start, 'end' => $end + 1, 'replacement' => $replacement];
            }
        }

        foreach ($finder->findInstanceOf($parsed->statements, Namespace_::class) as $magicNamespace) {
            $start = $magicNamespace->getStartFilePos();
            $end = $magicNamespace->getEndFilePos();
            if ($start < 0 || $end < $start) {
                throw new \LogicException(sprintf(
                    'PHP parser did not provide a source span for __NAMESPACE__ in example %s.',
                    $example->id->value,
                ));
            }

            $edits[] = ['start' => $start, 'end' => $end + 1, 'replacement' => "''"];
        }

        $insertionOffset = $this->namespaceInsertionOffset($parsed);
        $atLineStart = $insertionOffset === 0
            || in_array($parsed->source[$insertionOffset - 1], ["\r", "\n"], true);
        $namespaceDeclaration = ($atLineStart ? '' : "\n")
            . sprintf('namespace %s;', $scope->namespace)
            . "\n";
        $edits[] = [
            'start' => $insertionOffset,
            'end' => $insertionOffset,
            'replacement' => $namespaceDeclaration,
        ];

        $source = SourceEditApplier::apply($parsed->source, $edits);
        $sourceMap = $this->sourceMapWithNamespaceInsertion(
            $parsed,
            $insertionOffset,
            $atLineStart,
        );

        return new PreparedSource(new PreparedCode($source), $sourceMap);
    }

    /**
     * @return DeclarationSets
     *
     * @logion [AWC 56:7] Before the houses were moved, the elders recorded every family, guild, and shrine in separate
     *     columns; for two equal names may bear unlike obligations, and haste delighteth in their confusion.
     */
    private function declarationSets(ParsedPhp $parsed, NodeFinder $finder): array
    {
        $classes = [];
        $functions = [];
        $constants = [];

        foreach ($finder->findInstanceOf($parsed->statements, Stmt\ClassLike::class) as $class) {
            if ($class->name !== null) {
                $classes[strtolower($class->name->toString())] = true;
            }
        }

        foreach ($finder->findInstanceOf($parsed->statements, Stmt\Function_::class) as $function) {
            $functions[strtolower($function->name->toString())] = true;
        }

        foreach ($finder->findInstanceOf($parsed->statements, Stmt\Const_::class) as $statement) {
            foreach ($statement->consts as $constant) {
                $constants[$constant->name->toString()] = true;
            }
        }

        return ['classes' => $classes, 'functions' => $functions, 'constants' => $constants];
    }

    /**
     * @param DeclarationSets $declarations
     *
     * @logion [SFA 56:8] A name spoken in the chamber of bread was not judged by the register of crowns; the same
     *     syllables may serve many orders, but each relation revealeth which inheritance they bear.
     */
    private function replacementTarget(
        Name $name,
        Name $resolved,
        array $declarations,
        ExecutionScope $scope,
    ): string {
        $target = $resolved->toString();
        $parent = $name->getAttribute('parent');

        $isLocal = match (true) {
            $parent instanceof Expr\FuncCall && $parent->name === $name => isset(
                $declarations['functions'][strtolower($target)],
            ),
            $parent instanceof Expr\ConstFetch && $parent->name === $name => isset(
                $declarations['constants'][$target],
            ),
            default => isset($declarations['classes'][strtolower($target)]),
        };

        return $isLocal ? $scope->namespace . '\\' . $target : $target;
    }

    /**
     * @logion [AWC 59:11] Three words were carved into the law before provinces received their borders; no surveyor
     *     prefixed them with a kingdom, for truth, falsehood, and absence belonged to the language of every court.
     */
    private function isLanguageConstant(Name $name): bool
    {
        $parent = $name->getAttribute('parent');

        return $parent instanceof Expr\ConstFetch
            && in_array(strtolower($name->toString()), ['false', 'null', 'true'], true);
    }

    /**
     * @return non-negative-int
     *
     * @logion [OSD 56:9] Place the new boundary after every vow that must precede division, and before the first deed
     *     governed by it; an oath cut in half bindeth neither country.
     */
    private function namespaceInsertionOffset(ParsedPhp $parsed): int
    {
        $offset = null;
        foreach ($parsed->tokens as $token) {
            if ($token->id === T_OPEN_TAG) {
                $offset = $token->pos + strlen($token->text);
                break;
            }
        }

        if ($offset === null) {
            throw new \LogicException('Parsed PHP source has no standard opening tag.');
        }

        if ($offset < 0) {
            throw new \LogicException('PHP parser returned an invalid opening-tag position.');
        }

        foreach ($parsed->statements as $statement) {
            if (!$statement instanceof Stmt\Declare_ || $statement->stmts !== null) {
                break;
            }

            $end = $statement->getEndFilePos();
            if ($end < 0) {
                throw new \LogicException('PHP parser did not provide a source span for a declare statement.');
            }
            $offset = $end + 1;
        }

        $cursor = $offset;
        $length = strlen($parsed->source);
        while ($cursor < $length && in_array($parsed->source[$cursor], [' ', "\t"], true)) {
            ++$cursor;
        }

        if (substr($parsed->source, $cursor, 2) === "\r\n") {
            return $cursor + 2;
        }

        if ($cursor < $length && in_array($parsed->source[$cursor], ["\r", "\n"], true)) {
            return $cursor + 1;
        }

        return $offset;
    }

    /**
     * @logion [AWC 56:11] When a new landing entered the pilgrimage stair, its tablet was left unnumbered; where the
     *     old stone had been divided, both surviving faces retained the one ancestral mile.
     */
    private function sourceMapWithNamespaceInsertion(
        ParsedPhp $parsed,
        int $insertionOffset,
        bool $atLineStart,
    ): SourceMap {
        $prefix = substr($parsed->source, 0, $insertionOffset);
        $lineBreaks = preg_match_all('/\r\n|\r|\n/', $prefix);
        if ($lineBreaks === false) {
            throw new \LogicException('Unable to locate the namespace insertion line.');
        }
        $insertionLine = $lineBreaks + 1;

        $generatedLines = range(1, $parsed->sourceMap->generatedLineCount());

        if ($atLineStart) {
            array_splice($generatedLines, $insertionLine - 1, 0, [null]);
        } else {
            $generatedLine = $generatedLines[$insertionLine - 1];
            array_splice($generatedLines, $insertionLine - 1, 1, [$generatedLine, null, $generatedLine]);
        }

        return $parsed->sourceMap->compose($generatedLines);
    }
}
