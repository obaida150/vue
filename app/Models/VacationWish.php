<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class VacationWish extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'team_id',
        'start_date',
        'end_date',
        'days',
        'notes'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'team_id' => 'integer',
    ];

    /**
     * Get the user that owns the vacation wish.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team that the vacation wish belongs to.
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
