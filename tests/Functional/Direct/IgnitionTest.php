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

namespace Nytris\Ignition\Tests\Functional\Direct;

use Mockery\MockInterface;
use Nytris\Ignition\Filesystem\StreamWrapper;
use Nytris\Ignition\Ignition;
use Nytris\Ignition\Storage\StorageInterface;
use Nytris\Ignition\Tests\Functional\AbstractFunctionalTestCase;

/**
 * Class IgnitionTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class IgnitionTest extends AbstractFunctionalTestCase
{
    private MockInterface&StorageInterface $storage;

    public function setUp(): void
    {
        $this->storage = mock(StorageInterface::class, [
            'fetchStatCache' => [
                '/my/first/path' => ['size' => 1234],
            ],
            'isSupported' => true,
            'saveStatCache' => null,
        ]);
    }

    public function tearDown(): void
    {
        Ignition::switchOff();
    }

    public function testStartInstallsStreamWrapper(): void
    {
        Ignition::start(
            rootProjectPath: dirname(__DIR__) . '/Fixtures/Direct/WithAutoHandoffDisablingPreflight',
            storage: $this->storage
        );

        $stream = fopen(__FILE__, 'rb');
        $metadata = stream_get_meta_data($stream);
        fclose($stream);

        static::assertInstanceOf(StreamWrapper::class, $metadata['wrapper_data']);
    }

    public function testStartLoadsStatCacheWhenSupported(): void
    {
        Ignition::start(
            rootProjectPath: dirname(__DIR__) . '/Fixtures/Direct/WithAutoHandoffDisablingPreflight',
            storage: $this->storage
        );

        $statCache = Ignition::getStatCache();

        static::assertEquals(['size' => 1234], $statCache['/my/first/path']);
    }

    public function testStartDoesNotLoadStatCacheWhenNotSupported(): void
    {
        $this->storage->allows()
            ->isSupported()
            ->andReturnFalse();

        $this->storage->expects()
            ->fetchStatCache()
            ->never();

        Ignition::start(
            rootProjectPath: dirname(__DIR__) . '/Fixtures/Direct/WithAutoHandoffDisablingPreflight',
            storage: $this->storage
        );

        static::assertFalse(Ignition::isChokeOn());
    }

    public function testCachedStatIsReturnedForChokeStreamWrapperStreamStat(): void
    {
        $this->storage->allows()
            ->fetchStatCache()
            ->andReturn([
                __FILE__ => ['size' => 4321],
            ]);
        Ignition::start(
            rootProjectPath: dirname(__DIR__) . '/Fixtures/Direct/WithAutoHandoffDisablingPreflight',
            storage: $this->storage
        );

        $stream = fopen(__FILE__, 'rb');
        $stat = fstat($stream);

        static::assertEquals(4321, $stat['size']);
        static::assertEquals(4321, $stat[7]);
    }

    public function testCachedStatIsReturnedForChokeStreamWrapperUrlStat(): void
    {
        Ignition::start(
            rootProjectPath: dirname(__DIR__) . '/Fixtures/Direct/WithAutoHandoffDisablingPreflight',
            storage: $this->storage
        );

        $stat = stat('/my/first/path');

        static::assertEquals(1234, $stat['size']);
        static::assertEquals(1234, $stat[7]);
    }

    public function testHandoffTurnsOffChoke(): void
    {
        Ignition::start(
            rootProjectPath: dirname(__DIR__) . '/Fixtures/Direct/WithAutoHandoffDisablingPreflight',
            storage: $this->storage
        );

        Ignition::handOff();

        static::assertFalse(Ignition::isChokeOn());
    }

    public function testHandoffStoresNewlyCachedStatsFromChokeStreamWrapperStreamStat(): void
    {
        Ignition::start(
            rootProjectPath: dirname(__DIR__) . '/Fixtures/Direct/WithAutoHandoffDisablingPreflight',
            storage: $this->storage
        );
        $stream = fopen(__FILE__, 'rb');

        $this->storage->expects('saveStatCache')
            ->once()
            ->andReturnUsing(function (array $statCache) {
                static::assertArrayHasKey(__FILE__, $statCache);
                $stat = $statCache[__FILE__];
                static::assertSame((int) filesize(__FILE__), $stat['size']);
            });

        $stat = fstat($stream);
        Ignition::handOff();

        static::assertIsArray($stat);
    }

    public function testHandoffStoresNewlyCachedStatsFromChokeStreamWrapperUrlStat(): void
    {
        Ignition::start(
            rootProjectPath: dirname(__DIR__) . '/Fixtures/Direct/WithAutoHandoffDisablingPreflight',
            storage: $this->storage
        );

        $this->storage->expects('saveStatCache')
            ->once()
            ->andReturnUsing(function (array $statCache) {
                static::assertArrayHasKey(__FILE__, $statCache);
                $stat = $statCache[__FILE__];
                static::assertSame((int) filesize(__FILE__), $stat['size']);
            });

        $stat = stat(__FILE__);
        Ignition::handOff();

        static::assertIsArray($stat);
    }
}
