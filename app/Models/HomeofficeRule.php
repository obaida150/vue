<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeofficeRule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'max_days_per_week',
        'description',
        'created_by',
    ];

    /**
     * Get the team (department) that the homeoffice rule belongs to.
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who created the homeoffice rule.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

