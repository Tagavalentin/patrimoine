<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_displayed_for_guests(): void
    {
        $this->get('/login')->assertOk()->assertSee('Se connecter');
    }

    public function test_login_stores_token_and_redirects_to_dashboard(): void
    {
        Http::fake([
            '*/api/auth/login' => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'jwt-test-token',
                'user' => [
                    'id' => 1,
                    'name' => 'Admin',
                    'email' => 'admin@patrimoine.local',
                    'role' => ['id' => 1, 'name' => 'Administrateur', 'slug' => 'admin'],
                ],
            ], 200),
            '*/api/auth/me' => Http::response([
                'data' => [
                    'id' => 1,
                    'name' => 'Admin',
                    'email' => 'admin@patrimoine.local',
                    'role' => ['id' => 1, 'name' => 'Administrateur', 'slug' => 'admin'],
                ],
            ], 200),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@patrimoine.local',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertSame('jwt-test-token', session('jwt_token'));
        $this->assertSame('admin', session('jwt_user.role'));
    }

    public function test_login_with_invalid_credentials_shows_error(): void
    {
        Http::fake([
            '*/api/auth/login' => Http::response([
                'message' => 'Identifiants invalides.',
            ], 401),
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'bad@patrimoine.local',
            'password' => 'wrong',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');
    }

    public function test_login_when_gateway_is_down_shows_error(): void
    {
        Http::fake(fn () => throw new ConnectionException('connexion refusée'));

        $this->from('/login')->post('/login', [
            'email' => 'admin@patrimoine.local',
            'password' => 'password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        Http::fake([
            '*/api/auth/me' => Http::response([
                'data' => [
                    'id' => 1,
                    'name' => 'Admin',
                    'email' => 'admin@patrimoine.local',
                    'role' => ['id' => 1, 'name' => 'Administrateur', 'slug' => 'admin'],
                ],
            ], 200),
        ]);

        $this->withSession(['jwt_token' => 'jwt-test-token'])
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Tableau de bord');
    }

    public function test_logout_clears_session_and_calls_gateway(): void
    {
        Http::fake([
            '*/api/auth/logout' => Http::response(['message' => 'ok'], 200),
        ]);

        $this->withSession(['jwt_token' => 'jwt-test-token', 'jwt_user' => ['id' => 1]])
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertNull(session('jwt_token'));
    }
}
