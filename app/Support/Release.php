<?php

namespace App\Support;

use Illuminate\Contracts\Support\Arrayable;

/**
 * A published LaraOwl release, as reported by the GitHub releases API.
 *
 * @implements Arrayable<string, string|null>
 */
readonly class Release implements Arrayable
{
    public function __construct(
        public string $version,
        public string $name,
        public string $url,
        public ?string $notes = null,
        public ?string $publishedAt = null,
    ) {
        //
    }

    /**
     * Build a release from a GitHub releases API payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromGitHub(array $payload): ?self
    {
        $tag = $payload['tag_name'] ?? null;

        if (! is_string($tag) || $tag === '') {
            return null;
        }

        return new self(
            version: Version::normalize($tag),
            name: is_string($payload['name'] ?? null) && $payload['name'] !== '' ? $payload['name'] : $tag,
            url: is_string($payload['html_url'] ?? null) ? $payload['html_url'] : '',
            notes: is_string($payload['body'] ?? null) ? $payload['body'] : null,
            publishedAt: is_string($payload['published_at'] ?? null) ? $payload['published_at'] : null,
        );
    }

    /**
     * Rebuild a release from its array form.
     *
     * Releases are cached as plain arrays rather than objects, because the
     * cache forbids unserializing classes. See config/cache.php.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): ?self
    {
        if (! is_string($payload['version'] ?? null) || $payload['version'] === '') {
            return null;
        }

        return new self(
            version: $payload['version'],
            name: is_string($payload['name'] ?? null) ? $payload['name'] : $payload['version'],
            url: is_string($payload['url'] ?? null) ? $payload['url'] : '',
            notes: is_string($payload['notes'] ?? null) ? $payload['notes'] : null,
            publishedAt: is_string($payload['publishedAt'] ?? null) ? $payload['publishedAt'] : null,
        );
    }

    /**
     * Determine whether this release is newer than the running version.
     */
    public function isNewerThanCurrent(): bool
    {
        return Version::isNewerThanCurrent($this->version);
    }

    /**
     * @return array{version: string, name: string, url: string, notes: string|null, publishedAt: string|null}
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'name' => $this->name,
            'url' => $this->url,
            'notes' => $this->notes,
            'publishedAt' => $this->publishedAt,
        ];
    }
}
