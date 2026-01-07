<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Limpia cache de permisos
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /**
         * ======================
         * Permisos
         * ======================
         */
        $permissions = [
            // users
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            // roles & permissions
            'roles.manage',
            'permissions.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        /**
         * ======================
         * Roles
         * ======================
         */
        $master = Role::firstOrCreate(['name' => 'master']);
        $admin  = Role::firstOrCreate(['name' => 'admin']);

        // Master → todo
        $master->syncPermissions(Permission::all());

        // Admin → casi todo menos sistema crítico
        $admin->syncPermissions([
            'users.view',
            'users.create',
            'users.update',
        ]);

        /**
         * ======================
         * Usuarios
         * ======================
         */

        // MASTER (TÚ)
        $masterUser = User::firstOrCreate(
            ['email' => 'lieragomezosmaralejandro@gmail.com', 'username' => 'osmarlg'],
            [
                'name' => 'Osmar Liera',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $masterUser->syncRoles(['master']);
        
        $masterUser2 = User::firstOrCreate(
            ['email' => 'jeremy.ojeda@hotmail.com', 'username' => 'jeremy'],
            [
                'name' => 'Jeremy Ojeda',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $masterUser2->syncRoles(['master']);

        // ADMIN
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@avt.com', 'username' => 'admin'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $adminUser->syncRoles(['admin']);
    }
}
