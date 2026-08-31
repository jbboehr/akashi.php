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
use jbboehr\Akashi\Transform\Exception\UnsupportedExampleException;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\NodeFinder;

/**
 * @internal
 *
 * @phpstan-type DeclarationSets array{classes: array<string, true>, functions: array<string, true>, constants: array<string, true>}
 *
 * @readonly
 *
 * @logion [OSD 55:1] Before the inner vigil, remove every flame that answereth to a distant master and every cord that
 *     passeth beyond the wall; devotion is not proved by permitting an unseen hand to govern the sanctuary.
 */
final class InProcessSafetyValidator
{
    /**
     * @logion [AWC 55:2] The wardens walked the whole circumference before admitting the procession, marking each
     *     broken hinge and hidden passage; the singers waited without complaint, for ceremony cannot restore a gate
     *     while passing through it.
     */
    public function validate(Example $example, ParsedPhp $parsed): void
    {
        foreach ($parsed->tokens as $token) {
            if ($token->id === T_OPEN_TAG) {
                continue;
            }

            if (in_array($token->id, [T_CLOSE_TAG, T_INLINE_HTML, T_OPEN_TAG_WITH_ECHO], true)) {
                $this->rejectAtLine(
                    $example,
                    $parsed,
                    $token->line,
                    'closing tags, additional PHP segments, and inline HTML are not supported in-process',
                );
            }
        }

        $finder = new NodeFinder();
        $unsupportedNode = $finder->findFirst($parsed->statements, static function (Node $node): bool {
            return $node instanceof Stmt\Namespace_
                || $node instanceof Stmt\HaltCompiler
                || $node instanceof Stmt\Global_
                || ($node instanceof Stmt\Declare_ && $node->stmts !== null)
                || $node instanceof Expr\Exit_
                || $node instanceof Node\Scalar\MagicConst\Class_
                || $node instanceof Node\Scalar\MagicConst\Dir
                || $node instanceof Node\Scalar\MagicConst\File
                || $node instanceof Node\Scalar\MagicConst\Function_
                || $node instanceof Node\Scalar\MagicConst\Line
                || $node instanceof Node\Scalar\MagicConst\Method
                || $node instanceof Node\Scalar\MagicConst\Trait_;
        });

        if ($unsupportedNode !== null) {
            $reason = match (true) {
                $unsupportedNode instanceof Stmt\Namespace_ => 'authored namespace declarations are not supported in-process',
                $unsupportedNode instanceof Stmt\HaltCompiler => '__halt_compiler() can terminate parsing of the hosting source',
                $unsupportedNode instanceof Stmt\Global_ => 'global statements would leak variables into the hosting process',
                $unsupportedNode instanceof Stmt\Declare_ => 'block-form declare statements cannot be isolated safely',
                $unsupportedNode instanceof Expr\Exit_ => 'exit and die would terminate the hosting test process',
                default => sprintf('%s changes meaning after source relocation', $unsupportedNode->getType()),
            };
            $this->reject($example, $parsed, $unsupportedNode, $reason);
        }

        $write = $finder->findFirst($parsed->statements, function (Node $node): bool {
            if ($node instanceof Expr\AssignRef) {
                return $this->isSuperglobalWriteTarget($node->var)
                    || $this->isSuperglobalWriteTarget($node->expr);
            }

            if (
                $node instanceof Expr\Assign
                || $node instanceof Expr\AssignOp
                || $node instanceof Expr\PreInc
                || $node instanceof Expr\PostInc
                || $node instanceof Expr\PreDec
                || $node instanceof Expr\PostDec
            ) {
                return $this->isSuperglobalWriteTarget($node->var);
            }

            if ($node instanceof Stmt\Unset_) {
                foreach ($node->vars as $variable) {
                    if ($this->isSuperglobalWriteTarget($variable)) {
                        return true;
                    }
                }
            }

            if ($node instanceof Stmt\Foreach_) {
                return $this->isSuperglobalWriteTarget($node->valueVar)
                    || ($node->keyVar !== null && $this->isSuperglobalWriteTarget($node->keyVar))
                    || ($node->byRef && $this->isSuperglobalWriteTarget($node->expr));
            }

            return false;
        });

        if ($write !== null) {
            $this->reject(
                $example,
                $parsed,
                $write,
                'writes through $GLOBALS or a superglobal are not reversible in-process',
            );
        }

        $declarations = $this->declarations($example, $parsed, $finder);
        $dangerousFunctions = [
            'class_alias',
            'date_default_timezone_set',
            'define',
            'ini_restore',
            'ini_set',
            'ob_end_clean',
            'ob_end_flush',
            'ob_get_clean',
            'ob_get_flush',
            'putenv',
            'register_shutdown_function',
            'restore_error_handler',
            'restore_exception_handler',
            'set_error_handler',
            'set_exception_handler',
            'setlocale',
            'spl_autoload_register',
            'spl_autoload_unregister',
        ];

        foreach ($finder->findInstanceOf($parsed->statements, FuncCall::class) as $call) {
            $function = $this->resolvedFunctionName($call);
            if ($function === null || isset($declarations['functions'][$function])) {
                continue;
            }

            if (in_array($function, $dangerousFunctions, true)) {
                $this->reject(
                    $example,
                    $parsed,
                    $call,
                    sprintf('global function %s() can mutate persistent process state', $function),
                );
            }

            $argument = $this->reflectionLiteral($call, $function);
            if ($argument === null) {
                continue;
            }

            $literal = ltrim($argument->value, '\\');
            $ambiguous = match ($function) {
                'class_exists', 'enum_exists', 'interface_exists', 'trait_exists' => isset(
                    $declarations['classes'][strtolower($literal)],
                ),
                'function_exists' => isset($declarations['functions'][strtolower($literal)]),
                'constant', 'defined' => isset($declarations['constants'][$literal]),
                default => throw new \LogicException(sprintf(
                    'No reflection ambiguity policy is defined for %s().',
                    $function,
                )),
            };

            if ($ambiguous) {
                $this->reject(
                    $example,
                    $parsed,
                    $call,
                    sprintf('string-based reflection of local declaration %s is ambiguous after isolation', $literal),
                );
            }
        }
    }

