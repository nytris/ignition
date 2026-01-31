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

use Closure;
use LogicException;

/**
 * Class EarlyAutoloader.
 *
 * An autoloader for Nytris Ignition, for use in the early pre-Composer-autoloader environment.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class EarlyAutoloader implements EarlyAutoloaderInterface
{
    private ?Closure $autoloadCallback = null;

    /**
     * @param array<string, string[]> $psr4Mappings
     */
    public function __construct(
        private array $psr4Mappings
    ) {
    }

    /**
     * @inheritDoc
     */
    public function addPsr4AutoloadMappings(array $mappings): void
    {
        $this->psr4Mappings = array_merge($this->psr4Mappings, $mappings);
    }

    /**
     * @inheritDoc
     */
    public function register(): void
    {
        if ($this->autoloadCallback !== null) {
            throw new LogicException('Nytris Ignition early autoloader is already registered');
        }

        $this->autoloadCallback = function ($class) {
            // Check the PSR-4 prefix mappings.
            // TODO: Optimise - see note in ignition.php.
            foreach ($this->psr4Mappings as $namespace => $paths) {
                foreach ($paths as $path) {
                    if (str_starts_with($class, $namespace)) {
                        $relativePath = substr($class, strlen($namespace));
                        $modulePath = $path . '/' . str_replace('\\', '/', $relativePath) . '.php';

                        if (file_exists($modulePath)) {
                            require $modulePath;

                            return;
                        }
                    }
                }
            }
        };

        spl_autoload_register($this->autoloadCallback);
    }

    /**
     * @inheritDoc
     */
    public function unregister(): void
    {
        if ($this->autoloadCallback === null) {
            throw new LogicException('Nytris Ignition early autoloader is not currently registered');
        }

        // TODO: Handle scenario where this has been wrapped by Overdrive.
        spl_autoload_unregister($this->autoloadCallback);

        $this->autoloadCallback = null;
    }
}
