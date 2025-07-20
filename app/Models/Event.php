<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'team_id',
        'event_type_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'is_all_day',
        'status',
        'approved_by',
        'approved_date',
        'rejected_by',
        'rejected_date',
        'rejection_reason',
        'created_by',
        'outlook_change_key'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime', // Changed from 'date' to 'datetime'
        'end_date' => 'datetime',   // Changed from 'date' to 'datetime'
        'is_all_day' => 'boolean',
        'approved_date' => 'datetime',
        'rejected_date' => 'datetime',
    ];

    /**
     * Get the user that owns the event.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team that the event belongs to.
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the event type that owns the event.
     */
    public function eventType()
    {
        return $this->belongsTo(EventType::class);
    }

    /**
     * Get the user who approved the event.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who rejected the event.
     */
    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get the user who created the event.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

