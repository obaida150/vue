<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('color', 20);
            $table->text('description')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->timestamps();
        });

        // Standard-Ereignistypen
        DB::table('event_types')->insert([
            [
                'name' => 'Homeoffice',
                'color' => '#4CAF50',
                'description' => 'Arbeit von zu Hause aus',
                'requires_approval' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Büro',
                'color' => '#2196F3',
                'description' => 'Arbeit im Büro',
                'requires_approval' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Außendienst',
                'color' => '#FF9800',
                'description' => 'Arbeit beim Kunden oder extern',
                'requires_approval' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Krank',
                'color' => '#F44336',
                'description' => 'Krankheitsbedingte Abwesenheit',
                'requires_approval' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Sonstiges',
                'color' => '#607D8B',
                'description' => 'Sonstige Ereignisse',
                'requires_approval' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_types');
    }
};
