<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'year',
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'year' => 'integer',
    ];

    /**
     * Get the user that owns the subject.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team that owns the subject.
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the reports for the subject.
     */
    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Scope a query to only include subjects for a specific year.
     */
    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope a query to only include subjects for the authenticated user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
