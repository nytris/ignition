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

use Nytris\Ignition\IgnitionConfigInterface;

/**
 * Interface ConfigResolverInterface.
 *
 * Resolves the ignition config from nytris.ignition.php, if present.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface ConfigResolverInterface
{
    /**
     * Resolves the ignition config, if present.
     */
    public function resolveIgnitionConfig(string $projectRootPath): ?IgnitionConfigInterface;
}
