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

use Closure;

/**
 * Interface PreflightInterface.
 *
 * Configures a preflight for Nytris Ignition.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface PreflightInterface
{
    /**
     * Fetches the name of the preflight.
     */
    public function getName(): string;

    /**
     * Fetches the callback to run the preflight.
     *
     * @return Closure(): void
     */
    public function getRunCallback(): Closure;

    /**
     * Fetches the vendor of the preflight.
     */
    public function getVendor(): string;
}
