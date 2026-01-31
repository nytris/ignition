<?php

declare(strict_types=1);

use Nytris\Ignition\Filesystem\StreamWrapper;
use Nytris\Ignition\Ignition;

require_once dirname(__DIR__, 5) . '/ignition.php';
$helpers = require __DIR__ . '/helpers.php';

$storage = $helpers['getStorage']();

Ignition::start(
    rootProjectPath: __DIR__,
    storage: $storage
);

$stream = fopen(__FILE__, 'rb');
$metadata = stream_get_meta_data($stream);
fclose($stream);

print serialize([
    'wrapper_data instanceof StreamWrapper' => $metadata['wrapper_data'] instanceof StreamWrapper
]);
