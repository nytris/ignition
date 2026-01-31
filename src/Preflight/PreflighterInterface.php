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

/**
 * Interface PreflighterInterface.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface PreflighterInterface
{
    /**
     * Runs all preflights.
     */
    public function runPreflights(string $rootProjectPath): void;
}
