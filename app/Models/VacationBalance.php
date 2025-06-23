<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

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
        'carry_over_days',        // NEU
        'carry_over_used',        // NEU
        'carry_over_expires_at',  // NEU
        'max_carry_over',         // NEU
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_days' => 'decimal:1',
        'used_days' => 'decimal:1',
        'carry_over_days' => 'decimal:1',      // NEU
        'carry_over_used' => 'decimal:1',      // NEU
        'max_carry_over' => 'decimal:1',       // NEU
        'carry_over_expires_at' => 'date',     // NEU
        'year' => 'integer',
    ];

    /**
     * Get the user that owns the vacation balance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the remaining vacation days (regular + carry over).
     * NEU: Berücksichtigt jetzt auch übertragene Tage
     */
    public function getRemainingDaysAttribute(): float
    {
        $regularRemaining = $this->total_days - $this->used_days;
        $carryOverRemaining = $this->getAvailableCarryOverDays();

        return round($regularRemaining + $carryOverRemaining, 1);
    }

    /**
     * NEU: Gibt die verfügbaren übertragenen Tage zurück (prüft Verfallsdatum)
     */
    public function getAvailableCarryOverDays(): float
    {
        if ($this->carry_over_expires_at && Carbon::now()->gt($this->carry_over_expires_at)) {
            return 0.0; // Übertragene Tage sind verfallen
        }

        return round($this->carry_over_days - $this->carry_over_used, 1);
    }

    /**
     * NEU: Gesamte verfügbare Urlaubstage (regular + carry over)
     */
    public function getTotalAvailableDaysAttribute(): float
    {
        return round($this->total_days + $this->getAvailableCarryOverDays(), 1);
    }

    /**
     * NEU: Gesamte verbrauchte Tage (regular + carry over)
     */
    public function getTotalUsedDaysAttribute(): float
    {
        return round($this->used_days + $this->carry_over_used, 1);
    }

    /**
     * NEU: Berechnet den Prozentsatz der verbrauchten Urlaubstage
     */
    public function getUsagePercentageAttribute(): float
    {
        $totalAvailable = $this->total_available_days;

        if ($totalAvailable <= 0) {
            return 0;
        }

        return round(($this->total_used_days / $totalAvailable) * 100, 1);
    }

    /**
     * NEU: Prüft, ob alle Urlaubstage verbraucht sind
     */
    public function getIsFullyUsedAttribute(): bool
    {
        return $this->total_used_days >= $this->total_available_days;
    }

    /**
     * NEU: Fügt Urlaubstage hinzu (bevorzugt übertragene Tage)
     *
     * @param float $days Anzahl der Tage
     * @return bool
     */
    public function addUsedDays(float $days): bool
    {
        if (!$this->hasEnoughDays($days)) {
            return false;
        }

        $remainingDays = $days;

        // Zuerst übertragene Tage verwenden (falls verfügbar und nicht verfallen)
        $availableCarryOver = $this->getAvailableCarryOverDays();
        if ($availableCarryOver > 0 && $remainingDays > 0) {
            $useFromCarryOver = min($remainingDays, $availableCarryOver);
            $this->carry_over_used += $useFromCarryOver;
            $remainingDays -= $useFromCarryOver;
        }

        // Dann reguläre Tage verwenden
        if ($remainingDays > 0) {
            $this->used_days += $remainingDays;
        }

        return $this->save();
    }

    /**
     * NEU: Entfernt Urlaubstage (z.B. bei Stornierung)
     *
     * @param float $days Anzahl der Tage
     * @return bool
     */
    public function removeUsedDays(float $days): bool
    {
        $remainingDays = $days;

        // Zuerst von regulären Tagen abziehen
        if ($this->used_days > 0 && $remainingDays > 0) {
            $removeFromRegular = min($remainingDays, $this->used_days);
            $this->used_days -= $removeFromRegular;
            $remainingDays -= $removeFromRegular;
        }

        // Dann von übertragenen Tagen abziehen
        if ($this->carry_over_used > 0 && $remainingDays > 0) {
            $removeFromCarryOver = min($remainingDays, $this->carry_over_used);
            $this->carry_over_used -= $removeFromCarryOver;
        }

        return $this->save();
    }

    /**
     * NEU: Prüft, ob genügend Urlaubstage verfügbar sind
     *
     * @param float $days Anzahl der benötigten Tage
     * @return bool
     */
    public function hasEnoughDays(float $days): bool
    {
        return $this->remaining_days >= $days;
    }

    /**
     * NEU: Scope für ein bestimmtes Jahr
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * NEU: Scope für einen bestimmten Benutzer
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * NEU: Formatiert die Tage für die Anzeige
     *
     * @param float $days
     * @return string
     */
    public static function formatDays(float $days): string
    {
        if ($days == (int)$days) {
            return (int)$days . ' Tag' . ($days != 1 ? 'e' : '');
        }

        return number_format($days, 1, ',', '.') . ' Tage';
    }

    /**
     * NEU: Gibt eine formatierte Beschreibung des Urlaubssaldos zurück
     */
    public function getDescriptionAttribute(): string
    {
        $totalUsed = self::formatDays($this->total_used_days);
        $totalAvailable = self::formatDays($this->total_available_days);
        $remaining = self::formatDays($this->remaining_days);

        $description = "Verbraucht: {$totalUsed} von {$totalAvailable} ({$remaining} verbleibend)";

        if ($this->getAvailableCarryOverDays() > 0) {
            $carryOver = self::formatDays($this->getAvailableCarryOverDays());
            $expiryDate = $this->carry_over_expires_at ? $this->carry_over_expires_at->format('d.m.Y') : 'unbekannt';
            $description .= " | Übertrag: {$carryOver} (verfällt am {$expiryDate})";
        }

        return $description;
    }
}
