<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Modular Granular Permissions
        $permissions = [
            // Routers
            'routers.view', 'routers.create', 'routers.edit', 'routers.delete', 'routers.test_connection',
            // Packages
            'packages.view', 'packages.create', 'packages.edit', 'packages.delete',
            // Customers
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete', 'customers.import',
            // Online sessions
            'online_sessions.view', 'online_sessions.disconnect',
            // Network map
            'network_map.view', 'network_map.manage',
            // Invoices
            'invoices.view', 'invoices.create', 'invoices.mark_paid',
            // Tickets
            'tickets.view', 'tickets.create', 'tickets.assign', 'tickets.update_own', 'tickets.close',
            // Roles & Users management
            'roles.manage',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        // 1. Super Admin
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->syncPermissions(Permission::all());

        // 2. Owner
        $owner = Role::firstOrCreate(['name' => 'Owner']);
        $owner->syncPermissions(Permission::all());

        // 3. Admin
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $admin->syncPermissions(
            collect($permissions)->filter(fn($p) => $p !== 'roles.manage')->toArray()
        );

        // 4. Kasir
        $kasir = Role::firstOrCreate(['name' => 'Kasir']);
        $kasir->syncPermissions([
            'routers.view',
            'packages.view',
            'customers.view',
            'invoices.view', 'invoices.mark_paid',
        ]);

        // 5. Teknisi
        $teknisi = Role::firstOrCreate(['name' => 'Teknisi']);
        $teknisi->syncPermissions([
            'routers.view',
            'packages.view',
            'customers.view',
            'network_map.view',
            'tickets.view', 'tickets.update_own',
        ]);

        // 6. Customer Service
        $cs = Role::firstOrCreate(['name' => 'Customer Service']);
        $cs->syncPermissions([
            'customers.view', 'customers.create', 'customers.edit',
            'tickets.view', 'tickets.create', 'tickets.assign', 'tickets.update_own',
            'network_map.view',
        ]);
    }
}
