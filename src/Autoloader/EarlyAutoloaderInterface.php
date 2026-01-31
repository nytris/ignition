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

namespace Nytris\Ignition\Autoloader;

/**
 * Interface EarlyAutoloaderInterface.
 *
 * An autoloader for Nytris Ignition, for use in the early pre-Composer-autoloader environment.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface EarlyAutoloaderInterface
{
    /**
     * Adds PSR-4 autoload mappings.
     *
     * @param array<string, string[]> $mappings
     */
    public function addPsr4AutoloadMappings(array $mappings): void;

    /**
     * Registers the autoloader.
     */
    public function register(): void;

    /**
     * Unregisters the autoloader.
     */
    public function unregister(): void;
}
