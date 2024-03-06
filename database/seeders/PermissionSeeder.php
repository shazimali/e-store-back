<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $permissions = [];
        //Users
        $permissions[0]['name'] = 'User Management';
        $permissions[0]['key'] = 'user_management';
        $permissions[1]['name'] = 'User Create';
        $permissions[1]['key'] = 'user_create';
        $permissions[2]['name'] = 'User Edit';
        $permissions[2]['key'] = 'user_edit';
        $permissions[3]['name'] = 'User Delete';
        $permissions[3]['key'] = 'user_delete';

        //roles
        $permissions[4]['name'] = 'Role Create';
        $permissions[4]['key'] = 'role_create';
        $permissions[5]['name'] = 'Role Edit';
        $permissions[5]['key'] = 'role_edit';
        $permissions[6]['name'] = 'Role Delete';
        $permissions[6]['key'] = 'role_delete';


        foreach ($permissions as $key => $permission) {

            \App\Models\Permission::create([
                'name' => $permission['name'],
                'key' => $permission['key']
            ]);
            
        }
    }
}
