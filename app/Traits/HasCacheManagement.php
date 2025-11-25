<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait HasCacheManagement
{
    /**
     * Get cache tags for this model (only if cache driver supports tagging)
     */
    public function getCacheTags(): array
    {
        $tags = [strtolower(class_basename(static::class)) . 's'];

        // Add specific instance tag if model has ID
        if ($this->exists) {
            $tags[] = strtolower(class_basename(static::class)) . "_{$this->getKey()}";
        }

        return $tags;
    }

    /**
     * Get cache keys for this model (for non-tagging cache drivers)
     */
    public function getCacheKeys(): array
    {
        $keys = [strtolower(class_basename(static::class)) . '_cache_all'];

        // Add specific instance key if model has ID
        if ($this->exists) {
            $keys[] = strtolower(class_basename(static::class)) . "_cache_{$this->getKey()}";
        }

        return $keys;
    }

    /**
     * Check if cache driver supports tagging
     */
    public function cacheSupportsTagging(): bool
    {
        $driver = config('cache.default');
        return in_array($driver, ['redis', 'memcached']);
    }

    /**
     * Clear cache for this model instance
     */
    public function clearCache(): void
    {
        if ($this->cacheSupportsTagging()) {
            Cache::tags($this->getCacheTags())->flush();
        } else {
            // For non-tagging drivers, forget specific keys
            foreach ($this->getCacheKeys() as $key) {
                Cache::forget($key);
            }

            // Also clear model-specific pattern keys
            $this->clearModelPatternCache();
        }
    }

    /**
     * Clear cache for all instances of this model
     */
    public static function clearAllCache(): void
    {
        $instance = new static();

        if ($instance->cacheSupportsTagging()) {
            Cache::tags([strtolower(class_basename(static::class)) . 's'])->flush();
        } else {
            // For non-tagging drivers, clear pattern-based keys
            $modelName = strtolower(class_basename(static::class));
            $keys = [
                $modelName . '_cache_all',
                $modelName . 's_active',
                'dashboard_summary',
            ];

            foreach ($keys as $key) {
                Cache::forget($key);
            }
        }
    }

    /**
     * Clear model-specific pattern cache
     */
    private function clearModelPatternCache(): void
    {
        $modelName = strtolower(class_basename(static::class));

        // Common cache patterns to clear
        $patterns = [
            $modelName . 's_active',
            $modelName . '_hierarchy_',
            $modelName . '_by_type_',
            'dashboard_summary'
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }

        // Model-specific patterns
        if (method_exists($this, 'getAdditionalCachePatterns')) {
            foreach ($this->getAdditionalCachePatterns() as $pattern) {
                Cache::forget($pattern);
            }
        }
    }

    /**
     * Remember cache with model-specific handling
     */
    public function remember(string $key, $ttl, callable $callback)
    {
        if ($this->cacheSupportsTagging()) {
            return Cache::tags($this->getCacheTags())->remember($key, $ttl, $callback);
        } else {
            return Cache::remember($key, $ttl, $callback);
        }
    }

    /**
     * Static remember cache with model-specific handling
     */
    public static function rememberStatic(string $key, $ttl, callable $callback)
    {
        $instance = new static();

        if ($instance->cacheSupportsTagging()) {
            return Cache::tags([strtolower(class_basename(static::class)) . 's'])->remember($key, $ttl, $callback);
        } else {
            return Cache::remember($key, $ttl, $callback);
        }
    }

    /**
     * Get cache instance with tags if supported
     */
    public function getCacheInstance()
    {
        if ($this->cacheSupportsTagging()) {
            return Cache::tags($this->getCacheTags());
        } else {
            return Cache::getFacadeRoot();
        }
    }

    /**
     * Get static cache instance with tags if supported
     */
    public static function getStaticCacheInstance()
    {
        $instance = new static();

        if ($instance->cacheSupportsTagging()) {
            return Cache::tags([strtolower(class_basename(static::class)) . 's']);
        } else {
            return Cache::getFacadeRoot();
        }
    }
}
