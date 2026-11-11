<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN = ['email' => 'admin@patrimoine.local', 'password' => 'password'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function login(array $credentials = self::ADMIN): string
    {
        $response = $this->postJson('/api/login', $credentials);
        $response->assertOk();

        return (string) $response->json('access_token');
    }

    private function authHeaders(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    // ------------------------------------------------ login

    public function test_login_with_valid_credentials_returns_a_token(): void
    {
        $response = $this->postJson('/api/login', self::ADMIN);

        $response->assertOk()
            ->assertJsonStructure([
                'token_type',
                'access_token',
                'expires_in',
                'user' => ['id', 'name', 'email', 'role' => ['slug'], 'permissions'],
            ])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', self::ADMIN['email'])
            ->assertJsonPath('user.role.slug', 'admin');
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        $this->postJson('/api/login', [
            'email' => self::ADMIN['email'],
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_login_with_unknown_email_is_rejected(): void
    {
        $this->postJson('/api/login', [
            'email' => 'inconnu@patrimoine.local',
            'password' => 'password',
        ])->assertUnprocessable();
    }

    // ------------------------------------------------ routes protégées

    public function test_me_without_token_returns_401(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_me_with_invalid_token_returns_401(): void
    {
        $this->getJson('/api/me', $this->authHeaders('token.invalide'))
            ->assertUnauthorized();
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $token = $this->login(['email' => 'auditor@patrimoine.local', 'password' => 'password']);

        $this->getJson('/api/me', $this->authHeaders($token))
            ->assertOk()
            ->assertJsonPath('data.email', 'auditor@patrimoine.local')
            ->assertJsonPath('data.role.slug', 'auditor')
            ->assertJsonStructure(['data' => ['permissions']]);
    }

    public function test_refresh_issues_a_new_valid_token(): void
    {
        $token = $this->login();

        $response = $this->postJson('/api/refresh', [], $this->authHeaders($token));

        $response->assertOk()->assertJsonStructure(['access_token']);

        // Le nouveau token fonctionne
        $this->getJson('/api/me', $this->authHeaders($response->json('access_token')))
            ->assertOk();
    }

    public function test_logout_returns_a_message(): void
    {
        $token = $this->login();

        $this->postJson('/api/logout', [], $this->authHeaders($token))
            ->assertOk()
            ->assertJsonStructure(['message']);
    }

    // ------------------------------------------------ CRUD utilisateurs (admin)

    public function test_users_index_requires_authentication(): void
    {
        $this->getJson('/api/users')->assertUnauthorized();
    }

    public function test_users_index_is_forbidden_for_non_admin(): void
    {
        $token = $this->login(['email' => 'auditor@patrimoine.local', 'password' => 'password']);

        $this->getJson('/api/users', $this->authHeaders($token))
            ->assertForbidden();
    }

    public function test_admin_can_list_users(): void
    {
        $token = $this->login();

        $this->getJson('/api/users', $this->authHeaders($token))
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['total']])
            ->assertJsonCount(5, 'data');
    }

    public function test_admin_can_create_a_user(): void
    {
        $token = $this->login();

        $response = $this->postJson('/api/users', [
            'name' => 'Nouveau Gestionnaire',
            'email' => 'gestionnaire2@patrimoine.local',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
            'role' => 'asset_manager',
        ], $this->authHeaders($token));

        $response->assertCreated()
            ->assertJsonPath('data.email', 'gestionnaire2@patrimoine.local')
            ->assertJsonPath('data.role.slug', 'asset_manager');

        // Le nouvel utilisateur peut se connecter
        $this->postJson('/api/login', [
            'email' => 'gestionnaire2@patrimoine.local',
            'password' => 'motdepasse123',
        ])->assertOk();
    }

    public function test_create_user_with_invalid_role_is_rejected(): void
    {
        $token = $this->login();

        $this->postJson('/api/users', [
            'name' => 'Test',
            'email' => 'test@patrimoine.local',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
            'role' => 'super_admin_inexistant',
        ], $this->authHeaders($token))->assertUnprocessable()
            ->assertJsonValidationErrors('role');
    }

    public function test_admin_can_update_a_user(): void
    {
        $token = $this->login();
        $userId = \App\Models\User::where('email', 'auditor@patrimoine.local')->value('id');

        $this->putJson("/api/users/{$userId}", [
            'name' => 'Auditeur Principal',
            'role' => 'accountant',
        ], $this->authHeaders($token))
            ->assertOk()
            ->assertJsonPath('data.name', 'Auditeur Principal')
            ->assertJsonPath('data.role.slug', 'accountant');
    }

    public function test_admin_cannot_delete_himself(): void
    {
        $token = $this->login();
        $adminId = \App\Models\User::where('email', self::ADMIN['email'])->value('id');

        $this->deleteJson("/api/users/{$adminId}", [], $this->authHeaders($token))
            ->assertUnprocessable();
    }

    public function test_admin_can_delete_another_user(): void
    {
        $token = $this->login();
        $userId = \App\Models\User::where('email', 'auditor@patrimoine.local')->value('id');

        $this->deleteJson("/api/users/{$userId}", [], $this->authHeaders($token))
            ->assertNoContent();

        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }
}
