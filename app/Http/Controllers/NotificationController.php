<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Get birthday notifications for the current user's team
     */
    public function getBirthdayNotifications()
    {
        try {
            Log::info('NotificationController::getBirthdayNotifications wurde aufgerufen');

            $user = Auth::user();
            $teamId = $user->currentTeam ? $user->currentTeam->id : null;

            Log::info('Aktueller Benutzer: ' . $user->id . ', Team: ' . ($teamId ?? 'keins'));

            if (!$teamId) {
                Log::info('Kein Team gefunden, gebe leere Liste zurück');
                return response()->json(['notifications' => []]);
            }

            // Finde Teammitglieder, die heute Geburtstag haben
            $today = Carbon::today();
            Log::info('Heutiges Datum: ' . $today->format('Y-m-d'));

            $birthdayUsers = User::whereHas('teams', function ($query) use ($teamId) {
                $query->where('teams.id', $teamId);
            })
                ->where('id', '!=', $user->id) // Nicht den aktuellen Benutzer
                ->where('is_active', true)
                ->whereNotNull('birth_date')
                ->get();

            Log::info('Gefundene Benutzer mit Geburtsdatum: ' . $birthdayUsers->count());

            // Manuell filtern, um Benutzer zu finden, die heute Geburtstag haben
            $todayBirthdayUsers = $birthdayUsers->filter(function ($teamUser) use ($today) {
                if (!$teamUser->birth_date) return false;

                $birthDate = Carbon::parse($teamUser->birth_date);
                $hasBirthdayToday = $birthDate->month === $today->month && $birthDate->day === $today->day;

                Log::info('Benutzer ' . $teamUser->id . ' (' . $teamUser->full_name . ') hat Geburtstag am ' .
                    $birthDate->format('d.m.') . ' - Heute Geburtstag: ' . ($hasBirthdayToday ? 'Ja' : 'Nein'));

                return $hasBirthdayToday;
            });

            Log::info('Benutzer mit Geburtstag heute: ' . $todayBirthdayUsers->count());

            $result = $todayBirthdayUsers->map(function ($birthdayUser) {
                $age = Carbon::parse($birthdayUser->birth_date)->age;

                Log::info('Benutzer ' . $birthdayUser->id . ' (' . $birthdayUser->full_name . ') wird heute ' . $age . ' Jahre alt');

                return [
                    'id' => $birthdayUser->id,
                    'name' => $birthdayUser->full_name,
                    'age' => $age
                ];
            });

            Log::info('Rückgabe: ' . $result->count() . ' Geburtstags-Benachrichtigungen');

            return response()->json([
                'notifications' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Fehler im NotificationController: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage(), 'notifications' => []], 500);
        }
    }
}

