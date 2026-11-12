<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\GatewayClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthController extends Controller
{
    public function __construct(private readonly GatewayClient $gateway) {}

    public function showLogin(): View|RedirectResponse
    {
        if (is_string(session('jwt_token')) && session('jwt_token') !== '') {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $response = $this->gateway->post('/api/auth/login', $credentials);
        } catch (\Throwable $e) {
            Log::warning('Frontend login : gateway injoignable', ['error' => $e->getMessage()]);

            return back()->withErrors([
                'email' => 'Service d\'authentification indisponible. Réessayez plus tard.',
            ])->withInput($request->only('email'));
        }

        $token = $response['access_token'] ?? $response['token'] ?? null;
        $user = $response['user'] ?? null;

        if (! is_string($token) || $token === '' || ! is_array($user)) {
            return back()->withErrors([
                'email' => $response['message'] ?? 'Identifiants invalides.',
            ])->withInput($request->only('email'));
        }

        $role = $user['role'] ?? null;

        session([
            'jwt_token' => $token,
            'jwt_user' => [
                'id' => $user['id'] ?? null,
                'name' => $user['name'] ?? null,
                'email' => $user['email'] ?? $credentials['email'],
                'role' => is_array($role) ? ($role['slug'] ?? null) : null,
                'role_name' => is_array($role) ? ($role['name'] ?? null) : null,
            ],
        ]);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        try {
            $this->gateway->post('/api/auth/logout');
        } catch (HttpException|\Throwable) {
            // Best effort : le token expirera de toute façon
        }

        $request->session()->forget(['jwt_token', 'jwt_user']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
