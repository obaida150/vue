<?php

     namespace App\Http\Controllers;

     use Illuminate\Http\Request;
     use App\Models\EventType;
     use Illuminate\Support\Facades\Log;
     use Illuminate\Support\Facades\Auth; // Auth-Klasse importieren

     class EventTypeController extends Controller
     {
         /**
          * Display a listing of the event types.
          *
          * @return \Illuminate\Http\Response
          */
         public function index()
         {
             try {
                 $user = Auth::user();

                 // Logging für Debugging-Zwecke
                 Log::info('EventTypeController@index wurde aufgerufen', [
                     'user_id' => $user ? $user->id : 'nicht authentifiziert'
                 ]);

                 if (!$user) {
                     return response()->json(['error' => 'Nicht authentifiziert'], 401);
                 }

                 $eventTypes = EventType::all();

                 return response()->json($eventTypes);
             } catch (\Exception $e) {
                 Log::error('Fehler in EventTypeController@index: ' . $e->getMessage(), [
                     'trace' => $e->getTraceAsString()
                 ]);
                 return response()->json(['error' => $e->getMessage()], 500);
             }
         }
     }
