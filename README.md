# Nytris Ignition

[![Build Status](https://github.com/nytris/ignition/workflows/CI/badge.svg)](https://github.com/nytris/ignition/actions?query=workflow%3ACI)

Caches filesystem hits during early Nytris boot when `open_basedir` is enabled,
prior to Nytris Antilag and later Boost starting.
Installs a minimal autoloader for use in the pre-Composer-autoloader environment.

## Usage
Install this package with Composer:

```shell
$ composer require nytris/ignition
```

### Configure Nytris Ignition:

`nytris.ignition.php`

```php
<?php

declare(strict_types=1);

use Nytris\Antilag\AntilagPreflight;
use Nytris\Antilag\Stage;
use Nytris\Ignition\IgnitionConfig;

$ignitionConfig = new IgnitionConfig();

// Install preflights - Antilag is recommended.
$ignitionConfig->installPreflight(new AntilagPreflight(stage: Stage::STAGE_1));

return $ignitionConfig;
```

### Invoke Ignition as early as possible

e.g. from a front controller:

`app.php`
```php
<?php

if (getenv('ENABLE_NYTRIS_IGNITION') !== 'no') {
    require dirname(__DIR__) . '/vendor/nytris/ignition/ignition.php';
    Ignition::start(dirname(__DIR__));
}

require dirname(__DIR__) . '/vendor/autoload.php';

// Using Symfony as an example:
$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
// ...
```

## See also

- [Nytris Antilag][Nytris Antilag]
- [Nytris Boost][Nytris Boost]

[Nytris Antilag]: https://github.com/nytris/antilag
[Nytris Boost]: https://github.com/nytris/boost
