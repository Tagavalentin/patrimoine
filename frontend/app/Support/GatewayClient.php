<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Client HTTP vers l'API Gateway — SEUL point d'accès aux services.
 * Le JWT de session est propagé dans le header Authorization.
 */
class GatewayClient
{
    private const TIMEOUT = 10;

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function get(string $path, array $params = []): array
    {
        return $this->send(
            fn () => $this->request()->get($this->url($path), $params),
        )->json() ?? [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function post(string $path, array $data = []): array
    {
        return $this->send(
            fn () => $this->request()->post($this->url($path), $data),
        )->json() ?? [];
    }

    /**
     * Téléchargement brut (exports CSV) — retourne la réponse HTTP
     * pour que le contrôleur la relaie telle quelle au navigateur.
     */
    public function download(string $path): Response
    {
        return $this->send(fn () => $this->request()->get($this->url($path)));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function patch(string $path, array $data = []): array
    {
        return $this->send(
            fn () => $this->request()->patch($this->url($path), $data),
        )->json() ?? [];
    }

    public function delete(string $path): ?Response
    {
        return $this->send(fn () => $this->request()->delete($this->url($path)));
    }

    /**
     * Appel authentifié par le token de session.
     */
    private function request()
    {
        $pending = Http::acceptJson()->timeout(self::TIMEOUT);

        $token = session('jwt_token');
        if (is_string($token) && $token !== '') {
            $pending = $pending->withToken($token);
        }

        return $pending;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.gateway.url'), '/').'/'.ltrim($path, '/');
    }

    /** @param callable(): Response $call */
    private function send(callable $call): Response
    {
        try {
            return $call();
        } catch (ConnectionException $e) {
            throw new ServiceUnavailableHttpException(
                null,
                'La passerelle API est indisponible : '.$e->getMessage(),
                $e,
            );
        }
    }
}
