<?php

namespace App\Traits;

use App\Scopes\EnabledScope;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait that provides an optional enabled global scope and helper methods.
 *
 * Default behavior is to NOT apply the global scope automatically.
 * You can enable the scope globally, enable it temporarily for a closure,
 * or apply it per-query using the scopeOnlyEnabled query scope.
 */
trait HasOptionalEnabledScope
{
    /**
     * Runtime flag that controls whether the global scope should be applied.
     *
     * Default false means the global scope is not applied automatically.
     */
    protected static bool $applyEnabledScope = false;

    /**
     * Boot method for the trait.
     *
     * If the runtime flag is true at boot time, register the EnabledScope.
     */
    public static function bootHasOptionalEnabledScope(): void
    {
        if (static::$applyEnabledScope) {
            static::addGlobalScope(new EnabledScope);
        }
    }

    /**
     * Enable the enabled global scope for this model class.
     *
     * This will add the scope immediately and affect subsequent queries.
     */
    public static function enableEnabledScope(): void
    {
        static::$applyEnabledScope = true;

        // Add the global scope dynamically if possible
        if (method_exists(static::class, 'addGlobalScope')) {
            static::addGlobalScope(new EnabledScope);
        }
    }

    /**
     * Disable the enabled global scope for this model class.
     *
     * Attempts to remove the scope if it was previously added.
     */
    public static function disableEnabledScope(): void
    {
        static::$applyEnabledScope = false;

        if (method_exists(static::class, 'removeGlobalScope')) {
            try {
                static::removeGlobalScope(EnabledScope::class);
            } catch (\Throwable $e) {
                // Ignore removal errors to keep behavior predictable across environments
            }
        }
    }

    /**
     * Execute a callback with the enabled global scope temporarily active.
     *
     * The previous state of the flag is restored after the callback finishes.
     *
     * Example usage:
     *   Contact::withEnabledScope(fn () => Contact::all());
     *
     * Returns whatever the callback returns.
     */
    public static function withEnabledScope(Closure $callback)
    {
        $previous = static::$applyEnabledScope;
        static::enableEnabledScope();

        try {
            return $callback();
        } finally {
            static::$applyEnabledScope = $previous;

            // If the scope was not active before, try to remove it now
            if (! $previous && method_exists(static::class, 'removeGlobalScope')) {
                try {
                    static::removeGlobalScope(EnabledScope::class);
                } catch (\Throwable $e) {
                    // Ignore removal errors
                }
            }
        }
    }

    /**
     * Query scope to filter only records where enabled = true for this query.
     *
     * If the table does not have an 'enabled' column, the query is returned unchanged.
     *
     * Example usage:
     *   Contact::onlyEnabled()->get();
     */
    public function scopeOnlyEnabled(Builder $query): Builder
    {
        $model = $query->getModel();
        $connection = $model->getConnection();
        static $hasColumnCache = [];

        $key = $connection->getName().'|'.$model->getTable();

        if (! array_key_exists($key, $hasColumnCache)) {
            $hasColumnCache[$key] = $connection->getSchemaBuilder()->hasColumn($model->getTable(), 'enabled');
        }

        if ($hasColumnCache[$key]) {
            return $query->where($model->getTable().'.enabled', true);
        }

        return $query;
    }
}
