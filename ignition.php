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

/*
 * Entrypoint for Nytris Ignition.
 *
 * Intended to be included as the very first statement in an entrypoint PHP script,
 * e.g. a front controller, followed by a call to ::start(...) - see README for details.
 *
 * Contains core classes required in the pre-Composer-autoloader environment,
 * caching filesystem hits and also defining a minimal autoloader of its own.
 */
namespace Nytris\Ignition\Filesystem {

    use Nytris\Ignition\Ignition;
    use RuntimeException;

    /**
     * Class StreamWrapper.
     *
     * A stream wrapper that records filesystem stats during early Nytris boot.
     *
     * @author Dan Phillimore <dan@ovms.co>
     */
    class StreamWrapper
    {
        public const PROTOCOL = 'file';
        /**
         * @var resource|null
         */
        public $context;

        private ?string $path = null;
        /**
         * @var resource|null
         */
        private $wrappedResource;

        public static function register(): void
        {
            stream_wrapper_unregister(static::PROTOCOL);
            stream_wrapper_register(static::PROTOCOL, static::class);
        }

        /**
         * @return resource|false
         */
        public function stream_cast(int $cast_as)
        {
            return false;
        }

        public function stream_close(): void
        {
            if (!$this->wrappedResource) {
                return;
            }

            fclose($this->wrappedResource);

            $this->path = null;
            $this->wrappedResource = null;
        }

        public function stream_eof(): bool
        {
            if (!$this->wrappedResource) {
                return false;
            }

            return feof($this->wrappedResource);
        }

        public function stream_flush(): bool
        {
            if (!$this->wrappedResource) {
                return false;
            }

            return fflush($this->wrappedResource);
        }

        public function stream_open(
            string $path,
            string $mode,
            int $options,
            ?string &$openedPath
        ): bool {
            $useIncludePath = (bool)($options & STREAM_USE_PATH);
            $stream = $this->unwrapped(fn () => fopen($path, $mode, use_include_path: $useIncludePath));

            if ($stream === false) {
                return false;
            }

            $this->path = $path;
            $this->wrappedResource = $stream;

            if ($useIncludePath) {
                $metaData = stream_get_meta_data($stream);

                $openedPath = $metaData['uri'];
            }

            return true;
        }

        public function stream_read(int $count): string|false
        {
            if (!$this->wrappedResource) {
                return false;
            }

            return fread($this->wrappedResource, $count);
        }

        public function stream_seek(int $offset, int $whence = SEEK_SET): bool
        {
            if (!$this->wrappedResource) {
                return false;
            }

            return fseek($this->wrappedResource, $offset, $whence) !== -1;
        }

        public function stream_set_option(int $option, int $arg1, int|null $arg2): bool
        {
            if (!$this->wrappedResource) {
                return false;
            }

            return $this->unwrapped(
                fn () => match ($option) {
                    STREAM_OPTION_BLOCKING => stream_set_blocking($this->wrappedResource, (bool) $arg1),
                    STREAM_OPTION_READ_TIMEOUT => stream_set_timeout($this->wrappedResource, $arg1, $arg2),
                    STREAM_OPTION_WRITE_BUFFER => stream_set_write_buffer($this->wrappedResource, $arg1) === 0,
                    STREAM_OPTION_READ_BUFFER => stream_set_read_buffer($this->wrappedResource, $arg1) === 0,
                    default => false,
                }
            );
        }

        /**
         * Retrieves information about an open file resource.
         *
         * @see {@link https://www.php.net/manual/en/streamwrapper.stream-stat.php}
         *
         * @return array<mixed>|false
         */
        public function stream_stat(): array|false
        {
            if (!$this->wrappedResource) {
                return false;
            }

            $stat = Ignition::getCachedStat($this->path);

            if ($stat !== null) {
                return $stat;
            }

            $stat = fstat($this->wrappedResource);

            Ignition::cacheStat($this->path, $stat);

            return $stat;
        }

        public function stream_tell(): int|false
        {
            if (!$this->wrappedResource) {
                return false;
            }

            return ftell($this->wrappedResource);
        }

        public function stream_truncate(int $newSize): bool
        {
            if (!$this->wrappedResource) {
                return false;
            }

            return ftruncate($this->wrappedResource, $newSize);
        }

        public function stream_write(string $data): int|false
        {
            if (!$this->wrappedResource) {
                return false;
            }

            return fwrite($this->wrappedResource, $data);
        }

        public function unlink(string $path): bool
        {
            return $this->unwrapped(fn () => unlink($path));
        }

        public static function unregister(): void
        {
            @stream_wrapper_restore(static::PROTOCOL);
        }

