<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VacationRequest extends Model
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
        'start_date',
        'end_date',
        'days',
        'substitute_id',
        'notes',
        'status',
        'approved_by',
        'approved_date',
        'rejected_by',
        'rejected_date',
        'rejection_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_date' => 'datetime',
        'rejected_date' => 'datetime',
    ];

    /**
     * Get the user that owns the vacation request.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team (department) that the vacation request belongs to.
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the substitute user for the vacation request.
     */
    public function substitute()
    {
        return $this->belongsTo(User::class, 'substitute_id');
    }

    /**
     * Get the user who approved the vacation request.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who rejected the vacation request.
     */
    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}

