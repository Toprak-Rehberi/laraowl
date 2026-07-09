<?php

namespace App\Support;

class Version
{
    /**
     * The memoized version, so composer.json is only read once per process.
     */
    protected static ?string $current = null;

    /**
     * Get the version this instance of LaraOwl is running.
     *
     * The version key in composer.json is the single source of truth and is
     * bumped when a release is tagged.
     */
    public static function current(): string
    {
        return static::$current ??= static::readFromComposerFile();
    }

    /**
     * Normalize a version string for comparison, dropping any "v" prefix.
     */
    public static function normalize(string $version): string
    {
        return ltrim(trim($version), 'vV');
    }

    /**
     * Determine whether the given version is newer than the one running.
     */
    public static function isNewerThanCurrent(string $version): bool
    {
        return version_compare(
            static::normalize($version),
            static::normalize(static::current()),
            '>',
        );
    }

    /**
     * Forget the memoized version. Intended for tests.
     */
    public static function flush(): void
    {
        static::$current = null;
    }

    /**
     * Read the version key out of the root composer.json.
     */
    protected static function readFromComposerFile(): string
    {
        $path = base_path('composer.json');

        if (! is_readable($path)) {
            return '0.0.0';
        }

        $composer = json_decode((string) file_get_contents($path), true);

        return is_array($composer) && is_string($composer['version'] ?? null)
            ? $composer['version']
            : '0.0.0';
    }
}
