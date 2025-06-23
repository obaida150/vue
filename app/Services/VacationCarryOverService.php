<?php

namespace App\Services;

use App\Models\User;
use App\Models\VacationBalance;
use App\Models\VacationRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class VacationCarryOverService
{
    /**
     * Maximale Übertragung von Urlaubstagen (kann pro Unternehmen angepasst werden)
     */
    const MAX_CARRY_OVER_DAYS = 10.0;

    /**
     * Verfallsdatum für übertragene Tage (31. März des Folgejahres)
     */
    const CARRY_OVER_EXPIRY_MONTH = 3;
    const CARRY_OVER_EXPIRY_DAY = 31;

    /**
     * Führt die jährliche Übertragung für alle aktiven Benutzer durch
     */
    public function processYearlyCarryOver(int $fromYear, int $toYear): array
    {
        $results = [
            'processed' => 0,
            'errors' => 0,
            'total_carried_over' => 0.0,
            'details' => []
        ];

        $activeUsers = User::where('is_active', true)->get();

        DB::beginTransaction();
        try {
            foreach ($activeUsers as $user) {
                $result = $this->processUserCarryOver($user, $fromYear, $toYear);
                
                if ($result['success']) {
                    $results['processed']++;
                    $results['total_carried_over'] += $result['carried_over_days'];
                } else {
                    $results['errors']++;
                }
                
                $results['details'][] = $result;
            }

            DB::commit();
            Log::info("Jährliche Urlaubsübertragung abgeschlossen", $results);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Fehler bei jährlicher Urlaubsübertragung: " . $e->getMessage());
            throw $e;
        }

        return $results;
    }

    /**
     * Verarbeitet die Übertragung für einen einzelnen Benutzer
     */
    public function processUserCarryOver(User $user, int $fromYear, int $toYear): array
    {
        try {
            // Hole den Urlaubssaldo des Vorjahres
            $previousBalance = VacationBalance::where('user_id', $user->id)
                ->where('year', $fromYear)
                ->first();

            if (!$previousBalance) {
                return [
                    'success' => false,
                    'user_id' => $user->id,
                    'user_name' => $user->full_name,
                    'error' => 'Kein Urlaubssaldo für das Vorjahr gefunden',
                    'carried_over_days' => 0
                ];
            }

            // Berechne die verbleibenden Tage
            $remainingDays = $previousBalance->total_days - $previousBalance->used_days;
            
            // Bestimme die zu übertragenden Tage (maximal MAX_CARRY_OVER_DAYS)
            $carryOverDays = min($remainingDays, self::MAX_CARRY_OVER_DAYS);
            $carryOverDays = max(0, $carryOverDays); // Keine negativen Werte

            // Verfallsdatum berechnen (31. März des neuen Jahres)
            $expiryDate = Carbon::create($toYear, self::CARRY_OVER_EXPIRY_MONTH, self::CARRY_OVER_EXPIRY_DAY);

            // Erstelle oder aktualisiere den Urlaubssaldo für das neue Jahr
            $newBalance = VacationBalance::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'year' => $toYear
                ],
                [
                    'total_days' => $user->vacation_days_per_year,
                    'used_days' => 0.0,
                    'carry_over_days' => $carryOverDays,
                    'carry_over_used' => 0.0,
                    'carry_over_expires_at' => $expiryDate,
                    'max_carry_over' => self::MAX_CARRY_OVER_DAYS
                ]
            );

            Log::info("Urlaubsübertragung für Benutzer {$user->id}: {$carryOverDays} Tage übertragen");

            return [
                'success' => true,
                'user_id' => $user->id,
                'user_name' => $user->full_name,
                'remaining_days_previous_year' => $remainingDays,
                'carried_over_days' => $carryOverDays,
                'expiry_date' => $expiryDate->format('Y-m-d'),
                'new_total_available' => $newBalance->total_days + $carryOverDays
            ];

        } catch (\Exception $e) {
            Log::error("Fehler bei Urlaubsübertragung für Benutzer {$user->id}: " . $e->getMessage());
            
            return [
                'success' => false,
                'user_id' => $user->id,
                'user_name' => $user->full_name,
                'error' => $e->getMessage(),
                'carried_over_days' => 0
            ];
        }
    }

    /**
     * Lässt übertragene Urlaubstage verfallen, die das Verfallsdatum überschritten haben
     */
    public function expireCarryOverDays(): array
    {
        $today = Carbon::now();
        $results = [
            'expired_balances' => 0,
            'total_expired_days' => 0.0,
            'details' => []
        ];

        $expiredBalances = VacationBalance::where('carry_over_expires_at', '<', $today)
            ->where('carry_over_days', '>', 0)
            ->get();

        foreach ($expiredBalances as $balance) {
            $expiredDays = $balance->carry_over_days - $balance->carry_over_used;
            
            if ($expiredDays > 0) {
                $balance->carry_over_days = $balance->carry_over_used;
                $balance->save();

                $results['expired_balances']++;
                $results['total_expired_days'] += $expiredDays;
                $results['details'][] = [
                    'user_id' => $balance->user_id,
                    'user_name' => $balance->user->full_name ?? 'Unbekannt',
                    'expired_days' => $expiredDays,
                    'expiry_date' => $balance->carry_over_expires_at
                ];

                Log::info("Übertragene Urlaubstage verfallen für Benutzer {$balance->user_id}: {$expiredDays} Tage");
            }
        }

        return $results;
    }

    /**
     * Einmalige Migration von alten Urlaubsdaten
     */
    public function migrateOldVacationData(array $oldData): array
    {
        $results = [
            'migrated' => 0,
            'errors' => 0,
            'details' => []
        ];

        DB::beginTransaction();
        try {
            foreach ($oldData as $userData) {
                $result = $this->migrateUserData($userData);
                
                if ($result['success']) {
                    $results['migrated']++;
                } else {
                    $results['errors']++;
                }
                
                $results['details'][] = $result;
            }

            DB::commit();
            Log::info("Migration alter Urlaubsdaten abgeschlossen", $results);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Fehler bei Migration alter Urlaubsdaten: " . $e->getMessage());
            throw $e;
        }

        return $results;
    }

    /**
     * Migriert die Daten eines einzelnen Benutzers
     */
    private function migrateUserData(array $userData): array
    {
        try {
            $user = User::find($userData['user_id']);
            if (!$user) {
                return [
                    'success' => false,
                    'user_id' => $userData['user_id'],
                    'error' => 'Benutzer nicht gefunden'
                ];
            }

            // Erstelle Urlaubssaldo für das aktuelle Jahr mit übertragenen Tagen
            $currentYear = Carbon::now()->year;
            $carryOverDays = min($userData['remaining_days_from_previous_year'] ?? 0, self::MAX_CARRY_OVER_DAYS);
            $expiryDate = Carbon::create($currentYear, self::CARRY_OVER_EXPIRY_MONTH, self::CARRY_OVER_EXPIRY_DAY);

            VacationBalance::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'year' => $currentYear
                ],
                [
                    'total_days' => $userData['total_days'] ?? $user->vacation_days_per_year,
                    'used_days' => $userData['used_days'] ?? 0.0,
                    'carry_over_days' => $carryOverDays,
                    'carry_over_used' => $userData['carry_over_used'] ?? 0.0,
                    'carry_over_expires_at' => $expiryDate,
                    'max_carry_over' => self::MAX_CARRY_OVER_DAYS
                ]
            );

            return [
                'success' => true,
                'user_id' => $user->id,
                'user_name' => $user->full_name,
                'migrated_carry_over' => $carryOverDays
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'user_id' => $userData['user_id'] ?? 'unknown',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Berechnet die verfügbaren Urlaubstage für einen Benutzer
     */
    public function getAvailableVacationDays(User $user, int $year = null): array
    {
        $year = $year ?? Carbon::now()->year;
        $balance = VacationBalance::where('user_id', $user->id)
            ->where('year', $year)
            ->first();

        if (!$balance) {
            return [
                'total_days' => 0,
                'used_days' => 0,
                'carry_over_days' => 0,
                'carry_over_used' => 0,
                'available_regular' => 0,
                'available_carry_over' => 0,
                'total_available' => 0,
                'carry_over_expires_at' => null
            ];
        }

        $availableRegular = $balance->total_days - $balance->used_days;
        $availableCarryOver = $balance->carry_over_days - $balance->carry_over_used;
        
        // Prüfe ob übertragene Tage noch gültig sind
        if ($balance->carry_over_expires_at && Carbon::now()->gt($balance->carry_over_expires_at)) {
            $availableCarryOver = 0;
        }

        return [
            'total_days' => (float) $balance->total_days,
            'used_days' => (float) $balance->used_days,
            'carry_over_days' => (float) $balance->carry_over_days,
            'carry_over_used' => (float) $balance->carry_over_used,
            'available_regular' => max(0, $availableRegular),
            'available_carry_over' => max(0, $availableCarryOver),
            'total_available' => max(0, $availableRegular) + max(0, $availableCarryOver),
            'carry_over_expires_at' => $balance->carry_over_expires_at
        ];
    }
}
