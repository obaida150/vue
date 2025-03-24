<?php
//
//use Illuminate\Foundation\Application;
//use Illuminate\Support\Facades\Route;
//use Inertia\Inertia;
//
//Route::get('/', function () {
//    return Inertia::render('Welcome', [
//        'canLogin' => Route::has('login'),
//        'canRegister' => Route::has('register'),
//        'laravelVersion' => Application::VERSION,
//        'phpVersion' => PHP_VERSION,
//    ]);
//});
//
//Route::middleware([
//    'auth:sanctum',
//    config('jetstream.auth_session'),
//    'verified',
//])->group(function () {
//    Route::get('/dashboard', function () {
//        return Inertia::render('Dashboard');
//    })->name('dashboard');
//});


use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

// Import all your controllers
use App\Http\Controllers\KalenderController;
use App\Http\Controllers\LeiterController;
use App\Http\Controllers\VeranstaltungController;
use App\Http\Controllers\SonstigeEreignisseController;
use App\Http\Controllers\ADController;
use App\Http\Controllers\AntraegeController;
use App\Http\Controllers\UrlaubController;
use App\Http\Controllers\UrlaubsInfo;
use App\Http\Controllers\Urlaubsmonatlich;
use App\Http\Controllers\UrlaubswunschController;
use App\Http\Controllers\HomeofficeController;
use App\Http\Controllers\AzubiKalenderController;
use App\Http\Controllers\BetriebKalenderController;
use App\Http\Controllers\AbwesenheitController;
use App\Http\Controllers\MeinPortalController;
use App\Http\Controllers\ZeitungController;
use App\Http\Controllers\ParkplatzController;
use App\Http\Controllers\SubjectManager;
use App\Http\Controllers\ReportManager;
use App\Http\Controllers\BerichtTabelleController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AntragController;

// Welcome page route
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Group all authenticated routes
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Dashboard route
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/calendar', function () {
        return Inertia::render('Calendar/Index');
    })->name('calendar');

    Route::get('/company-calendar', function () {
        return Inertia::render('Calendar/Company');
    })->name('company-calendar');

    // Urlaubsverwaltung Routen
    Route::get('/vacation', function () {
        return Inertia::render('Vacation/Overview');
    })->name('vacation');

    Route::get('/vacation/management', function () {
        return Inertia::render('Vacation/Management');
    })->name('vacation.management');

    Route::get('/kalender/{year?}', function ($year = null) {
        return Inertia::render('Kalender', [
            'year' => $year ?? now()->year, // Falls kein Jahr angegeben ist, nehme das aktuelle Jahr
        ]);
    })->name('kalender');

    // Kalender routes
//    Route::get('/kalender/{year?}', [KalenderController::class, 'getKalender'])->name('kalender');
    Route::get('/homeoffice', [HomeofficeController::class, 'getKalender'])->name('homeoffice');
    Route::get('/ad', [ADController::class, 'getList'])->name('AD');
    Route::get('/betriebkalender', [BetriebKalenderController::class, 'getKalender'])->name('betriebkalender');
    Route::get('/azubikalender', [AzubiKalenderController::class, 'getKalender'])->name('azubikalender');

    // Urlaubsverwaltung routes
    Route::get('/leiter', [LeiterController::class, 'getList'])->name('leiter');
    Route::get('/antraegeliste', [AntraegeController::class, 'getAntrag'])->name('antraegeliste');
    Route::get('/urlaub', [UrlaubController::class, 'getUrlaub'])->name('urlaub');
    Route::get('/urlaubswunsch', [UrlaubswunschController::class, 'getKalender'])->name('urlaubswunsch');
    Route::get('/rest-urlaubstage', [UrlaubController::class, 'showRestUrlaubstage']);
    Route::get('/anträge', function () {
        return Inertia::render('Antraege');
    })->name('anträge');
    Route::post('/anträge', [AntragController::class, 'submitAntrag'])->name('antrag.submit');

    // Sonstiges routes
    Route::get('/sonstige_ereignisse', [SonstigeEreignisseController::class, 'getList'])->name('sonstige_ereignisse');
    Route::get('/zeitung', [ZeitungController::class, 'zeitung'])->name('zeitung');
    Route::get('/parkplatz', [ParkplatzController::class, 'index'])->name('parkplatz');
    Route::get('/parkplaetze-liste', function () {
        return Inertia::render('Parkplatz', ['activeTab' => 'parkplaetze-liste-tab']);
    })->name('parkplaetze-liste-tab');

    // Ausbildung routes
    Route::get('/subjects', [SubjectManager::class, '__invoke'])->name('subjects.index');
    Route::get('/reports', [ReportManager::class, '__invoke'])->name('reports.index');
    Route::get('/reports-table', [BerichtTabelleController::class, 'getList'])->name('reports-table');

    // Personal routes
    Route::get('/urlaubsinfo', [UrlaubsInfo::class, 'getUrlaub'])->name('urlaubsinfo');
    Route::get('/urlaubmonatlich', [Urlaubsmonatlich::class, 'getUrlaub'])->name('urlaubmonatlich');
    Route::get('/veranstaltung', [VeranstaltungController::class, 'getList'])->name('veranstaltung');
    Route::get('/abwesenheit', [AbwesenheitController::class, 'getKalender'])->name('abwesenheit');
    Route::get('/meinportal', [MeinPortalController::class, 'getPortal'])->name('meinportal');
    Route::get('/user', function () {
        if (Auth::user()->ist_personal || Auth::user()->ist_admin) {
            return Inertia::render('User');
        }
    })->name('user');

    // File upload routes
    Route::get('file-upload', [FileUploadController::class, 'fileUpload'])->name('file.upload');
    Route::post('file-upload', [FileUploadController::class, 'fileUploadPost'])->name('file.upload.post');

    // Team route
    Route::get('/team', [UserController::class, 'getTeam']);
});