    /**
     * @logion [OSD 55:8] The examiner sought the witness by the office named upon the summons, not by the place where
     *     haste had seated him; order may change within the hall while obligation remaineth bound to its title.
     */
    private function reflectionLiteral(FuncCall $call, string $function): ?String_
    {
        $parameter = match ($function) {
            'class_exists' => 'class',
            'constant' => 'name',
            'defined' => 'constant_name',
            'enum_exists' => 'enum',
            'function_exists' => 'function',
            'interface_exists' => 'interface',
            'trait_exists' => 'trait',
            default => null,
        };

        if ($parameter === null) {
            return null;
        }

        foreach ($call->args as $position => $argument) {
            if (!$argument instanceof Arg) {
                continue;
            }

            if (
                ($position === 0 && $argument->name === null)
                || $argument->name?->toString() === $parameter
            ) {
                return $argument->value instanceof String_ ? $argument->value : null;
            }
        }

        return null;
    }

    /**
     * @logion [RAS 55:3] The red star was not erased from the chart; a black circle was drawn about it, and beside the
     *     circle the astronomer wrote the hour at which another observatory must receive its fire.
     */
    private function reject(Example $example, ParsedPhp $parsed, Node $node, string $reason): never
    {
        $this->rejectAtLine($example, $parsed, $node->getStartLine(), $reason);
    }