        /**
         * Disables the stream wrapper while the given callback is executed,
         * allowing the native file:// protocol stream wrapper to be used for actual filesystem access.
         */
        public function unwrapped(callable $callback): mixed
        {
            static::unregister();

            try {
                return $callback();
            } finally {
                // Note that if we do not unregister again first following the above restore,
                // a segfault will be raised.
                static::register();
            }
        }

        /**
         * Retrieves information about a file from its path.
         *
         * @see {@link https://www.php.net/manual/en/streamwrapper.url-stat.php}
         *
         * @return array<mixed>|false
         */
        public function url_stat(string $path, int $flags): array|false
        {
            $stat = Ignition::getCachedStat($path);

            if ($stat !== null) {
                return $stat;
            }

            $link = (bool)($flags & STREAM_URL_STAT_LINK);
            $quiet = (bool)($flags & STREAM_URL_STAT_QUIET);

            /*
             * This additional call to file_exists(...) should not cause an additional native filesystem stat,
             * due to PHP's stat cache, which keeps the most recent file status,
             * and so will be reused below by stat(...)/lstat(...) if the file does exist.
             *
             * This prevents the (l)stat call from raising a warning whose suppression below
             * is then potentially overridden by a custom error handler.
             */
            if ($quiet && !$this->unwrapped(static fn () => file_exists($path))) {
                return false;
            }

            // Use lstat(...) for links but stat() for other files.
            $doStat = static function () use ($link, $path) {
                try {
                    return $link ?
                        lstat($path) :
                        stat($path);
                } catch (RuntimeException) {
                    /*
                     * Stream wrapper must have been invoked by SplFileInfo::__construct(),
                     * which raises RuntimeExceptions in place of warnings
                     * such as `RuntimeException: stat(): stat failed for .../non_existent.txt`.
                     */
                    return false;
                }
            };

            // Suppress warnings/notices if quiet flag is set.
            $stat = $this->unwrapped(
                $quiet ?
                    static fn () => @$doStat() :
                    $doStat
            );

            Ignition::cacheStat($path, $stat);

            return $stat;
        }
    }
}

namespace Nytris\Ignition\Storage {

    /**
     * Interface StorageInterface.
     *
     * Stores the filesystem stat cache for Ignition.
     *
     * @author Dan Phillimore <dan@ovms.co>
     */
    interface StorageInterface
    {
        /**
         * Fetches the stat cache from the backing store, if it has been stored yet.
         *
         * @return array<mixed>|null
         */
        public function fetchStatCache(): ?array;

        /**
         * Determines whether the storage mechanism is supported.
         */
        public function isSupported(): bool;

        /**
         * Stores a new stat cache to the backing store.
         *
         * @param array<mixed> $statCache
         */
        public function saveStatCache(array $statCache): void;
    }

    /**
     * Class ApcuStorage.
     *
     * Stores the filesystem stat cache for Ignition in APCu.
     *
     * @author Dan Phillimore <dan@ovms.co>
     */
    class ApcuStorage implements StorageInterface
    {
        public function __construct(
            private readonly string $apcuNamespace = 'nytris.ignition.stat'
        ) {
        }

        /**
         * @inheritDoc
         */
        public function fetchStatCache(): ?array
        {
            $statCache = apcu_fetch($this->apcuNamespace, success: $success);

            return $success ? $statCache : null;
        }

        /**
         * @inheritDoc
         */
        public function isSupported(): bool
        {
            return function_exists('apcu_enabled') && apcu_enabled();
        }

        /**
         * @inheritDoc
         */
        public function saveStatCache(array $statCache): void
        {
            if (apcu_store($this->apcuNamespace, $statCache) === false) {
                trigger_error('Failed to save Nytris Ignition cache in APCu', E_USER_ERROR);
            }
        }
    }
}

namespace Nytris\Ignition\Choke {

    use Asmblah\PhpCodeShift\Shifter\Stream\Native\StreamWrapper as ShiftStreamWrapper;
    use Closure;
    use LogicException;
    use Nytris\Ignition\Autoloader\EarlyAutoloaderInterface;
    use Nytris\Ignition\Filesystem\StreamWrapper;
    use Nytris\Ignition\Storage\StorageInterface;

    /**
     * Interface ChokeInterface.
     *
     * Caches filesystem stats during early Nytris ignition and then boot.
     *
     * @author Dan Phillimore <dan@ovms.co>
     */
    interface ChokeInterface
    {
        /**
         * Adds a path to the stat cache.
         *
         * @param string $path
         * @param array<mixed>|false $stat
         */
        public function cacheStat(string $path, array|false $stat): void;

