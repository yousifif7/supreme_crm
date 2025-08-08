<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        User::where(['email' => 'admin@gmail.com'])->update(['password' => Hash::make('654321')]);
/*        $user = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'username' => 'superadmin',
                'password' => Hash::make('password'),
            ]
        );
        $user1 = User::firstOrCreate(
            ['email' => 'admin@supremesecurity.co.uk'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'username' => 'admin',
                'password' => Hash::make('12#Admin'),
            ]
        );
        $role = Role::where('name', 'superadmin')->first();
        if ($role) {
            $user->assignRole($role);
            $user1->assignRole($role);
        }*/
    }
}
