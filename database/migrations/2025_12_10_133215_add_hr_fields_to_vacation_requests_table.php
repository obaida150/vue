<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vacation_requests', function (Blueprint $table) {
            // Markierung, ob der Eintrag von HR erstellt wurde
            $table->boolean('created_by_hr')->default(false)->after('status');
            
            // ID des HR-Mitarbeiters, der den Eintrag erstellt hat
            $table->foreignId('created_by_hr_user_id')->nullable()->constrained('users')->onDelete('set null')->after('created_by_hr');
            
            // Grund für den nachträglichen Eintrag
            $table->text('hr_entry_reason')->nullable()->after('created_by_hr_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacation_requests', function (Blueprint $table) {
            $table->dropForeign(['created_by_hr_user_id']);
            $table->dropColumn(['created_by_hr', 'created_by_hr_user_id', 'hr_entry_reason']);
        });
    }
};
