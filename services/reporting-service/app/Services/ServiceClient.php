<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Client HTTP générique vers les services internes.
 * Le token JWT de l'utilisateur est propagé tel quel.
 */
class ServiceClient
{
    private const TIMEOUT = 10;

    /**
     * GET JSON vers un service interne.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function get(string $service, string $path, array $params, string $token): array
    {
        $response = $this->send(
            fn () => Http::withToken($token)->acceptJson()->timeout(self::TIMEOUT)
                ->get($this->baseUrl($service).$path, $params),
        );

        $this->assertOk($response, $service);

        /** @var array<string, mixed> $body */
        $body = $response->json() ?? [];

        return is_array($body) ? $body : [];
    }

    /**
     * Liste toutes les pages d'un endpoint paginé.
     *
     * @param  array<string, mixed>  $params
     * @return list<array<string, mixed>>
     */
    public function getAllPages(string $service, string $path, array $params, string $token, int $perPage = 100): array
    {
        $items = [];
        $page = 1;

        do {
            $body = $this->get($service, $path, array_merge($params, [
                'per_page' => $perPage,
                'page' => $page,
            ]), $token);

            /** @var list<array<string, mixed>> $data */
            $data = $body['data'] ?? [];
            $items = array_merge($items, $data);

            $lastPage = (int) ($body['meta']['last_page'] ?? 1);
            $page++;
        } while ($page <= $lastPage && $page <= 100); // borne : 10 000 lignes max

        return $items;
    }

    private function baseUrl(string $service): string
    {
        $key = "services.internal.{$service}";
        $url = (string) config($key);

        if ($url === '') {
            throw new \RuntimeException("URL non configurée pour le service « {$service} ».");
        }

        return rtrim($url, '/');
    }

    /** @param callable(): Response $call */
    private function send(callable $call): Response
    {
        try {
            return $call();
        } catch (ConnectionException $e) {
            throw new ServiceUnavailableHttpException(
                null,
                'Un service interne est indisponible : '.$e->getMessage(),
                $e,
            );
        }
    }

    private function assertOk(Response $response, string $service): void
    {
        $status = $response->status();

        if ($status >= 200 && $status < 300) {
            return;
        }

        throw new HttpException(
            $status >= 400 && $status < 600 ? $status : 502,
            "Le service « {$service} » a répondu HTTP {$status}.",
        );
    }
}
