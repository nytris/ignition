<?php

declare(strict_types=1);

use Nytris\Ignition\Storage\StorageInterface;

return [
    'getStorage' => static function (bool $supported = true) {
        return new class($supported) implements StorageInterface {
            public function __construct(
                private readonly bool $supported
            ) {
            }

            public function fetchStatCache(): ?array
            {
                return [
                    '/my/first/path' => ['size' => 1234],
                ];
            }

            public function isSupported(): bool
            {
                return $this->supported;
            }

            public function saveStatCache(array $statCache): void
            {
            }
        };
    },
];
