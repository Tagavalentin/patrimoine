<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Client HTTP vers l'asset-service.
 *
 * - Le token JWT de l'utilisateur est propagé tel quel (Authorization).
 * - Timeout court (5 s) ; toute indisponibilité produit une 503
 *   explicite plutôt qu'une attente infinie.
 */
class AssetServiceClient
{
    private const TIMEOUT = 5;

    /**
     * Récupère un bien. Retourne null si introuvable (404).
     *
     * @return array<string, mixed>|null
     */
    public function findAsset(int $assetId, string $token): ?array
    {
        $response = $this->send(
            fn () => Http::withToken($token)->acceptJson()->timeout(self::TIMEOUT)
                ->get("{$this->url()}/api/assets/{$assetId}"),
        );

        if ($response->status() === 404) {
            return null;
        }

        $this->assertSuccessful($response->status(), 'consultation du bien');

        /** @var array<string, mixed>|null $data */
        $data = $response->json('data') ?? $response->json();

        return is_array($data) ? $data : null;
    }

    /**
     * Change le statut d'un bien côté asset-service.
     */
    public function setAssetStatus(int $assetId, string $status, ?string $reason, string $token): void
    {
        $response = $this->send(
            fn () => Http::withToken($token)->acceptJson()->timeout(self::TIMEOUT)
                ->post("{$this->url()}/api/assets/{$assetId}/status", [
                    'status' => $status,
                    'reason' => $reason,
                ]),
        );

        $this->assertSuccessful($response->status(), 'changement de statut du bien');
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

    private function assertSuccessful(int $status, string $action): void
    {
        if ($status >= 200 && $status < 300) {
            return;
        }

        if ($status === 404) {
            throw new NotFoundHttpException("Bien introuvable lors de la {$action}.");
        }

        throw new HttpException(
            $status >= 400 && $status < 600 ? $status : 502,
            "Le service des biens a refusé la {$action} (HTTP {$status}).",
        );
    }
}
