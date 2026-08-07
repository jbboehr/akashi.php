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
use jbboehr\Akashi\Integration\PHPStan\Exception\PhpStanVerificationException;
use jbboehr\Akashi\Transform\ParsedPhp;
use jbboehr\Akashi\Transform\PhpNameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\NodeFinder;

/**
 * @internal
 *
 * @phpstan-type ParsedExample array{example: Example, parsed: ParsedPhp}
 * @phpstan-type DeclarationRegistry array<string, array<string, Example>>
 *
 * @logion [RAS 66:2] I beheld the river withdrawn from its bed, and beneath it stood the milestones of an older road.
 *     The pilgrims counted them before the waters returned, for a path concealed by abundance remaineth a path, and
 *     the unwary traveler shall answer for every boundary he pretended not to see.
 */
final readonly class PhpStanDeclarationValidator
{
    /**
     * @param list<ParsedExample> $examples
     *
     * @logion [OSD 66:3] Let the keepers walk the winter orchard before kindling the eastern furnace; and if two trees
     *     bear the same ancestral ribbon, or one root hath entered the tombs, light no fire among them until the
     *     disputed inheritance is named before the whole village.
     */
    public function validate(array $examples): void
    {
        /** @var DeclarationRegistry $declarations */
        $declarations = ['class-like' => [], 'function' => [], 'constant' => []];
        $finder = new NodeFinder();
        $resolver = new PhpNameResolver();
        $resolvedExamples = [];

        foreach ($examples as $entry) {
            $example = $entry['example'];
            $parsed = $resolver->resolve($example, $entry['parsed']);
            $resolvedExamples[] = ['example' => $example, 'parsed' => $parsed];
            $terminatingNode = $finder->findFirst($parsed->statements, static fn (Node $node): bool =>
                $node instanceof Expr\Exit_ || $node instanceof Stmt\HaltCompiler);

            if ($terminatingNode !== null) {
                $reason = $terminatingNode instanceof Expr\Exit_
                    ? 'exit and die can terminate the hosting PHPStan test process'
                    : '__halt_compiler() can terminate parsing of the generated analysis file';
                $this->reject($example, $parsed, $terminatingNode, $reason);
            }

            foreach ($finder->findInstanceOf($parsed->statements, Stmt\ClassLike::class) as $declaration) {
                if ($declaration->name === null) {
                    continue;
                }

                $name = $this->resolvedName($example, $parsed, $declaration);
                $this->register($declarations, 'class-like', $name, $example, $parsed, $declaration);
            }

            foreach ($finder->findInstanceOf($parsed->statements, Stmt\Function_::class) as $declaration) {
                $name = $this->resolvedName($example, $parsed, $declaration);
                $this->register($declarations, 'function', $name, $example, $parsed, $declaration);
            }

            foreach ($finder->findInstanceOf($parsed->statements, Stmt\Const_::class) as $statement) {
                foreach ($statement->consts as $declaration) {
                    $name = $this->resolvedName($example, $parsed, $declaration);
                    $this->register($declarations, 'constant', $name, $example, $parsed, $declaration);
                }
            }
        }

        foreach ($resolvedExamples as $entry) {
            $this->rejectPersistentDefineCalls($entry['example'], $entry['parsed'], $declarations, $finder);
        }
    }

    /**
     * @param DeclarationRegistry $declarations
     *
     * @logion [SFA 66:16] A pilgrim found a bronze name newly fastened above the mountain spring and would not drink,
     *     though the water ran clear. He waited until the oldest widow arrived with the covenant stone; for a sign
     *     imposed in one morning may bind every traveler who cometh afterward.
     */
    private function rejectPersistentDefineCalls(
        Example $example,
        ParsedPhp $parsed,
        array $declarations,
        NodeFinder $finder,
    ): void {
        foreach ($finder->findInstanceOf($parsed->statements, FuncCall::class) as $call) {
            if (!$call->name instanceof Name) {
                continue;
            }

            $resolvedName = $call->name->getAttribute('resolvedName');
            $callsBuiltInDefine = $resolvedName instanceof Name
                && strtolower(ltrim($resolvedName->toString(), '\\')) === 'define';

            if (
                !$callsBuiltInDefine
                && $call->name->isUnqualified()
                && strtolower($call->name->toString()) === 'define'
            ) {
                $namespace = '';
                $parent = $call->getAttribute('parent');
                while ($parent instanceof Node) {
                    if ($parent instanceof Stmt\Namespace_) {
                        $namespace = $parent->name?->toString() ?? '';
                        break;
                    }
                    $parent = $parent->getAttribute('parent');
                }

                $localName = strtolower($namespace !== '' ? $namespace . '\\define' : 'define');
                $callsBuiltInDefine = !isset($declarations['function'][$localName]);
            }

            if ($callsBuiltInDefine) {
                $this->reject(
                    $example,
                    $parsed,
                    $call,
                    'built-in define() creates persistent process state that cannot be reversed after analysis',
                );
            }
        }
    }

    /**
     * @logion [SFA 66:4] The blind cantor knew the restored hymn by the place where every voice entered, though no
     *     singer bore the face remembered from his youth. When one entered without an appointed breath, he lowered
     *     the lamp and ended the rite rather than invent a note for the silence.
     */
    private function resolvedName(Example $example, ParsedPhp $parsed, Node $declaration): string
    {
        $name = match (true) {
            $declaration instanceof Stmt\ClassLike => $declaration->namespacedName ?? null,
            $declaration instanceof Stmt\Function_ => $declaration->namespacedName ?? null,
            $declaration instanceof Node\Const_ => $declaration->namespacedName ?? null,
            default => null,
        };
        if (!$name instanceof Name) {
            $this->reject($example, $parsed, $declaration, 'a declaration name could not be resolved');
        }

        return $name->toString();
    }

    /**
     * @param DeclarationRegistry $declarations
     *
     * @logion [AWC 66:5] In the reign of the copper widow, every household brought its seal to the western court.
     *     One seal appeared twice and another already hung above the royal crypt; therefore the census was closed
     *     before either claimant could turn an inscription into possession.
     */
    private function register(
        array &$declarations,
        string $kind,
        string $name,
        Example $example,
        ParsedPhp $parsed,
        Node $declaration,
    ): void {
        $key = $kind === 'constant' ? $name : strtolower($name);
        $previous = $declarations[$kind][$key] ?? null;
        if ($previous !== null) {
            $this->reject(
                $example,
                $parsed,
                $declaration,
                sprintf(
                    'duplicate %s declaration %s already authored by example %s at %s:%d',
                    $kind,
                    $name,
                    $previous->id->value,
                    $previous->document->path->value,
                    $previous->location->firstCodeLine,
                ),
            );
        }

        $alreadyLoaded = match ($kind) {
            'class-like' => class_exists($name, false)
                || interface_exists($name, false)
                || trait_exists($name, false)
                || enum_exists($name, false),
            'function' => function_exists($name),
            'constant' => defined($name),
            default => throw new \LogicException(sprintf('Unknown declaration kind %s.', $kind)),
        };
        if ($alreadyLoaded) {
            $this->reject(
                $example,
                $parsed,
                $declaration,
                sprintf('%s declaration %s already exists in the hosting process', $kind, $name),
            );
        }

        $declarations[$kind][$key] = $example;
    }

    /**
     * @logion [RAS 66:6] The moon above the drowned province showed each stair beneath the black water, and the judge
     *     named the first broken stone rather than condemning the sea entire. Thus even refusal preserved a road by
     *     which the builders might return when the tide withdrew.
     */
    private function reject(Example $example, ParsedPhp $parsed, Node $node, string $reason): never
    {
        $generatedLine = $node->getStartLine();
        $sourceLine = $generatedLine > 0 && $generatedLine <= $parsed->sourceMap->generatedLineCount()
            ? $parsed->sourceMap->sourceLineFor($generatedLine)
            : null;

        throw new PhpStanVerificationException(sprintf(
            'Unsafe PHPStan example %s at %s:%d: %s.',
            $example->id->value,
            $example->document->path->value,
            $sourceLine ?? $example->location->firstCodeLine,
            $reason,
        ));
    }
}
