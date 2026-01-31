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

namespace Nytris\Ignition\Config;

use LogicException;
use Nytris\Ignition\IgnitionConfigInterface;
use Nytris\Ignition\Includer\IncluderInterface;

/**
 * Class ConfigResolver.
 *
 * Resolves the ignition config from nytris.ignition.php, if present.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ConfigResolver implements ConfigResolverInterface
{
    public function __construct(
        private readonly IncluderInterface $includer,
        private readonly string $configFileName = 'nytris.ignition.php'
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolveIgnitionConfig(string $projectRootPath): ?IgnitionConfigInterface
    {
        $configPath = $projectRootPath . DIRECTORY_SEPARATOR . $this->configFileName;

        if (!is_file($configPath)) {
            // Nytris Ignition config isn't present: nothing to do.
            return null;
        }

        $ignitionConfig = $this->includer->isolatedInclude($configPath);

        if (!($ignitionConfig instanceof IgnitionConfigInterface)) {
            throw new LogicException(
                sprintf(
                    'Return value of module %s is expected to be an instance of %s but was not',
                    $configPath,
                    IgnitionConfigInterface::class
                )
            );
        }

        return $ignitionConfig;
    }
}
