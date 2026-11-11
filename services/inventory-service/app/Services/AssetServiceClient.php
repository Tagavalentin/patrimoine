<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Client HTTP vers l'asset-service : liste paginée des biens
 * pour alimenter les lignes d'inventaire. Token JWT propagé.
 */
class AssetServiceClient
{
    private const TIMEOUT = 10;

    private const PER_PAGE = 100;

    private const MAX_PAGES = 50; // garde-fou : 5000 biens max par campagne

    /**
     * Récupère tous les biens correspondant aux filtres (toutes pages).
     *
     * @param  array<string, mixed>  $filters  department_id, location_id…
     * @return list<array<string, mixed>>
     */
    public function listAssets(array $filters, string $token): array
    {
        $assets = [];
        $page = 1;

        do {
            $response = $this->send(
                fn () => Http::withToken($token)->acceptJson()->timeout(self::TIMEOUT)
                    ->get($this->url().'/api/assets', array_merge($filters, [
                        'per_page' => self::PER_PAGE,
                        'page' => $page,
                    ])),
            );

            if ($response->status() !== 200) {
                throw new HttpException(
                    $response->status() >= 400 && $response->status() < 600 ? $response->status() : 502,
                    "Le service des biens a refusé la liste des biens (HTTP {$response->status()}).",
                );
            }

            /** @var array<string, mixed> $body */
            $body = $response->json() ?? [];
            /** @var list<array<string, mixed>> $items */
            $items = $body['data'] ?? [];
            $assets = array_merge($assets, $items);

            $lastPage = (int) ($body['meta']['last_page'] ?? 1);
            $page++;
        } while ($page <= $lastPage && $page <= self::MAX_PAGES);

        return $assets;
    }

    private function url(): string
    {
        return rtrim((string) config('services.asset.url'), '/');
    }

    /**
     * @param  callable(): Response  $call
     */
    private function send(callable $call)
    {
        try {
            return $call();
        } catch (ConnectionException $e) {
            throw new ServiceUnavailableHttpException(
                null,
                'Le service des biens est indisponible : '.$e->getMessage(),
                $e,
            );
        }
    }
}
