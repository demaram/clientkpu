<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds one login per role (test-<role>@dev.local / Testing123!) for manual QA
 * in the browser. Safe to re-run: users are upserted by email, roles are
 * re-synced each time. Users table is shared across payroll/portalkpu/clientkpu,
 * so seeding from any one project makes the accounts usable on all three domains.
 */
class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['administrator', 'pic', 'karyawan', 'superadmin', 'bpjs', 'sdm', 'sdmlead', 'client'];

        foreach ($roles as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if (! $role) {
                $this->command?->warn("Role '{$roleName}' not found, skipping.");

                continue;
            }

            $user = User::updateOrCreate(
                ['email' => "test-{$roleName}@dev.local"],
                [
                    'name' => 'Test '.ucfirst($roleName),
                    'password' => Hash::make('Testing123!'),
                    'is_active' => 1,
                    'id_client' => $roleName === 'client' ? 1 : null,
                ]
            );

            $user->detachRoles();
            $user->attachRole($role);
        }
    }
}
