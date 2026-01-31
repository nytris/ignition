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

require_once __DIR__ . '/../vendor/autoload.php';

Mockery::getConfiguration()->allowMockingNonExistentMethods(false);
Mockery::globalHelpers();

// All required classes are defined in a single file to reduce unavoidable filesystem access
// which can occur even when OPcache is enabled.
require dirname(__DIR__) . '/ignition.php';
