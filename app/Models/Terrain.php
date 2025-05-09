<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Terrain extends Model
{
    use HasFactory;
    
    protected $table = 'terrains';
    protected $fillable = ["titre", "description", "prix", "image"];
    
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
