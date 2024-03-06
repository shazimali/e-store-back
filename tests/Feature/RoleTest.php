<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Role;

class RoleTest extends TestCase
{
    use RefreshDatabase;
    protected $user;
    protected $role;
    protected $permissions;
    public function setUp(): void
    {
        parent::setUp();   
        $this->user = $this->createUser();
        $this->role = $this->createRole();
        $this->permissions = $this->createPermissions();
        $this->user = $this->postJson(route('token'),[
            'email' => $this->user->email,
            'password' => 'password'
        ]);
        
    }

    public function test_roles_list()
    {   
        $response = $this->withHeaders(['Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->user['token']
        ])->get('/api/roles',);

        $response->assertStatus(200);
    }

    public function test_role_create()
    {   
        $response = $this->withHeaders(['Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->user['token']
        ])->get('/api/users/create',);

        $response->assertStatus(200);
    }

    public function test_store_role()
    {
        $data = Role::factory()->make();
        $permissions = $this->permissions->pluck('id');
        $data = [
            'name' => $data->name,
            'permissions' => [$permissions[0]]
        ];
        $response = $this->withHeaders(['Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->user['token']
        ])->post('/api/roles/store',$data);

        $response->assertStatus(200);
    }

    public function test_edit_role()
    {
        $id = $this->role->id; 
        $response = $this->withHeaders(['Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->user['token']
        ])->get('/api/roles/edit/'.$id);

        $response->assertStatus(200);
    }

    public function test_update_role()
    {
        $id = $this->role->id;
        $permissions = $this->permissions->pluck('id');
        $data = [
            'name' => 'smith',
            'permissions' => [$permissions[0]]
        ];

        $response = $this->withHeaders(['Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->user['token']
        ])->put('/api/roles/update/'.$id,$data);

        $response->assertStatus(200);
    }

    public function test_role_user()
    {
        $id = $this->role->id; 
        $response = $this->withHeaders(['Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->user['token']
        ])->delete('/api/roles/delete/'.$id);

        $response->assertStatus(200);
    }
}
