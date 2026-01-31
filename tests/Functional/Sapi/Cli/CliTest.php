<?php

/*
 * Nytris Ignition
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/ignition/
 *
 * Released under the MIT license.
 * https://github.com/nytris/ignition/raw/main/MIT-LICENSE.txt
 */

declare(strict_types=1);

namespace Nytris\Ignition\Tests\Functional\Sapi\Cli;

use Nytris\Ignition\Tests\Functional\AbstractFunctionalTestCase;
use Symfony\Component\Process\Process;

/**
 * Class CliTest.
 *
 * Tests Ignition when used by scripts run under CLI, with Composer's autoloader not being loaded at all.
 * Ensures there are no catch-22 issues with loading Ignition's stream wrapper, early autoloader
 * and any preflights using them.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class CliTest extends AbstractFunctionalTestCase
{
    public function testStartInstallsStreamWrapper(): void
    {
        $process = new Process([
            PHP_BINARY,
            dirname(__DIR__, 2) . '/Fixtures/Sapi/Cli/test_start_installs_stream_wrapper.php',
        ]);

        static::assertSame(0, $process->run());
        static::assertEquals(
            ['wrapper_data instanceof StreamWrapper' => true],
            unserialize($process->getOutput())
        );
        static::assertSame('', $process->getErrorOutput());
    }

    public function testStartLoadsStatCacheWhenSupported(): void
    {
        $process = new Process([
            PHP_BINARY,
            dirname(__DIR__, 2) . '/Fixtures/Sapi/Cli/test_start_loads_stat_cache_when_supported.php',
        ]);

        static::assertSame(0, $process->run());
        static::assertEquals(
            ['size' => 1234],
            unserialize($process->getOutput())['stat_cache']['/my/first/path']
        );
        static::assertSame('', $process->getErrorOutput());
    }

    public function testStartDoesNotLoadStatCacheWhenNotSupported(): void
    {
        $process = new Process([
            PHP_BINARY,
            dirname(__DIR__, 2) . '/Fixtures/Sapi/Cli/test_start_does_not_load_stat_cache_when_not_supported.php',
        ]);

        static::assertSame(0, $process->run());
        static::assertEquals(
            ['is_choke_on' => false],
            unserialize($process->getOutput())
        );
        static::assertSame('', $process->getErrorOutput());
    }
}
