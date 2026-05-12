<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('display_name', 64)->index();
            $table->string('handle', 32)->unique();
            $table->text('bio')->nullable();
            $table->string('avatar_url', 512)->nullable();
            $table->string('avatar_blurhash', 255)->nullable();
            $table->boolean('is_private')->default(false);
            $table->geometry('location', subtype: 'point', srid: 4326)->nullable();
            $table->string('city', 120)->nullable()->index();
            $table->timestamps();
            $table->index('handle');
        });

        $client = app(Client::class);
        $schema = [
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

        try {
            $client->collections['users']->delete();
        } catch (ObjectNotFound) {
            //
        }

        $client->collections->create($schema);

        /*
         * MySQL : un index SPATIAL exige une colonne NOT NULL (erreur 1252 si `location` est nullable).
         * Index fonctionnel sur lat/lng dérivés du POINT (MySQL 8.0.13+) pour accélérer les filtres par borne / proximité.
         */
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement(
                'CREATE INDEX user_profiles_location_lat_lng_idx ON user_profiles ((ST_Latitude(`location`)), (ST_Longitude(`location`)))'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
