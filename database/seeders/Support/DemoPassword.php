<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\Hash;

/**
 * Mot de passe unique pour tous les comptes créés par `migrate:fresh --seed`.
 */
final class DemoPassword
{
    public const PLAIN = 'jimmyBulL1230$';

    public static function hash(): string
    {
        return Hash::make(self::PLAIN);
    }
}