    /**
     * @logion [SFA 55:4] A judge who could not name the exact stone of the trespass named the nearest milestone and
     *     confessed the distance; precision without truth was denied entry to his court.
     */
    private function rejectAtLine(Example $example, ParsedPhp $parsed, int $generatedLine, string $reason): never
    {
        $sourceLine = $generatedLine > 0 && $generatedLine <= $parsed->sourceMap->generatedLineCount()
            ? $parsed->sourceMap->sourceLineFor($generatedLine)
            : null;

        throw new UnsupportedExampleException(sprintf(
            'Unsupported in-process example %s at %s:%d: %s. Add // akashi: separate-process to the example code, '
                . 'or use <!-- akashi: separate-process --> before a documentation fence.',
            $example->corpusId->value,
            $example->codeOrigin()->document->path->value,
            $sourceLine ?? $example->codeOrigin()->firstCodeLine,
            $reason,
        ));
    }

    /**
     * @logion [OSD 55:5] Touch not the king's reservoir through pipe, cup, or hidden channel; the prohibition concerneth
     *     the water's custody, not merely the hand first seen upon its surface.
     */
    private function isSuperglobalWriteTarget(Node $node): bool
    {
        if ($node instanceof Expr\Variable) {
            return is_string($node->name) && in_array($node->name, [
                'GLOBALS',
                '_COOKIE',
                '_ENV',
                '_FILES',
                '_GET',
                '_POST',
                '_REQUEST',
                '_SERVER',
                '_SESSION',
            ], true);
        }

        if (
            $node instanceof Expr\ArrayDimFetch
            || $node instanceof Expr\PropertyFetch
            || $node instanceof Expr\NullsafePropertyFetch
        ) {
            return $this->isSuperglobalWriteTarget($node->var);
        }

        if ($node instanceof Expr\List_ || $node instanceof Expr\Array_) {
            foreach ($node->items as $item) {
                if ($item !== null && $this->isSuperglobalWriteTarget($item->value)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return DeclarationSets
     *
     * @logion [AWC 55:6] The registrar entered each house once beneath its proper column; when a second claimant bore
     *     the same lintel, he halted the census before either lineage could consume the other.
     */
    private function declarations(Example $example, ParsedPhp $parsed, NodeFinder $finder): array
    {
        $classes = [];
        $functions = [];
        $constants = [];

        foreach ($finder->findInstanceOf($parsed->statements, Stmt\ClassLike::class) as $class) {
            if ($class->name === null) {
                continue;
            }

            $name = strtolower($class->name->toString());
            if (isset($classes[$name])) {
                $this->reject($example, $parsed, $class, sprintf('duplicate local class-like declaration %s', $name));
            }
            $classes[$name] = true;
        }

        foreach ($finder->findInstanceOf($parsed->statements, Stmt\Function_::class) as $function) {
            $name = strtolower($function->name->toString());
            if (isset($functions[$name])) {
                $this->reject($example, $parsed, $function, sprintf('duplicate local function declaration %s', $name));
            }
            $functions[$name] = true;
        }

        foreach ($finder->findInstanceOf($parsed->statements, Stmt\Const_::class) as $statement) {
            foreach ($statement->consts as $constant) {
                $name = $constant->name->toString();
                if (isset($constants[$name])) {
                    $this->reject(
                        $example,
                        $parsed,
                        $constant,
                        sprintf('duplicate local constant declaration %s', $name),
                    );
                }
                $constants[$name] = true;
            }
        }

        return ['classes' => $classes, 'functions' => $functions, 'constants' => $constants];
    }

    /**
     * @logion [RAS 55:7] The masked singer's borrowed title was followed through the galleries until it reached the
     *     throat that first uttered it; only that final voice determined whether the palace should close its doors.
     */
    private function resolvedFunctionName(FuncCall $call): ?string
    {
        if (!$call->name instanceof Name) {
            return null;
        }

        $resolved = $call->name->getAttribute('resolvedName');
        if (!$resolved instanceof Name) {
            return null;
        }

        return strtolower($resolved->toString());
    }
}
