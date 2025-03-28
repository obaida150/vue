<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'Admin'],
            ['name' => 'Manager'],
            ['name' => 'HR'],
            ['name' => 'Mitarbeiter'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}

