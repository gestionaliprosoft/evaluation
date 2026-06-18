<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class EnabledScope implements Scope
{
    /**
     * In-memory cache to avoid repeated schema checks per request.
     *
     * Key format: connection_name|table_name
     *
     * This prevents repeated calls to the schema builder for the same table.
     */
    protected static array $hasColumnCache = [];

    /**
     * Check if the model's table has an 'enabled' column.
     *
     * Uses an in-memory static cache for performance.
     */
    protected function tableHasEnabledColumn(Model $model): bool
    {
        $connection = $model->getConnection();
        $key = $connection->getName().'|'.$model->getTable();

        if (! array_key_exists($key, self::$hasColumnCache)) {
            self::$hasColumnCache[$key] = $connection->getSchemaBuilder()->hasColumn($model->getTable(), 'enabled');
        }

        return self::$hasColumnCache[$key];
    }

    /**
     * Apply the scope to the query builder.
     *
     * If the table contains an 'enabled' column, add a where clause
     * to filter only records where enabled = true.
     *
     * If the column does not exist, do nothing.
     */
    public function apply(Builder $builder, Model $model)
    {
        if ($this->tableHasEnabledColumn($model)) {
            $builder->where($model->getTable().'.enabled', true);
        }
    }
}