        /**
         * Fetches a stat from the stat cache.
         *
         * @param string $path
         * @return array<mixed>|false|null Returns array on success, false when cached as non-accessible or null on miss.
         */
        public function getCachedStat(string $path): array|false|null;

        /**
         * Fetches the in-memory contents of the stat cache.
         *
         * @return array<string, array<mixed>|false>
         */
        public function getStatCache(): array;

        /**
         * Turns off choke.
         */
        public function turnOff(): void;

        /**
         * Turns on choke.
         */
        public function turnOn(): void;
    }

    /**
     * Class Choke.
     *
     * Caches filesystem stats during early Nytris ignition and then boot.
     *
     * @author Dan Phillimore <dan@ovms.co>
     */
    class Choke implements ChokeInterface
    {
        private ?EarlyAutoloaderInterface $earlyAutoloader;
        private bool $isOn = false;
        /**
         * @var array<string, array<mixed>|false>
         */
        private array $statCache;
        private bool $statCacheIsDirty = false;

        public function __construct(
            private readonly StorageInterface $storage,
            private readonly Closure $earlyAutoloaderProvider
        ) {
            $this->statCache = $storage->fetchStatCache() ?? [];
        }

        /**
         * @inheritDoc
         */
        public function cacheStat(string $path, array|false $stat): void
        {
            $this->statCache[$path] = $stat;
            $this->statCacheIsDirty = true;
        }

        /**
         * @inheritDoc
         */
        public function getCachedStat(string $path): array|false|null
        {
            return $this->statCache[$path] ?? null;
        }

        /**
         * @inheritDoc
         */
        public function getStatCache(): array
        {
            return $this->statCache;
        }

        /**
         * @inheritDoc
         */
        public function turnOff(): void
        {
            if (!$this->isOn) {
                throw new LogicException('Nytris Ignition choke is not currently on');
            }

            if (!class_exists(ShiftStreamWrapper::class) || !ShiftStreamWrapper::isRegistered()) {
                @stream_wrapper_restore('file');
            }

            if ($this->statCacheIsDirty) {
                $this->storage->saveStatCache($this->statCache);
                $this->statCacheIsDirty = false;
            }

            $this->statCache = [];

            $this->earlyAutoloader->unregister();

            $this->isOn = false;
        }

        /**
         * @inheritDoc
         */
        public function turnOn(): void
        {
            StreamWrapper::register();

            // With the stream wrapper installed, the autoloader modules are now safe to require.
            $this->earlyAutoloader = ($this->earlyAutoloaderProvider)();

            $this->earlyAutoloader->register();

            $this->isOn = true;
        }
    }
}

namespace Nytris\Ignition\Implementation {

    use Closure;
    use Nytris\Ignition\Autoloader\EarlyAutoloader;
    use Nytris\Ignition\Choke\Choke;
    use Nytris\Ignition\Choke\ChokeInterface;
    use Nytris\Ignition\Config\ConfigResolver;
    use Nytris\Ignition\Includer\Includer;
    use Nytris\Ignition\Preflight\Preflighter;
    use Nytris\Ignition\Preflight\PreflighterInterface;
    use Nytris\Ignition\Storage\StorageInterface;

    /**
     * Interface ImplementationInterface.
     *
     * Defines the implementation of Nytris Ignition.
     *
     * @author Dan Phillimore <dan@ovms.co>
     */
    interface ImplementationInterface
    {
        /**
         * Fetches the Choke implementation.
         */
        public function getChoke(string $rootProjectPath): ChokeInterface;

        /**
         * Fetches the Preflighter implementation.
         */
        public function getPreflighter(): PreflighterInterface;
    }

    /**
     * Class DefaultImplementation.
     *
     * Defines the default implementation of Nytris Ignition.
     *
     * @author Dan Phillimore <dan@ovms.co>
     */
    class DefaultImplementation implements ImplementationInterface
    {
        public function __construct(
            private readonly StorageInterface $storage,
            private ?ChokeInterface $choke = null,
            private ?PreflighterInterface $preflighter = null
        ) {
        }

        /**
         * @inheritDoc
         */
        public function getChoke(string $rootProjectPath): ChokeInterface
        {
            $this->choke ??= new Choke(
                $this->storage,
                function () use ($rootProjectPath) {
                    // With the stream wrapper installed, these are now safe to require.
                    // After this point, the early autoloader can then take over.
                    require_once __DIR__ . '/src/Autoloader/EarlyAutoloaderInterface.php';
                    require_once __DIR__ . '/src/Autoloader/EarlyAutoloader.php';

                    $psr4MappingsPath = $rootProjectPath . '/vendor/composer/autoload_psr4.php';

                    $psr4Mappings = file_exists($psr4MappingsPath) ?
                        // Note we cannot use Includer here due to a catch-22.
                        Closure::bind(static fn () => require $psr4MappingsPath, null, null)() :
                        [
                            'Nytris\\Ignition\\' => [__DIR__ . '/src'],
                        ];

                    // TODO: Optimise mappings structure and cache optimised structure via configured Storage e.g. APCu.

                    return new EarlyAutoloader($psr4Mappings);
                }
            );

            return $this->choke;
        }

