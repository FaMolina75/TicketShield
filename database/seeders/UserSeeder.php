<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (app()->environment('production') && !env('ALLOW_SEEDERS_IN_PRODUCTION', false)) {
            $this->command->warn('UserSeeder: Ejecución de seeders bloqueada en entorno de producción por seguridad.');
            return;
        }

        User::truncate();
        Role::truncate();
        Permission::truncate();
        Location::truncate();
        $role_admin = Role::create(['name' => 'super-admin']);
        $role_user = Role::create(['name' => 'user']);
        Permission::create(['name' => 'dashboard'])->syncRoles([$role_user, $role_admin]);

        $adminPassword = env('ADMIN_DEFAULT_PASSWORD', 'TicketShield@2026!AdminSec');
        $user_admin = User::factory()
            ->has(Location::factory()->count(5))
            ->create([
                'email' => env('ADMIN_DEFAULT_EMAIL', 'admin@ticketshield.local'),
                'password' => \Illuminate\Support\Facades\Hash::make($adminPassword),
                'email_verified_at' => now(),
                'remember_token' => null,
            ]);
        $user_admin->assignRole($role_admin);

        $user = User::factory()
            ->has(Location::factory()->count(5))
            ->create([
                'email' => 'user2@user.com',
                'email_verified_at' => null,
                'remember_token' => null,
            ]);
        $user->assignRole($role_user);

        $users = User::factory(50)
            ->has(Location::factory()->count(5))
            ->create();

        foreach ($users as $user) {
            $user->assignRole($role_user);
        }
    }
}
