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

namespace Nytris\Ignition;

use Nytris\Ignition\Preflight\PreflightInterface;

/**
 * Interface IgnitionConfigInterface.
 *
 * Configures Nytris Ignition.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface IgnitionConfigInterface
{
    /**
     * Fetches the installed preflights.
     *
     * @return PreflightInterface[]
     */
    public function getPreflights(): array;

    /**
     * Installs a preflight.
     */
    public function installPreflight(PreflightInterface $preflight): void;
}
