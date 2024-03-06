<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
class UserRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user =  \App\Models\User::first();
        $role =  \App\Models\Role::first();
        $user->roles()->sync([$role->id]);
    }
}
