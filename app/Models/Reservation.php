<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'terrain_id',
        'user_id',
        'name',
        'email',
        'date',
        'time_slot',
        'price',
        'is_paid',
        'accepted',
        'rejected'
    ];

    protected $casts = [
        'date' => 'date',
        'is_paid' => 'boolean',
        'accepted' => 'boolean',
        'rejected' => 'boolean',
        'price' => 'float'
    ];

    public function terrain()
    {
        return $this->belongsTo(Terrain::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'username');
    }
}
