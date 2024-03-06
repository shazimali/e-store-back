<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_get_token_with_email_and_password()
    {
        $user = $this->createUser();

        $response = $this->postJson(route('token'),[
            'email' => $user->email,
            'password' => 'password'
        ])
        ->assertOk();

        $this->assertArrayHasKey('token',$response->json());
    }

    public function test_if_user_email_is_not_available_then_it_return_error()
    {
        $this->postJson(route('token'),[
            'email' => 'abc@abc.com',
            'password' => '1231234'
        ])
        ->assertUnauthorized();
    }

    public function test_it_raise_error_if_password_is_incorrect()
    {
        $user = $this->createUser();

        $this->postJson(route('token'),[
            'email' => $user->email,
            'password' => 'random'
        ])
        ->assertUnauthorized();
    }
}
