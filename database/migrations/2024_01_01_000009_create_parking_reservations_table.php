<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('parking_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parking_spot_id')->constrained()->onDelete('cascade');
            $table->date('reservation_date');
            $table->time('start_time')->default('06:00:00');
            $table->time('end_time')->default('18:00:00');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('confirmed');
            $table->text('notes')->nullable();
            $table->string('vehicle_info')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();

            // Ein Parkplatz kann nur einmal pro Tag reserviert werden
            $table->unique(['parking_spot_id', 'reservation_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('parking_reservations');
    }
};
