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

namespace Nytris\Ignition\Tests\Functional;

use Nytris\Ignition\Tests\AbstractTestCase;

/**
 * Class AbstractFunctionalTestCase.
 *
 * Base class for all functional test cases.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
abstract class AbstractFunctionalTestCase extends AbstractTestCase
{
    protected function rimrafDescendantsOf(string $path): void
    {
        foreach (glob($path . '/**') as $subPath) {
            if (is_file($subPath) || is_link($subPath)) {
                unlink($subPath);
            } else {
                $this->rimrafDescendantsOf($subPath);

                rmdir($subPath);
            }
        }
    }
}
