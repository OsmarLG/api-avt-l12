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
        /**
         * =====================================
         * Reset cache de permisos (Spatie)
         * =====================================
         */
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /**
         * =====================================
         * Permisos
         * =====================================
         */
        $permissions = [
            // Users
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            // Roles & Permissions
            'roles.manage',
            'permissions.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission]);
        }

        /**
         * =====================================
         * Roles
         * =====================================
         */
        $master = Role::updateOrCreate(['name' => 'master']);
        $admin  = Role::updateOrCreate(['name' => 'admin']);

        // Master → todos los permisos
        $master->syncPermissions(Permission::all());

        // Admin → permisos limitados
        $admin->syncPermissions([
            'users.view',
            'users.create',
            'users.update',
        ]);

        /**
         * =====================================
         * Usuarios
         * =====================================
         */

        // ===== MASTER 1 =====
        $masterUser = User::updateOrCreate(
            ['email' => 'lieragomezosmaralejandro@gmail.com'],
            [
                'name' => 'Osmar Liera',
                'username' => 'osmarlg',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $masterUser->syncRoles(['master']);

        // ===== MASTER 2 =====
        $masterUser2 = User::updateOrCreate(
            ['email' => 'jeremy.ojeda@hotmail.com'],
            [
                'name' => 'Jeremy Ojeda',
                'username' => 'jeremy',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $masterUser2->syncRoles(['master']);

        // ===== ADMIN =====
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@avt.com'],
            [
                'name' => 'Admin User',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $adminUser->syncRoles(['admin']);

        /**
         * =====================================
         * Output consola (opcional)
         * =====================================
         */
        $this->command?->info('Roles, permisos y usuarios creados/actualizados correctamente.');
    }
}
