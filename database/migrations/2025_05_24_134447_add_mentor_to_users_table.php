<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('mentor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_apprentice')->default(false);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['mentor_id']);
            $table->dropColumn(['mentor_id', 'is_apprentice']);
        });
    }
};
