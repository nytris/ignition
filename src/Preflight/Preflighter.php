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

namespace Nytris\Ignition\Preflight;

use Nytris\Ignition\Config\ConfigResolverInterface;

/**
 * Class Preflighter.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class Preflighter implements PreflighterInterface
{
    public function __construct(
        private readonly ConfigResolverInterface $configResolver
    ) {
    }

    /**
     * @inheritDoc
     */
    public function runPreflights(string $rootProjectPath): void
    {
        $config = $this->configResolver->resolveIgnitionConfig($rootProjectPath);

        if ($config === null) {
            // No config found, so no preflights to run.
            return;
        }

        foreach ($config->getPreflights() as $preflight) {
            ($preflight->getRunCallback())();
        }
    }
}
