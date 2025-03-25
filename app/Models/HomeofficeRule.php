<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeofficeRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'max_days_per_week',
        'description',
        'created_by',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
