<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_api_login_is_disabled(): void
    {
        $user = User::factory()->create([
            'email' => 'api-user@example.com',
            'password' => 'secret-password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret-password',
            'device_name' => 'postman',
        ]);

        $response->assertNotFound();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unauthenticated_api_request_without_accept_header_returns_json_401(): void
    {
        $this->post('/api/v1/webhook-endpoints', [
            'url' => 'https://receiver.example.com/webhook',
            'events' => ['invoice.created'],
        ])
            ->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJson([
                'success' => false,
                'message' => 'API tokenı eksik, geçersiz veya süresi dolmuş.',
            ]);
    }

    public function test_me_returns_the_bearer_token_owner(): void
    {
        $user = User::factory()->create();
        $plainTextToken = $user->createToken('postman', ['api:read', 'api:write'])->plainTextToken;

        $this->withToken($plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonMissingPath('data.password');
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $currentToken = $user->createToken('postman', ['api:read', 'api:write']);
        $otherToken = $user->createToken('integration', ['api:read']);

        $this->withToken($currentToken->plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $currentToken->accessToken->id,
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $otherToken->accessToken->id,
        ]);

        // PHPUnit aynı uygulama örneğinde ikinci isteği yaptığı için önceki auth
        // guard belleğini temizleyip yeni bir HTTP isteğini taklit ederiz.
        $this->app['auth']->forgetGuards();

        $this->withToken($currentToken->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

}
