<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        Permission::firstOrCreate(['name' => 'create events', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit events', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete events', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create events for others', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit events for others', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete events for others', 'guard_name' => 'web']);
        // Fügen Sie hier weitere Berechtigungen hinzu, die Ihre Anwendung benötigt

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $managerRole = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $hrRole = Role::firstOrCreate(['name' => 'HR', 'guard_name' => 'web']); // Behalten Sie diese, falls sie woanders verwendet wird
        $mitarbeiterRole = Role::firstOrCreate(['name' => 'Mitarbeiter', 'guard_name' => 'web']);
        $abteilungsleiterRole = Role::firstOrCreate(['name' => 'Abteilungsleiter', 'guard_name' => 'web']);
        $personalRole = Role::firstOrCreate(['name' => 'Personal', 'guard_name' => 'web']); // NEU: Personal Rolle

        // Assign permissions to roles
        // Admin gets all permissions
        $adminRole->givePermissionTo(Permission::all());

        // Manager can create/edit/delete their own events and events for others
        $managerRole->givePermissionTo([
            'create events',
            'edit events',
            'delete events',
            'create events for others',
            'edit events for others',
            'delete events for others',
        ]);

        // HR can create/edit/delete their own events and events for others
        $hrRole->givePermissionTo([
            'create events',
            'edit events',
            'delete events',
            'create events for others',
            'edit events for others',
            'delete events for others',
        ]);

        // Mitarbeiter can create/edit/delete their own events
        $mitarbeiterRole->givePermissionTo([
            'create events',
            'edit events',
            'delete events',
        ]);

        // Berechtigungen für Abteilungsleiter
        $abteilungsleiterRole->givePermissionTo([
            'create events',
            'edit events',
            'delete events',
            'create events for others',
            'edit events for others',
            'delete events for others',
        ]);

        // NEU: Berechtigungen für Personal (HR)
        $personalRole->givePermissionTo([
            'create events',
            'edit events',
            'delete events',
            'create events for others',
            'edit events for others',
            'delete events for others',
            // Fügen Sie hier spezifische HR-Berechtigungen hinzu, falls vorhanden
        ]);
    }
}
