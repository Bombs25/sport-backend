<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute une colonne STORED generated `location_spatial` (NOT NULL, SRID 4326)
 * et un SPATIAL INDEX (R-Tree) dessus.
 *
 * Pourquoi une colonne dérivée ?
 * MySQL exige NOT NULL pour créer un SPATIAL INDEX, mais `location` est nullable
 * (les utilisateurs sans position sont valides). La colonne générée remplace NULL
 * par POINT(0 0) (océan Atlantique) — ces lignes sont exclues en aval par
 * `whereNotNull('location')` dans les requêtes métier.
 *
 * Pourquoi STORED et pas VIRTUAL ?
 * MySQL ne supporte les SPATIAL INDEX que sur des colonnes STORED (ou réelles).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE user_profiles
             ADD COLUMN location_spatial POINT
                 GENERATED ALWAYS AS (IFNULL(`location`, ST_GeomFromText('POINT(0 0)', 4326)))
                 STORED NOT NULL
                 SRID 4326"
        );

        DB::statement(
            'CREATE SPATIAL INDEX idx_user_profiles_location_spatial ON user_profiles (location_spatial)'
        );
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('DROP INDEX idx_user_profiles_location_spatial ON user_profiles');
        DB::statement('ALTER TABLE user_profiles DROP COLUMN location_spatial');
    }
};
