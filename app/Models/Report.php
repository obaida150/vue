<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'year',
        'berichtsnummer',
        'date_from',
        'date_to',
        'instructor_id',
        'subjects_data',
        'activities',
        'unterweisungen',
        'unterricht',
        'abteilung',
        'erstellungsdatum',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'subjects_data' => 'array', // JSON wird automatisch zu Array konvertiert
        'erstellungsdatum' => 'date',
    ];

    protected $dates = [
        'date_from',
        'date_to',
        'erstellungsdatum',
        'created_at',
        'updated_at',
    ];

    // Beziehungen
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    // Accessor für subjects (für Kompatibilität mit dem PDF-Template)
    public function getSubjectsAttribute()
    {
        return $this->subjects_data;
    }
}
