<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Informationen über den aktuellen Benutzer abrufen
     */
    public function getCurrentUser()
    {
        $user = Auth::user();
        $user->load('role');

        return response()->json([
            'user' => $user,
            'permissions' => [
                'isAdmin' => $user->isAdmin(),
                'isPersonal' => $user->isPersonal(),
                'isManager' => $user->isManager(),
                'isEmployee' => $user->isEmployee(),
            ]
        ]);
    }

    /**
     * Geburtstage im aktuellen Monat abrufen
     */
    public function getBirthdays()
    {
        $user = Auth::user();
        $currentMonth = date('m');

        // Benutzer mit Geburtstagen im aktuellen Monat abrufen
        $birthdays = User::whereRaw('MONTH(birth_date) = ?', [$currentMonth])
            ->where('is_active', true)
            ->select('id', 'name', 'first_name', 'last_name', 'birth_date')
            ->orderByRaw('DAYOFMONTH(birth_date)')
            ->get();

        return response()->json($birthdays);
    }
}

