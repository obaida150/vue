<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ParkingLocation;
use App\Models\ParkingSpot;
use App\Models\ParkingReservation;
use App\Models\User;
use Carbon\Carbon;

class ParkingSeeder extends Seeder
{
    public function run()
    {
        // Create parking locations
        $firmenhof = ParkingLocation::create([
            'name' => 'Firmenhof - Hebebühnen',
            'address' => 'Hauptgebäude',
            'description' => '3 Hebebühnen mit jeweils 2 Parkplätzen (oben/unten)',
            'is_active' => true,
        ]);

        $external = ParkingLocation::create([
            'name' => 'Externe Parkplätze',
            'address' => 'Nebenstraße',
            'description' => 'Zusätzliche Parkplätze in der Nebenstraße',
            'is_active' => true,
        ]);

        // Create parking spots for Firmenhof (3 Hebebühnen = 6 Spots)
        $spots = [
            ['name' => 'Hebebühne 1 - Oben', 'type' => 'lift_top', 'identifier' => 'HB1-O'],
            ['name' => 'Hebebühne 1 - Unten', 'type' => 'lift_bottom', 'identifier' => 'HB1-U'],
            ['name' => 'Hebebühne 2 - Oben', 'type' => 'lift_top', 'identifier' => 'HB2-O'],
            ['name' => 'Hebebühne 2 - Unten', 'type' => 'lift_bottom', 'identifier' => 'HB2-U'],
            ['name' => 'Hebebühne 3 - Oben', 'type' => 'lift_top', 'identifier' => 'HB3-O'],
            ['name' => 'Hebebühne 3 - Unten', 'type' => 'lift_bottom', 'identifier' => 'HB3-U'],
        ];

        foreach ($spots as $index => $spotData) {
            ParkingSpot::create([
                'parking_location_id' => $firmenhof->id,
                'name' => $spotData['name'],
                'type' => $spotData['type'],
                'identifier' => $spotData['identifier'],
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }

        // Create external parking spot
        ParkingSpot::create([
            'parking_location_id' => $external->id,
            'name' => 'Extern 1',
            'type' => 'external',
            'identifier' => 'EXT-1',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Create some test reservations if users exist
        $users = User::take(3)->get();
        if ($users->count() > 0) {
            $spots = ParkingSpot::take(3)->get();

            foreach ($spots as $index => $spot) {
                if (isset($users[$index])) {
                    ParkingReservation::create([
                        'user_id' => $users[$index]->id,
                        'parking_spot_id' => $spot->id,
                        'reservation_date' => Carbon::today()->addDays($index),
                        'status' => $index === 1 ? 'pending' : 'confirmed',
                        'vehicle_info' => 'Test-' . ($index + 1),
                        'notes' => 'Test Reservierung ' . ($index + 1),
                    ]);
                }
            }
        }
    }
}
