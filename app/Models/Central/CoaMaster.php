<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

class CoaMaster extends Model
{
    protected $connection = 'central';

    protected $table = 'coa_master';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
            'is_template' => 'boolean',
        ];
    }
}
