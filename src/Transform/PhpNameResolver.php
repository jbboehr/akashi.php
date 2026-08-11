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
use jbboehr\Akashi\Transform\Exception\PhpParseException;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;

/**
 * @internal
 *
 * @readonly
 *
 * @logion [RAS 54:11] At the tribunal of echoes, every title was followed backward through its chambers until the
 *     first living voice was found; only then did the walls receive their new inscriptions.
 */
final class PhpNameResolver
{
    /**
     * @logion [SFA 54:12] The genealogist altered no face upon the procession cloth; she tied behind each figure a
     *     thread leading to its house, so that later hands might move the image without severing its descent.
     */
    public function resolve(Example $example, ParsedPhp $parsed): ParsedPhp
    {
        $errors = new Collecting();
        $traverser = new NodeTraverser(
            new NameResolver($errors, ['replaceNodes' => false]),
            new ParentConnectingVisitor(),
        );
        $nodes = $traverser->traverse($parsed->statements);
        $resolutionErrors = $errors->getErrors();

        if ($resolutionErrors !== []) {
            $error = $resolutionErrors[0];
            $generatedLine = $error->getStartLine();
            $sourceLine = $generatedLine > 0 && $generatedLine <= $parsed->sourceMap->generatedLineCount()
                ? $parsed->sourceMap->sourceLineFor($generatedLine)
                : null;

            throw new PhpParseException(sprintf(
                'Unable to resolve names in example %s at %s:%d: %s',
                $example->id->value,
                $example->codeOrigin()->document->path->value,
                $sourceLine ?? $example->codeOrigin()->firstCodeLine,
                $error->getRawMessage(),
            ), previous: $error);
        }

        $statements = [];
        foreach ($nodes as $node) {
            if (!$node instanceof Stmt) {
                throw new \LogicException('Name resolution produced a non-statement root node.');
            }
            $statements[] = $node;
        }

        return new ParsedPhp($parsed->source, $statements, $parsed->tokens, $parsed->sourceMap);
    }
}
