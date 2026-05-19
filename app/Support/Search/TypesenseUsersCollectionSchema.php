<?php

namespace App\Support\Search;

/**
 * Schéma unique de la collection Typesense `users` (migration + service de sync).
 */
final class TypesenseUsersCollectionSchema
{
    /**
     * @return array{
     *     name: string,
     *     fields: list<array<string, mixed>>,
     *     default_sorting_field: string
     * }
     */
    public static function definition(): array
    {
        return [
            'name' => 'users',
            'fields' => [
                ['name' => 'id', 'type' => 'int64'],
                ['name' => 'name', 'type' => 'string'],
                ['name' => 'email', 'type' => 'string', 'index' => false],
                ['name' => 'display_name', 'type' => 'string'],
                ['name' => 'handle', 'type' => 'string'],
                ['name' => 'bio', 'type' => 'string', 'optional' => true],
                ['name' => 'avatar_url', 'type' => 'string', 'optional' => true, 'index' => false],
                ['name' => 'avatar_blurhash', 'type' => 'string', 'optional' => true, 'index' => false],
                ['name' => 'is_private', 'type' => 'bool', 'facet' => true],
                ['name' => 'city', 'type' => 'string', 'facet' => true, 'optional' => true],
                ['name' => 'location', 'type' => 'geopoint', 'optional' => true],
                ['name' => 'created_at', 'type' => 'int64'],
            ],
            'default_sorting_field' => 'created_at',
        ];
    }
}
