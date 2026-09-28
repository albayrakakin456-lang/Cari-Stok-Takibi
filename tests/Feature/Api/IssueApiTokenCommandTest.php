<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueApiTokenCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_issues_read_and_write_token_by_default(): void
    {
        $user = User::factory()->create(['email' => 'integration@example.com']);

        $this->artisan('api:issue-token', [
            'email' => $user->email,
            '--name' => 'postman-test',
        ])->assertSuccessful();

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'postman-test',
            'abilities' => '["api:read","api:write"]',
        ]);
    }

    public function test_command_can_issue_a_read_only_token(): void
    {
        $user = User::factory()->create(['email' => 'reader@example.com']);

        $this->artisan('api:issue-token', [
            'email' => $user->email,
            '--name' => 'read-only-test',
            '--read-only' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'read-only-test',
            'abilities' => '["api:read"]',
        ]);
    }

    public function test_command_fails_when_user_does_not_exist(): void
    {
        $this->artisan('api:issue-token', [
            'email' => 'missing@example.com',
        ])
            ->expectsOutput('missing@example.com adresine sahip kullanıcı bulunamadı.')
            ->assertFailed();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
