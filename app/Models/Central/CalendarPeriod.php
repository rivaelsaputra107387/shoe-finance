<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

class CalendarPeriod extends Model
{
    protected $connection = 'central';

    protected $fillable = ['name', 'start_date', 'end_date'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
