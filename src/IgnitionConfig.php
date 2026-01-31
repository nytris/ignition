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
 * Class IgnitionConfig.
 *
 * Configures Nytris Ignition.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class IgnitionConfig implements IgnitionConfigInterface
{
    /**
     * @var PreflightInterface[]
     */
    private array $preflights = [];

    /**
     * @inheritDoc
     */
    public function getPreflights(): array
    {
        return $this->preflights;
    }

    /**
     * @inheritDoc
     */
    public function installPreflight(PreflightInterface $preflight): void
    {
        $this->preflights[] = $preflight;
    }
}
