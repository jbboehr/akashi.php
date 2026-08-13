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

namespace jbboehr\Akashi\Tests\Integration\PHPStan;

use jbboehr\Akashi\Integration\PHPStan\AnalyzerDiagnostic;
use jbboehr\Akashi\Integration\PHPStan\Exception\PhpStanJsonDecodeException;
use jbboehr\Akashi\Integration\PHPStan\PhpStanJsonDecoder;
use jbboehr\Akashi\Integration\PHPStan\PhpStanJsonResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhpStanJsonDecoderTest extends TestCase
{
    public function testDecodesCleanOutput(): void
    {
        $result = (new PhpStanJsonDecoder())->decode(
            '{"totals":{"errors":0,"file_errors":0},"files":{},"errors":[]}',
        );

        self::assertSame(0, $result->globalErrorCount);
        self::assertSame(0, $result->fileErrorCount);
        self::assertSame([], $result->globalErrors);
        self::assertSame([], $result->diagnosticsByFile);
    }

    public function testDecodesPhpStanOneEmptyFilesList(): void
    {
        $result = (new PhpStanJsonDecoder())->decode(
            '{"totals":{"errors":0,"file_errors":0},"files":[],"errors":[]}',
        );

        self::assertSame([], $result->diagnosticsByFile);
    }

    public function testPreservesDiagnosticWithoutAnAnalyzerLine(): void
    {
        $result = (new PhpStanJsonDecoder())->decode(
            '{"totals":{"errors":0,"file_errors":1},"files":{"a.php":{"errors":1,"messages":'
            . '[{"message":"Unable to resolve reflection.","line":null,"ignorable":false}]}},"errors":[]}',
        );

        self::assertNull($result->diagnosticsByFile['a.php'][0]->analyzerLine);
    }

    public function testPreservesGlobalErrorsFileAssociationAndDiagnosticEvidence(): void
    {
        $result = (new PhpStanJsonDecoder())->decode(<<<'JSON'
{
  "totals": {"errors": 1, "file_errors": 3},
  "files": {
    "/project/z.php": {
      "errors": 1,
      "messages": [
        {"message": "Variable $z might not be defined.", "line": 8, "ignorable": true,
         "identifier": "variable.undefined", "metadata": {"future": true}}
      ]
    },
    "/project/a.php": {
      "errors": 2,
      "messages": [
        {"message": "Syntax error.", "line": 2, "ignorable": false, "identifier": "phpstan.parse"},
        {"message": "A second problem.", "line": 5, "ignorable": true, "tip": "Read the guide."}
      ]
    }
  },
  "errors": ["Internal analyzer extension failed."],
  "future": "ignored"
}
JSON);

        self::assertSame(1, $result->globalErrorCount);
        self::assertSame(3, $result->fileErrorCount);
        self::assertSame(['Internal analyzer extension failed.'], $result->globalErrors);
        self::assertSame(['/project/a.php', '/project/z.php'], array_keys($result->diagnosticsByFile));

        $parse = $result->diagnosticsByFile['/project/a.php'][0];
        self::assertSame('phpstan.parse', $parse->identifier);
        self::assertSame('Syntax error.', $parse->message);
        self::assertSame(2, $parse->analyzerLine);
        self::assertNull($parse->sourceLine);
        self::assertFalse($parse->ignorable);

        $tip = $result->diagnosticsByFile['/project/a.php'][1];
        self::assertNull($tip->identifier);
        self::assertSame('Read the guide.', $tip->tip);
        self::assertTrue($tip->ignorable);

        $undefined = $result->diagnosticsByFile['/project/z.php'][0];
        self::assertSame('variable.undefined', $undefined->identifier);
        self::assertNull($undefined->tip);
        self::assertTrue($undefined->ignorable);
    }

    #[DataProvider('invalidJsonProvider')]
    public function testRejectsMalformedOrStructurallyInvalidOutput(string $json, string $message): void
    {
        $this->expectException(PhpStanJsonDecodeException::class);
        $this->expectExceptionMessage($message);

        (new PhpStanJsonDecoder())->decode($json);
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidJsonProvider(): iterable
    {
        yield 'empty' => ['', 'must not be empty'];
        yield 'invalid JSON' => ['{', 'Unable to decode'];
        yield 'nonobject root' => ['[]', 'must be an object'];
        yield 'missing totals' => ['{"files":{},"errors":[]}', '$.totals is required'];
        yield 'nonobject totals' => [
            '{"totals":[],"files":{},"errors":[]}',
            '$.totals must be an object',
        ];
        yield 'negative global count' => [
            '{"totals":{"errors":-1,"file_errors":0},"files":{},"errors":[]}',
            '$.totals.errors must be a nonnegative integer',
        ];
        yield 'floating file count' => [
            '{"totals":{"errors":0,"file_errors":1.0},"files":{},"errors":[]}',
            '$.totals.file_errors must be a nonnegative integer',
        ];
        yield 'global errors not a list' => [
            '{"totals":{"errors":0,"file_errors":0},"files":{},"errors":{}}',
            '$.errors must be a list',
        ];
        yield 'blank global error' => [
            '{"totals":{"errors":1,"file_errors":0},"files":{},"errors":[" "]}',
            '$.errors[0] must be a nonempty string',
        ];
        yield 'files not an object' => [
            '{"totals":{"errors":0,"file_errors":0},"files":[{}],"errors":[]}',
            '$.files must be an object or an empty list',
        ];
        yield 'blank file path' => [
            '{"totals":{"errors":0,"file_errors":0},"files":{" ":{"errors":0,"messages":[]}},"errors":[]}',
            '$.files property name must be a nonempty string',
        ];
        yield 'file result not an object' => [
            '{"totals":{"errors":0,"file_errors":0},"files":{"a.php":[]},"errors":[]}',
            '$.files["a.php"] must be an object',
        ];
        yield 'messages not a list' => [
            '{"totals":{"errors":0,"file_errors":0},"files":{"a.php":{"errors":0,"messages":{}}},"errors":[]}',
            '$.files["a.php"].messages must be a list',
        ];
        yield 'message not an object' => [
            '{"totals":{"errors":0,"file_errors":1},"files":{"a.php":{"errors":1,"messages":[[]]}},"errors":[]}',
            '$.files["a.php"].messages[0] must be an object',
        ];
        yield 'missing message text' => [
            '{"totals":{"errors":0,"file_errors":1},"files":{"a.php":{"errors":1,"messages":[{"line":1,"ignorable":true}]}},"errors":[]}',
            '.message is required',
        ];
        yield 'nonpositive line' => [
            '{"totals":{"errors":0,"file_errors":1},"files":{"a.php":{"errors":1,"messages":[{"message":"x","line":0,"ignorable":true}]}},"errors":[]}',
            '.line must be a positive integer',
        ];
        yield 'missing ignorable' => [
            '{"totals":{"errors":0,"file_errors":1},"files":{"a.php":{"errors":1,"messages":[{"message":"x","line":1}]}},"errors":[]}',
            '.ignorable is required',
        ];
        yield 'nonboolean ignorable' => [
            '{"totals":{"errors":0,"file_errors":1},"files":{"a.php":{"errors":1,"messages":[{"message":"x","line":1,"ignorable":1}]}},"errors":[]}',
            '.ignorable must be a boolean',
        ];
        yield 'blank identifier' => [
            '{"totals":{"errors":0,"file_errors":1},"files":{"a.php":{"errors":1,"messages":[{"message":"x","line":1,"ignorable":true,"identifier":""}]}},"errors":[]}',
            '.identifier must be a nonempty string',
        ];
        yield 'blank tip' => [
            '{"totals":{"errors":0,"file_errors":1},"files":{"a.php":{"errors":1,"messages":[{"message":"x","line":1,"ignorable":true,"tip":" "}]}},"errors":[]}',
            '.tip must be a nonempty string',
        ];
        yield 'file count mismatch' => [
            '{"totals":{"errors":0,"file_errors":0},"files":{"a.php":{"errors":1,"messages":[]}},"errors":[]}',
            'error count for a.php does not match',
        ];
        yield 'global count mismatch' => [
            '{"totals":{"errors":1,"file_errors":0},"files":{},"errors":[]}',
            'global error count must match',
        ];
        yield 'total file count mismatch' => [
            '{"totals":{"errors":0,"file_errors":2},"files":{"a.php":{"errors":1,"messages":[{"message":"x","line":1,"ignorable":true}]}},"errors":[]}',
            'file error count must match',
        ];
    }

    /**
     * @param array<mixed> $globalErrors
     * @param array<array-key, mixed> $diagnosticsByFile
     */
    #[DataProvider('invalidResultProvider')]
    public function testResultRejectsInvalidDirectConstruction(
        int $globalErrorCount,
        int $fileErrorCount,
        array $globalErrors,
        array $diagnosticsByFile,
        string $message,
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new PhpStanJsonResult($globalErrorCount, $fileErrorCount, $globalErrors, $diagnosticsByFile);
    }

    /** @return iterable<string, array{int, int, array<mixed>, array<mixed>, string}> */
    public static function invalidResultProvider(): iterable
    {
        $diagnostic = new AnalyzerDiagnostic(null, 'message');

        yield 'negative global count' => [-1, 0, [], [], 'global error count must not be negative'];
        yield 'negative file count' => [0, -1, [], [], 'file error count must not be negative'];
        yield 'global errors not a list' => [1, 0, [1 => 'error'], [], 'global errors must be a list'];
        yield 'invalid global error' => [1, 0, [' '], [], 'global error must be a nonempty string'];
        yield 'global count mismatch' => [1, 0, [], [], 'global error count must match'];
        yield 'blank file path' => [0, 1, [], [' ' => [$diagnostic]], 'file path must be a nonempty string'];
        yield 'diagnostics not a list' => [0, 1, [], ['a.php' => [1 => $diagnostic]], 'must be a list'];
        yield 'wrong diagnostic type' => [0, 1, [], ['a.php' => ['message']], 'AnalyzerDiagnostic values'];
        yield 'file count mismatch' => [0, 2, [], ['a.php' => [$diagnostic]], 'file error count must match'];
    }
}
