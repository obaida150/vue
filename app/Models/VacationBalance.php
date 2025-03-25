<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VacationBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'total_days',
        'used_days',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getRemainingDaysAttribute()
    {
        return $this->total_days - $this->used_days;
    }
}
