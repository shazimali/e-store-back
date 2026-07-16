<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_notification()
    {
        Notification::fake();

        $user = $this->createUser();

        $response = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
            'recaptcha_token' => 'mock_token'
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'message' => 'We have emailed your password reset link.'
        ]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_forgot_password_requires_existing_email()
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'nonexistent@example.com',
            'recaptcha_token' => 'mock_token'
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['email']]);
    }

    public function test_reset_password_updates_user_password()
    {
        $user = $this->createUser();
        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'message' => 'Your password has been reset successfully.'
        ]);

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_reset_password_requires_valid_token()
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(400);
        $response->assertJsonStructure(['status', 'message']);
    }
}
