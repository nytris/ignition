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

namespace Nytris\Ignition\Includer;

use Closure;

/**
 * Class Includer.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class Includer implements IncluderInterface
{
    /**
     * @var callable
     */
    private $include;

    public function __construct()
    {
        $this->include = Closure::bind(static fn ($path) => include $path, null, null);
    }

    /**
     * @inheritDoc
     */
    public function isolatedInclude(string $path): mixed
    {
        return ($this->include)($path);
    }
}
