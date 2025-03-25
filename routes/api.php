<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventController;
use App\Http\Controllers\VacationController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    // Benutzer-Routen
    Route::get('/user', [UserController::class, 'getCurrentUser']);
    Route::get('/birthdays', [UserController::class, 'getBirthdays']);

    // Team-Routen
    Route::get('/teams', [TeamController::class, 'getUserTeams']);
    Route::get('/teams/{team}/members', [TeamController::class, 'getTeamMembers']);
    Route::get('/teams/{team}/homeoffice-rules', [TeamController::class, 'getTeamHomeofficeRules']);

    // Ereignis-Routen
    Route::get('/event-types', [EventController::class, 'getEventTypes']);
    Route::get('/events', [EventController::class, 'getUserEvents']);
    Route::get('/teams/{team}/events', [EventController::class, 'getTeamEvents']);
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{event}', [EventController::class, 'update']);
    Route::delete('/events/{event}', [EventController::class, 'destroy']);
    Route::post('/events/{event}/approve', [EventController::class, 'approve']);
    Route::post('/events/{event}/reject', [EventController::class, 'reject']);
    Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show');
    // Urlaubs-Routen
    Route::get('/vacation/balance', [VacationController::class, 'getUserBalance']);
    Route::get('/vacation/requests', [VacationController::class, 'getUserRequests']);
    Route::get('/teams/{team}/vacation-requests', [VacationController::class, 'getTeamRequests']);
    Route::post('/vacation/requests', [VacationController::class, 'store']);
    Route::post('/vacation/requests/{vacationRequest}/approve', [VacationController::class, 'approve']);
    Route::post('/vacation/requests/{vacationRequest}/reject', [VacationController::class, 'reject']);
    Route::delete('/vacation/requests/{vacationRequest}', [VacationController::class, 'cancel']);
    Route::get('/vacation/substitutes', [VacationController::class, 'getAvailableSubstitutes']);
});

