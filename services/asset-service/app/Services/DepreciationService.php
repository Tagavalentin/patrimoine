<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Asset;
use Carbon\CarbonImmutable;

/**
 * Amortissement linéaire calculé à la volée (pas de table dédiée :
 * la valorisation est une projection déterministe des champs
 * acquisition_date / acquisition_value / useful_life_years).
 *
 * Convention : dotation proratisée au mois, plancher à 0,
 * durée de vie épuisée → valeur nette comptable nulle.
 */
class DepreciationService
{
    /**
     * Valorisation d'un bien à une date donnée (défaut : aujourd'hui).
     *
     * @return array<string, mixed>
     */
    public function valuation(Asset $asset, ?string $date = null): array
    {
        $asOf = $date !== null ? CarbonImmutable::parse($date) : CarbonImmutable::today();
        $acquisition = CarbonImmutable::parse($asset->acquisition_date);
        $lifeMonths = max(1, (int) $asset->useful_life_years) * 12;
        $gross = (float) $asset->acquisition_value;

        // Mois écoulés bornés à la durée de vie
        $elapsedMonths = (int) $acquisition->diffInMonths($asOf, false);
        $elapsedMonths = max(0, min($elapsedMonths, $lifeMonths));

        $monthly = $gross / $lifeMonths;
        $accumulated = round($monthly * $elapsedMonths, 2);
        $netBookValue = round(max(0.0, $gross - $accumulated), 2);

        return [
            'asset_id' => $asset->id,
            'asset_code' => $asset->code,
            'designation' => $asset->designation,
            'status' => $asset->status?->value ?? $asset->status,
            'as_of' => $asOf->toDateString(),
            'acquisition_date' => $acquisition->toDateString(),
            'acquisition_value' => $gross,
            'useful_life_years' => (int) $asset->useful_life_years,
            'elapsed_months' => $elapsedMonths,
            'remaining_months' => $lifeMonths - $elapsedMonths,
            'monthly_depreciation' => round($monthly, 2),
            'accumulated_depreciation' => $accumulated,
            'net_book_value' => $netBookValue,
            'fully_depreciated' => $netBookValue <= 0,
        ];
    }

    /**
     * Échéancier annuel : dotation et valeur nette en fin d'année.
     *
     * @return list<array<string, mixed>>
     */
    public function schedule(Asset $asset): array
    {
        $lifeYears = max(1, (int) $asset->useful_life_years);
        $gross = (float) $asset->acquisition_value;
        $annual = round($gross / $lifeYears, 2);
        $acquisition = CarbonImmutable::parse($asset->acquisition_date);

        $rows = [];
        $accumulated = 0.0;

        for ($year = 1; $year <= $lifeYears; $year++) {
            $yearEnd = $acquisition->addYears($year)->subDay();
            $accumulated = round(min($gross, $annual * $year), 2);
            $net = round(max(0.0, $gross - $accumulated), 2);

            $rows[] = [
                'year' => $year,
                'year_end' => $yearEnd->toDateString(),
                'annual_depreciation' => $annual,
                'accumulated_depreciation' => $accumulated,
                'net_book_value' => $net,
            ];
        }

        return $rows;
    }

    /**
     * Synthèse portefeuille : actifs non sortis du patrimoine.
     *
     * @return array<string, mixed>
     */
    public function portfolioSummary(?string $date = null): array
    {
        $assets = Asset::where('status', '!=', 'disposed')->get();

        $totals = [
            'assets_count' => $assets->count(),
            'gross_value' => 0.0,
            'accumulated_depreciation' => 0.0,
            'net_book_value' => 0.0,
            'fully_depreciated_count' => 0,
        ];

        foreach ($assets as $asset) {
            $v = $this->valuation($asset, $date);
            $totals['gross_value'] += $v['acquisition_value'];
            $totals['accumulated_depreciation'] += $v['accumulated_depreciation'];
            $totals['net_book_value'] += $v['net_book_value'];
            if ($v['fully_depreciated']) {
                $totals['fully_depreciated_count']++;
            }
        }

        $totals['gross_value'] = round($totals['gross_value'], 2);
        $totals['accumulated_depreciation'] = round($totals['accumulated_depreciation'], 2);
        $totals['net_book_value'] = round($totals['net_book_value'], 2);
        $totals['as_of'] = ($date !== null ? CarbonImmutable::parse($date) : CarbonImmutable::today())->toDateString();

        return $totals;
    }
}
