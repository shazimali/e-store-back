<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AssignPermissionsToRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role =  \App\Models\Role::first();
        $permissions =  \App\Models\Permission::get()->pluck('id');
        $role->permissions()->sync($permissions);
    }
}
