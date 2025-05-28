<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkingSpot extends Model
{
    use HasFactory;

    protected $fillable = [
        'parking_location_id',
        'name',
        'identifier',
        'type',
        'is_active',
        'requires_approval',
        'sort_order',
        'position_x',
        'position_y',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_approval' => 'boolean',
        'sort_order' => 'integer',
        'position_x' => 'decimal:2',
        'position_y' => 'decimal:2',
    ];

    // Füge diese Relation hinzu
    protected $appends = ['current_reservation'];

    public function parkingLocation()
    {
        return $this->belongsTo(ParkingLocation::class);
    }

    public function reservations()
    {
        return $this->hasMany(ParkingReservation::class);
    }

    public function activeReservations()
    {
        return $this->hasMany(ParkingReservation::class)->whereIn('status', ['pending', 'confirmed']);
    }

    public function getCurrentReservationAttribute()
    {
        return $this->activeReservations()
            ->with('user:id,name')
            ->where('reservation_date', '>=', now()->toDateString())
            ->first();
    }
}
