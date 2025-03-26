<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VacationBalance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'year',
        'total_days',
        'used_days',
    ];

    /**
     * Get the user that owns the vacation balance.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the remaining vacation days.
     */
    public function getRemainingDaysAttribute()
    {
        return $this->total_days - $this->used_days;
    }
}

