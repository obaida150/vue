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
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 100)->after('id');
            $table->string('last_name', 100)->after('first_name');
            $table->date('birth_date')->nullable()->after('email_verified_at');
            $table->unsignedBigInteger('role_id')->after('birth_date')->default(4); // Default: Mitarbeiter
            $table->integer('vacation_days_per_year')->default(30)->after('role_id');
            $table->string('initials', 10)->nullable()->after('vacation_days_per_year');
            $table->date('entry_date')->nullable()->after('initials');
            $table->string('employee_number', 50)->unique()->nullable()->after('entry_date');
            $table->boolean('is_active')->default(true)->after('employee_number');

            $table->foreign('role_id')->references('id')->on('roles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn([
                'first_name',
                'last_name',
                'birth_date',
                'role_id',
                'vacation_days_per_year',
                'initials',
                'entry_date',
                'employee_number',
                'is_active'
            ]);
        });
    }
};
