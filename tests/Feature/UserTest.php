<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;

class UserTest extends TestCase
{
    use RefreshDatabase;
    protected $user;
    protected $role;
    public function setUp(): void
    {
        parent::setUp();   
        $this->user = $this->createUser();
        $this->role = $this->createRole();
        $this->user = $this->postJson(route('token'),[
            'email' => $this->user->email,
            'password' => 'password'
        ]);
        
    }

    public function test_users_list()
    {   
        $response = $this->withHeaders(['Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->user['token']
        ])->get('/api/users',);

        $response->assertStatus(200);
    }

    public function test_users_create()
    {   
        $response = $this->withHeaders(['Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->user['token']
        ])->get('/api/users/create',);

        $response->assertStatus(200);
    }

    public function test_store_user()
    {
        $data = User::factory()->make();
        $data = [
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password,
            'password_confirmation' => $data->password,
            'roles' => [$this->role->id]
        ];
        $response = $this->withHeaders(['Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->user['token']
        ])->post('/api/users/store',$data);

        $response->assertStatus(200);
    }

    public function test_edit_user()
    {
        $id = $this->user['user']['id']; 
        $response = $this->withHeaders(['Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->user['token']
        ])->get('/api/users/edit/'.$id);

        $response->assertStatus(200);
    }

    public function test_update_user()
    {
        $id = $this->user['user']['id']; 
        $roles = Role::pluck('id'); 
        $data = [
            'name' => 'smith',
            'email' => 'smith_gold@rana.com', 
            'password' => 11223344,
            'password_confirmation' => 11223344,
            'roles' => [$roles[0]]
        ];

        $response = $this->withHeaders(['Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->user['token']
        ])->put('/api/users/update/'.$id,$data);

        $response->assertStatus(200);
    }

    public function test_delete_user()
    {
        $id = $this->user['user']['id']; 
        $response = $this->withHeaders(['Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->user['token']
        ])->delete('/api/users/delete/'.$id);

        $response->assertStatus(200);
    }
}