        /**
         * @inheritDoc
         */
        public function getPreflighter(): PreflighterInterface
        {
            $this->preflighter ??= new Preflighter(new ConfigResolver(new Includer()));

            return $this->preflighter;
        }
    }
}

namespace Nytris\Ignition {

    use Closure;
    use LogicException;
    use Nytris\Ignition\Choke\ChokeInterface;
    use Nytris\Ignition\Implementation\DefaultImplementation;
    use Nytris\Ignition\Storage\ApcuStorage;
    use Nytris\Ignition\Storage\StorageInterface;

    /**
     * Class Ignition.
     *
     * Initial entrypoint for the library.
     *
     * @author Dan Phillimore <dan@ovms.co>
     */
    class Ignition
    {
        private static bool $autoHandoffEnabled = true;
        private static ?ChokeInterface $choke = null;
        private static bool $isStarted = false;

        /**
         * Adds a path to the stat cache.
         *
         * @param string $path
         * @param array<mixed>|false $stat
         */
        public static function cacheStat(string $path, array|false $stat): void
        {
            if (self::$choke === null) {
                throw new LogicException('Nytris Ignition choke is not currently on');
            }

            self::$choke->cacheStat($path, $stat);
        }

        /**
         * Disables auto-handoff.
         */
        public static function disableAutoHandoff(): void
        {
            self::$autoHandoffEnabled = false;
        }

        /**
         * Fetches a stat from the stat cache.
         *
         * @param string $path
         * @return array<mixed>|false|null Returns array on success, false when cached as non-accessible or null on miss.
         */
        public static function getCachedStat(string $path): array|false|null
        {
            if (self::$choke === null) {
                throw new LogicException('Nytris Ignition choke is not currently on');
            }

            return self::$choke->getCachedStat($path);
        }

        /**
         * Fetches the in-memory contents of the stat cache.
         *
         * @return array<string, array<mixed>|false>
         */
        public static function getStatCache(): array
        {
            if (self::$choke === null) {
                throw new LogicException('Nytris Ignition choke is not currently on');
            }

            return self::$choke->getStatCache();
        }

        /**
         * Hands off from Ignition.
         */
        public static function handOff(): void
        {
            if (!self::$isStarted) {
                throw new LogicException('Nytris Ignition has not been started');
            }

            if (self::$choke === null) {
                throw new LogicException('Nytris Ignition choke is not currently on');
            }

            self::$choke->turnOff();
            self::$choke = null;
        }

        /**
         * Fetches whether auto-handoff is currently on.
         */
        public static function isAutoHandoffEnabled(): bool
        {
            return self::$autoHandoffEnabled;
        }

        /**
         * Fetches whether choke is currently on.
         */
        public static function isChokeOn(): bool
        {
            return self::$choke !== null;
        }

        /**
         * Fetches whether Ignition has been started.
         */
        public static function isStarted(): bool
        {
            return self::$isStarted;
        }

        /**
         * Turns on choke - starts caching filesystem stats.
         */
        public static function start(
            string $rootProjectPath,
            StorageInterface $storage = new ApcuStorage(),
            ?Closure $implementationProvider = null
        ): void {
            if (self::$isStarted) {
                throw new LogicException('Nytris Ignition already started');
            }

            self::$isStarted = true;

            if (!$storage->isSupported()) {
                // Storage is not supported, so we cannot start Ignition.
                // e.g. when running under CLI and APCu is not enabled for CLI.
                return;
            }

            $implementation = $implementationProvider ?
                $implementationProvider($storage) :
                new DefaultImplementation($storage);

            self::$choke = $implementation->getChoke($rootProjectPath);
            self::$choke->turnOn();

            $implementation->getPreflighter()->runPreflights($rootProjectPath);

            if (self::$autoHandoffEnabled) {
                self::handOff();
            }
        }

        /**
         * Switches off Nytris Ignition. Differs from ->handOff() in that choke does not need to be on first.
         */
        public static function switchOff(): void
        {
            if (!self::$isStarted) {
                throw new LogicException('Nytris Ignition has not been started');
            }

            self::$choke?->turnOff();
            self::$choke = null;
            self::$isStarted = false;
        }
    }
}
