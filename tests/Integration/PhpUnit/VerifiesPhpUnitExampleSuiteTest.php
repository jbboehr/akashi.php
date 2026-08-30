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

namespace jbboehr\Akashi\Tests\Integration\PhpUnit;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Example;
use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Execution\RuntimeConfiguration;
use jbboehr\Akashi\Integration\PhpUnit\PhpUnitExampleSuite;
use jbboehr\Akashi\Integration\PhpUnit\VerifiesPhpUnitExampleSuite;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use PHPUnit\Framework\TestCase;

final class VerifiesPhpUnitExampleSuiteTest extends TestCase
{
    private string $workspace;

    protected function setUp(): void
    {
        $workspace = tempnam(sys_get_temp_dir(), 'akashi-phpunit-suite-');
        self::assertNotFalse($workspace);
        self::assertTrue(unlink($workspace));
        self::assertTrue(mkdir($workspace, 0o700));

        $this->workspace = $workspace;
    }

    protected function tearDown(): void
    {
        PhpUnitExampleSuiteHarness::$suite = null;
        PhpUnitExampleSuiteHarness::$suiteHookCalls = 0;

        self::assertTrue(rmdir($this->workspace));
    }

    public function testLazilyProvidesTheConfiguredSuiteOnceToEveryNamedDataSet(): void
    {
        $first = $this->example('phpunit-suite-trait-01', 'First suite example', 'assert(true);', 1);
        $second = $this->example('phpunit-suite-trait-02', 'Second suite example', 'assert(true);', 2);
        $runtimeConfiguration = RuntimeConfiguration::forProject(dirname(__DIR__, 3));
        $suite = new PhpUnitExampleSuite(
            new ExampleCorpus($first, $second),
            $runtimeConfiguration,
        );
        PhpUnitExampleSuiteHarness::$suite = $suite;

        $provider = PhpUnitExampleSuiteHarness::akashiExampleDataProvider();

        self::assertSame(0, PhpUnitExampleSuiteHarness::$suiteHookCalls);

        $provider->rewind();

        self::assertSame(1, PhpUnitExampleSuiteHarness::$suiteHookCalls);

        $dataSets = [];
        while ($provider->valid()) {
            $dataSets[$provider->key()] = $provider->current();
            $provider->next();
        }

        self::assertSame(1, PhpUnitExampleSuiteHarness::$suiteHookCalls);
        self::assertSame([
            'First suite example' => [$first, $runtimeConfiguration],
            'Second suite example' => [$second, $runtimeConfiguration],
        ], $dataSets);
    }

    public function testCarriesNullConfigurationAsTheSecondAndOnlyOtherDataSetArgument(): void
    {
        $example = $this->example('phpunit-suite-trait-01', 'Default suite example', 'assert(true);', 1);
        PhpUnitExampleSuiteHarness::$suite = new PhpUnitExampleSuite(new ExampleCorpus($example));
        $provider = PhpUnitExampleSuiteHarness::akashiExampleDataProvider();

        $provider->rewind();

        self::assertSame([$example, null], $provider->current());
    }

    public function testRejectsDuplicateLabelsBeforeTheFirstDataSetIsYielded(): void
    {
        PhpUnitExampleSuiteHarness::$suite = new PhpUnitExampleSuite(new ExampleCorpus(
            $this->example('phpunit-suite-trait-01', 'Repeated suite example', 'assert(true);', 1),
            $this->example('phpunit-suite-trait-02', 'Repeated suite example', 'assert(true);', 2),
        ));
        $provider = PhpUnitExampleSuiteHarness::akashiExampleDataProvider();

        self::assertSame(0, PhpUnitExampleSuiteHarness::$suiteHookCalls);

        try {
            $provider->rewind();
        } catch (\InvalidArgumentException $exception) {
            self::assertSame(1, PhpUnitExampleSuiteHarness::$suiteHookCalls);
            self::assertSame(
                'Duplicate PHPUnit data-set label Repeated suite example for examples '
                . 'phpunit-suite-trait-01 and phpunit-suite-trait-02.',
                $exception->getMessage(),
            );

            return;
        }

        self::fail('Duplicate labels must be rejected before the first data set is yielded.');
    }

    public function testExecutesAnExampleWithTheSuiteRuntimeConfiguration(): void
    {
        $originalDirectory = getcwd();
        self::assertNotFalse($originalDirectory);
        $runtimeConfiguration = RuntimeConfiguration::forProject($this->workspace);
        $example = $this->example(
            'phpunit-suite-trait-01',
            'Configured suite example',
            sprintf('assert(getcwd() === %s);', var_export($runtimeConfiguration->projectRoot->value, true)),
            1,
        );
        $suite = new PhpUnitExampleSuite(new ExampleCorpus($example), $runtimeConfiguration);

        $testCase = new PhpUnitExampleSuiteHarness('testAkashiDocumentationExample');
        $testCase->testAkashiDocumentationExample($example, $suite->runtimeConfiguration);

        self::assertSame($originalDirectory, getcwd());
    }

    /** @param positive-int $ordinal */
    private function example(string $id, string $label, string $code, int $ordinal): Example
    {
        return Example::fromInline(
            id: new ExampleId($id),
            label: $label,
            document: new Document('docs/phpunit-suite-trait.md', $code),
            location: new SourceLocation(
                9,
                10,
                10,
                11,
                new SourceSpan(0, strlen($code)),
                new SourceSpan(0, strlen($code)),
            ),
            language: new Language('php'),
            code: new ExampleCode($code),
            fence: new FenceMetadata('php', '`', 3, 0),
            ordinal: $ordinal,
        );
    }
}

final class PhpUnitExampleSuiteHarness extends TestCase
{
    use VerifiesPhpUnitExampleSuite;

    public static ?PhpUnitExampleSuite $suite = null;

    public static int $suiteHookCalls = 0;

    protected static function akashiExampleSuite(): PhpUnitExampleSuite
    {
        ++self::$suiteHookCalls;

        return self::$suite ?? throw new \LogicException('PHPUnit example suite not configured.');
    }
}
