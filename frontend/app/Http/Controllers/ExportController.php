<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\GatewayClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Relais des exports CSV : le navigateur ne peut pas envoyer le JWT
 * en header sur un <a href>, donc le frontend récupère le fichier
 * auprès de la gateway avec le token de session et le sert en
 * téléchargement. Le journal d'audit de l'export reste tracé
 * côté reporting-service.
 */
class ExportController extends Controller
{
    private const TYPES = ['assets', 'maintenance', 'inventory'];

    public function __invoke(Request $request, string $type, GatewayClient $gateway)
    {
        if (! in_array($type, self::TYPES, true)) {
            throw new NotFoundHttpException('Type d\'export inconnu.');
        }

        try {
            $response = $gateway->download("/api/reports/exports/{$type}.csv");
        } catch (HttpException $e) {
            Log::warning('Export CSV refusé par la gateway', ['type' => $type, 'status' => $e->getStatusCode()]);
            abort($e->getStatusCode() >= 400 && $e->getStatusCode() < 500 ? $e->getStatusCode() : 502);
        } catch (\Throwable $e) {
            Log::warning('Export CSV : gateway injoignable', ['type' => $type, 'error' => $e->getMessage()]);
            abort(503, 'Service de reporting indisponible.');
        }

        $filename = "export-{$type}-".now()->format('Ymd-His').'.csv';

        return response($response->body(), $response->status(), [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
