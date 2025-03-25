<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'description',
        'requires_approval',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
    ];

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
