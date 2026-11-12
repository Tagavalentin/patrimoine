<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $service) {}

    /** Tableau de bord consolidé (tous services internes). */
    public function dashboard(Request $request): JsonResponse
    {
        $this->authorize('viewDashboard');

        return response()->json([
            'data' => $this->service->dashboard((string) $request->bearerToken()),
        ]);
    }

    /** Export CSV des biens. */
    public function assetsCsv(Request $request): StreamedResponse
    {
        $this->authorize('export');

        $filters = $request->validate([
            'status' => ['sometimes', 'string'],
            'category_id' => ['sometimes', 'integer'],
            'department_id' => ['sometimes', 'integer'],
            'location_id' => ['sometimes', 'integer'],
        ]);

        $user = $request->user();

        return $this->service->assetsCsv(
            $filters,
            (string) $request->bearerToken(),
            $user?->getAuthIdentifier(),
            $user?->email(),
            $user?->email(),
            $request->ip(),
        );
    }

    /** Export CSV des demandes de maintenance. */
    public function maintenanceCsv(Request $request): StreamedResponse
    {
        $this->authorize('export');

        $filters = $request->validate([
            'status' => ['sometimes', 'string'],
            'asset_id' => ['sometimes', 'integer'],
            'priority' => ['sometimes', 'string'],
        ]);

        $user = $request->user();

        return $this->service->maintenanceCsv(
            $filters,
            (string) $request->bearerToken(),
            $user?->getAuthIdentifier(),
            $user?->email(),
            $user?->email(),
            $request->ip(),
        );
    }

    /** Export CSV des campagnes d'inventaire. */
    public function inventoryCsv(Request $request): StreamedResponse
    {
        $this->authorize('export');

        $filters = $request->validate([
            'status' => ['sometimes', 'string'],
            'search' => ['sometimes', 'string', 'max:100'],
        ]);

        $user = $request->user();

        return $this->service->inventoryCsv(
            $filters,
            (string) $request->bearerToken(),
            $user?->getAuthIdentifier(),
            $user?->email(),
            $user?->email(),
            $request->ip(),
        );
    }

    /** Journal d'audit des rapports générés. */
    public function auditLogs(Request $request): JsonResponse
    {
        $this->authorize('viewAudit');

        $filters = $request->validate([
            'report_type' => ['sometimes', 'string'],
            'generated_by' => ['sometimes', 'integer'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json(['data' => $this->service->auditLogs($filters)]);
    }
}
