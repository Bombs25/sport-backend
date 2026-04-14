<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected function casts(): array
    {
        return [
            'hq_latitude' => 'float',
            'hq_longitude' => 'float',
        ];
    }
}
