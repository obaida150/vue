<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkingLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'description',
        'is_active',
        'coordinates'
    ];

    protected $casts = [
        'coordinates' => 'array',
        'is_active' => 'boolean'
    ];

    public function parkingSpots()
    {
        return $this->hasMany(ParkingSpot::class);
    }

    public function activeParkingSpots()
    {
        return $this->hasMany(ParkingSpot::class)->where('is_active', true);
    }
}
