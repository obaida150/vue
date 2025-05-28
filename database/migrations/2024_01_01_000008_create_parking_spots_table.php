<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('parking_spots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_location_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['lift_top', 'lift_bottom', 'external', 'regular'])->default('regular');
            $table->string('identifier')->unique();
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_approval')->default(false);
            $table->json('restrictions')->nullable();
            $table->integer('sort_order')->default(0);
            $table->json('visual_position')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('parking_spots');
    }
};
