<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Jwt\JwtService;
use Firebase\JWT\JWT;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tests\TestCase;

class JwtServiceTest extends TestCase
{
    private JwtService $jwt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jwt = new JwtService;
    }

    public function test_issue_and_decode_roundtrip(): void
    {
        $token = $this->jwt->issue([
            'sub' => 42,
            'email' => 'test@patrimoine.local',
            'role' => 'admin',
        ]);

        $claims = $this->jwt->decode($token);

        $this->assertSame(42, $claims['sub']);
        $this->assertSame('test@patrimoine.local', $claims['email']);
        $this->assertSame('admin', $claims['role']);
        $this->assertSame('patrimoine-auth-service', $claims['iss']);
        $this->assertArrayHasKey('jti', $claims);
    }

    public function test_tampered_token_is_rejected(): void
    {
        $token = $this->jwt->issue(['sub' => 1, 'role' => 'admin']);
        $tampered = substr($token, 0, -4).'AAAA';

        $this->expectException(UnauthorizedHttpException::class);
        $this->jwt->decode($tampered);
    }

    public function test_expired_token_is_rejected(): void
    {
        $expired = JWT::encode([
            'sub' => 1,
            'role' => 'admin',
            'iat' => now()->subHour()->timestamp,
            'exp' => now()->subMinute()->timestamp,
        ], (string) config('jwt.secret'), JwtService::ALG);

        $this->expectException(UnauthorizedHttpException::class);
        $this->jwt->decode($expired);
    }
}
