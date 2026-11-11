<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Create every role and permission the application relies on.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RoleEnum::cases() as $roleEnum) {
            foreach ($roleEnum->permissions() as $permission) {
                Permission::findOrCreate($permission);
            }

            Role::findOrCreate($roleEnum->value)->syncPermissions($roleEnum->permissions());
        }
    }
}
