<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EventType;

class EventTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Die Event-Typen werden bereits in der Migration erstellt,
        // aber wir stellen sicher, dass sie existieren
        $eventTypes = [
            [
                'name' => 'Homeoffice',
                'color' => '#4CAF50',
                'description' => 'Arbeit von zu Hause aus',
                'requires_approval' => true,
            ],
            [
                'name' => 'Büro',
                'color' => '#2196F3',
                'description' => 'Arbeit im Büro',
                'requires_approval' => false,
            ],
            [
                'name' => 'Außendienst',
                'color' => '#FF9800',
                'description' => 'Arbeit beim Kunden oder extern',
                'requires_approval' => true,
            ],
            [
                'name' => 'Krank',
                'color' => '#F44336',
                'description' => 'Krankheitsbedingte Abwesenheit',
                'requires_approval' => false,
            ],
            [
                'name' => 'Urlaub',
                'color' => '#9C27B0',
                'description' => 'Genehmigter Urlaub',
                'requires_approval' => true,
            ],
            [
                'name' => 'Sonstiges',
                'color' => '#607D8B',
                'description' => 'Sonstige Ereignisse',
                'requires_approval' => true,
            ],
        ];

        foreach ($eventTypes as $type) {
            EventType::updateOrCreate(
                ['name' => $type['name']],
                [
                    'color' => $type['color'],
                    'description' => $type['description'],
                    'requires_approval' => $type['requires_approval'],
                ]
            );
        }
    }
}

