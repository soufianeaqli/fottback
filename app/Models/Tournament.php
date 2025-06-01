<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'date',
        'max_teams',
        'registered_teams',
        'prize_pool',
        'description',
        'format',
        'entry_fee',
        'teams',
    ];

    protected $casts = [
        'date' => 'date',
        'max_teams' => 'integer',
        'registered_teams' => 'integer',
        'teams' => 'array',
    ];
}
