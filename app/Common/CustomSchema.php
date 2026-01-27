<?php

namespace App\Common;

use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;

class CustomSchema extends Schema
{
    /**
     * Get a schema builder instance for a connection.
     *
     * @param  string|null  $name
     * @return Builder
     */
    public static function connection($name)
    {
        return static::customizeBuilder(parent::connection($name));
    }

    /**
     * Get the default schema builder instance.
     * * @return Builder
     */
    protected static function getFacadeAccessor()
    {
        // This MUST be a string. 
        // We use the parent's accessor (which is 'db')
        return 'db';
    }

    /**
     * Overriding the call to get the facade root so we can 
     * inject the custom blueprint resolver.
     */
    public static function getFacadeRoot()
    {
        $builder = parent::getFacadeRoot()->connection()->getSchemaBuilder();
        return static::customizeBuilder($builder);
    }

    /**
     * Helper to attach the CustomBlueprint
     */
    protected static function customizeBuilder($builder)
    {
        $builder->blueprintResolver(static function ($table, $callback) {
            return new CustomBlueprint($table, $callback);
        });
        return $builder;
    }
}
