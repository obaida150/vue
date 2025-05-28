<?php

namespace App\Http\Controllers;

use App\Models\ParkingLocation;
use App\Models\ParkingSpot;
use App\Models\ParkingReservation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ParkingController extends Controller
{
    /**
     * Display the main parking page for users
     */
    public function index()
    {
        return Inertia::render('Parking/Index');
    }

    /**
     * Display the admin parking management page
     */
    public function admin()
    {
        return Inertia::render('Admin/Parking/Index');
    }

    /**
     * Get all parking locations
     */
    public function getLocations(): JsonResponse
    {
        try {
            $locations = ParkingLocation::withCount('parkingSpots')->get();
            return response()->json($locations);
        } catch (\Exception $e) {
            Log::error('Error fetching parking locations: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch locations', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a new parking location
     */
    public function storeLocation(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'address' => 'nullable|string|max:500',
                'description' => 'nullable|string|max:1000',
            ]);

            $location = ParkingLocation::create($validated);

            return response()->json($location, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error creating parking location: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to create location', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a parking location
     */
    public function updateLocation(Request $request, ParkingLocation $location): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'address' => 'nullable|string|max:500',
                'description' => 'nullable|string|max:1000',
            ]);

            $location->update($validated);

            return response()->json($location);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error updating parking location: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to update location', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a parking location
     */
    public function destroyLocation(ParkingLocation $location): JsonResponse
    {
        try {
            // Check if location has parking spots
            if ($location->parkingSpots()->count() > 0) {
                return response()->json(['error' => 'Cannot delete location with existing parking spots'], 400);
            }

            $location->delete();

            return response()->json(['message' => 'Location deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting parking location: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to delete location', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all parking spots with their locations
     */
    public function getSpaces(): JsonResponse
    {
        try {
            $spots = ParkingSpot::with([
                'parkingLocation',
                'activeReservations.user' => function($query) {
                    $query->select('id', 'name', 'initials');
                }
            ])->get();

            // Add current reservation info to each spot
            $spots = $spots->map(function($spot) {
                $currentReservation = $spot->activeReservations
                    ->where('reservation_date', '>=', now()->toDateString())
                    ->first();

                $spot->current_reservation = $currentReservation;
                $spot->status = $currentReservation ? 'reserved' : 'available';

                return $spot;
            });

            return response()->json($spots);
        } catch (\Exception $e) {
            Log::error('Error fetching parking spots: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch parking spots', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all parking spots (alias for getSpaces for compatibility)
     */
    public function getAllSpots(): JsonResponse
    {
        return $this->getSpaces();
    }

    /**
     * Get parking spots for a specific location
     */
    public function getSpacesByLocation(ParkingLocation $location): JsonResponse
    {
        try {
            $spots = $location->parkingSpots()->get();
            return response()->json($spots);
        } catch (\Exception $e) {
            Log::error('Error fetching parking spots for location: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch parking spots', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a new parking spot
     */
    public function storeSpace(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'parking_location_id' => 'required|exists:parking_locations,id',
                'name' => 'required|string|max:255',
                'type' => 'required|in:lift_top,lift_bottom,external,regular',
                'identifier' => 'required|string|max:10|unique:parking_spots,identifier',
                'is_active' => 'boolean',
                'requires_approval' => 'boolean',
                'sort_order' => 'integer|min:0',
                'position_x' => 'nullable|numeric|min:0|max:100',
                'position_y' => 'nullable|numeric|min:0|max:100',
            ]);

            $spot = ParkingSpot::create($validated);
            $spot->load('parkingLocation');

            return response()->json($spot, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error creating parking spot: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to create parking spot', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a parking spot
     */
    public function updateSpace(Request $request, ParkingSpot $space): JsonResponse
    {
        try {
            $validated = $request->validate([
                'parking_location_id' => 'required|exists:parking_locations,id',
                'name' => 'required|string|max:255',
                'type' => 'required|in:lift_top,lift_bottom,external,regular',
                'identifier' => 'required|string|max:10|unique:parking_spots,identifier,' . $space->id,
                'is_active' => 'boolean',
                'requires_approval' => 'boolean',
                'sort_order' => 'integer|min:0',
                'position_x' => 'nullable|numeric|min:0|max:100',
                'position_y' => 'nullable|numeric|min:0|max:100',
            ]);

            $space->update($validated);
            $space->load('parkingLocation');

            return response()->json($space);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error updating parking spot: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to update parking spot', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a parking spot
     */
    public function destroySpace(ParkingSpot $space): JsonResponse
    {
        try {
            $space->delete();

            return response()->json(['message' => 'Parking spot deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting parking spot: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to delete parking spot', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get my reservations
     */
    public function getMyReservations(): JsonResponse
    {
        try {
            $reservations = ParkingReservation::with(['parkingSpot.parkingLocation'])
                ->where('user_id', auth()->id())
                ->where('status', 'confirmed')
                ->orderBy('reservation_date', 'desc')
                ->get();

            return response()->json($reservations);
        } catch (\Exception $e) {
            Log::error('Error fetching my reservations: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch reservations', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a new reservation
     */
    public function createReservation(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'parking_spot_id' => 'required|exists:parking_spots,id',
                'reservation_date' => 'required|date|after_or_equal:today',
                'start_time' => 'nullable|date_format:H:i',
                'end_time' => 'nullable|date_format:H:i',
                'vehicle_info' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:500',
            ]);

            $validated['user_id'] = auth()->id();
            $validated['start_time'] = $validated['start_time'] ?? '06:00';
            $validated['end_time'] = $validated['end_time'] ?? '18:00';
            $validated['status'] = 'confirmed'; // Immer sofort bestätigt

            // Check if spot is already reserved for this date
            $existingReservation = ParkingReservation::where('parking_spot_id', $validated['parking_spot_id'])
                ->where('reservation_date', $validated['reservation_date'])
                ->where('status', 'confirmed')
                ->exists();

            if ($existingReservation) {
                return response()->json(['error' => 'Parking spot is already reserved for this date'], 400);
            }

            $reservation = ParkingReservation::create($validated);
            $reservation->load(['parkingSpot.parkingLocation', 'user']);

            // E-Mail-Benachrichtigung senden
            try {
                \Mail::to($reservation->user->email)->send(
                    new \App\Mail\ParkingReservationMail($reservation, $reservation->user, 'created')
                );
            } catch (\Exception $e) {
                \Log::error('Failed to send parking reservation email: ' . $e->getMessage());
                // Fehler beim E-Mail-Versand soll die Reservierung nicht blockieren
            }

            return response()->json($reservation, 201);
        } catch (ValidationException $e) {
            Log::error('Validation error in createReservation: ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error creating reservation: ' . $e->getMessage());
            Log::error('Request data: ' . json_encode($request->all()));
            return response()->json(['error' => 'Unable to create reservation', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Cancel a reservation (only future reservations)
     */
    public function cancelReservation(ParkingReservation $reservation): JsonResponse
    {
        try {
            // Check if user owns this reservation
            if ($reservation->user_id !== auth()->id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            if ($reservation->status === 'cancelled') {
                return response()->json(['error' => 'Reservation is already cancelled'], 400);
            }

            // Check if reservation is in the past
            $reservationDate = Carbon::parse($reservation->reservation_date);
            if ($reservationDate->isPast()) {
                return response()->json(['error' => 'Cannot cancel past reservations'], 400);
            }

            $reservation->update(['status' => 'cancelled']);

            // E-Mail-Benachrichtigung senden
            try {
                \Mail::to($reservation->user->email)->send(
                    new \App\Mail\ParkingReservationMail($reservation, $reservation->user, 'cancelled')
                );
            } catch (\Exception $e) {
                \Log::error('Failed to send parking cancellation email: ' . $e->getMessage());
            }

            return response()->json(['message' => 'Reservation cancelled successfully']);
        } catch (\Exception $e) {
            Log::error('Error cancelling reservation: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to cancel reservation', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get current reservations (for admin)
     */
    public function getCurrentReservations(): JsonResponse
    {
        try {
            $reservations = ParkingReservation::with(['user:id,name,initials', 'parkingSpot.parkingLocation'])
                ->where('status', 'confirmed')
                ->orderBy('reservation_date', 'desc')
                ->get();

            return response()->json($reservations);
        } catch (\Exception $e) {
            Log::error('Error fetching current reservations: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch current reservations', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Toggle spot status
     */
    public function toggleSpotStatus(ParkingSpot $spot): JsonResponse
    {
        try {
            $newStatus = !$spot->is_active;
            $spot->update(['is_active' => $newStatus]);

            return response()->json($spot);
        } catch (\Exception $e) {
            Log::error('Error toggling spot status: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to toggle spot status', 'message' => $e->getMessage()], 500);
        }
    }

    // Legacy methods for compatibility
    public function getAvailability(): JsonResponse
    {
        return $this->getSpaces();
    }
}
