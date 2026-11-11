<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([RolesAndPermissionsSeeder::class, CatalogueSeeder::class]);

        $accounts = [
            ['name' => 'Library Admin', 'email' => 'admin@library.test', 'admission_number' => null, 'role' => Role::Admin],
            ['name' => 'Desk Librarian', 'email' => 'librarian@library.test', 'admission_number' => null, 'role' => Role::Librarian],
            ['name' => 'Sample Member', 'email' => 'member@library.test', 'admission_number' => 'ADM001', 'role' => Role::Member],
        ];

        foreach ($accounts as $account) {
            $user = User::firstOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'admission_number' => $account['admission_number'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles($account['role']->value);
        }
    }
}
