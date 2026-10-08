<?php

declare(strict_types=1);

namespace App\Domain\Farming\Actions;

use App\Constants\FloodRiskConstants;
use App\Domain\Farming\DTOs\FloodRiskAssessment;
use App\Domain\Farming\Enums\FloodRiskLevel;
use Carbon\CarbonImmutable;
use Domain\Farming\Models\Plot;
use Illuminate\Support\Facades\DB;

final class AssessPlotFloodRiskAction
{
    private const int LONGITUDE_INDEX = 0;

    private const int LATITUDE_INDEX = 1;

    private const int FIRST_INDEX = 0;

    /**
     * @param  array<int, array{float, float}>  $coordinates
     */
    public function __invoke(array $coordinates): FloodRiskAssessment
    {
        if (DB::table('flood_hazard_zones')->count() === 0) {
            return $this->unknown();
        }

        $wkt = $this->toWkt($coordinates);
        $level = $this->highestTouchingClass($wkt) ?? FloodRiskLevel::SAFE;
        $withinCoverage = $this->withinCoverage($wkt);

        return new FloodRiskAssessment(
            level: $level,
            advice: FloodRiskConstants::adviceFor($level, $withinCoverage),
            withinCoverage: $withinCoverage,
            assessedAt: CarbonImmutable::now(),
        );
    }

    public function forPlot(Plot $plot): FloodRiskAssessment
    {
        $plotId = $plot->id;
        $ring = is_int($plotId) ? $this->plotRing($plotId) : null;

        if ($ring === null) {
            return $this->unknown();
        }

        return $this->__invoke($ring);
    }

    /**
     * @param  array<int, array{float, float}>  $coordinates
     */
    private function toWkt(array $coordinates): string
    {
        $coords = $coordinates;

        if (count($coords) > self::FIRST_INDEX && $coords[self::FIRST_INDEX] !== end($coords)) {
            $coords[] = $coords[self::FIRST_INDEX];
        }

        $points = array_map(
            fn (array $point): string => (float) $point[self::LONGITUDE_INDEX].' '.(float) $point[self::LATITUDE_INDEX],
            $coords
        );

        return 'POLYGON(('.implode(', ', $points).'))';
    }

    private function highestTouchingClass(string $wkt): ?FloodRiskLevel
    {
        $row = DB::selectOne(
            "SELECT hazard_class FROM flood_hazard_zones WHERE ST_Intersects(polygon, ST_GeomFromText(?, 4326)) ORDER BY CASE hazard_class WHEN 'high' THEN 3 WHEN 'medium' THEN 2 ELSE 1 END DESC LIMIT 1",
            [$wkt]
        );

        if (! is_object($row) || ! is_string($row->hazard_class ?? null)) {
            return null;
        }

        return FloodRiskLevel::tryFrom($row->hazard_class);
    }

    private function withinCoverage(string $wkt): bool
    {
        $row = DB::selectOne(
            'SELECT EXISTS(SELECT 1 FROM flood_hazard_zones WHERE ST_Intersects(ST_Envelope(polygon), ST_Envelope(ST_GeomFromText(?, 4326)))) AS covered',
            [$wkt]
        );

        return (bool) ($row->covered ?? false);
    }

    /**
     * @return array<int, array{float, float}>|null
     */
    private function plotRing(?int $plotId): ?array
    {
        if ($plotId === null) {
            return null;
        }

        $row = DB::selectOne('SELECT ST_AsGeoJSON(polygon) AS geojson FROM plots WHERE id = ?', [$plotId]);

        if (! is_object($row) || ! is_string($row->geojson ?? null)) {
            return null;
        }

        $decoded = json_decode($row->geojson, true);
        $ring = is_array($decoded) ? ($decoded['coordinates'][0] ?? null) : null;

        return is_array($ring) ? $ring : null;
    }

    private function unknown(): FloodRiskAssessment
    {
        return new FloodRiskAssessment(
            level: FloodRiskLevel::UNKNOWN,
            advice: FloodRiskConstants::adviceFor(FloodRiskLevel::UNKNOWN, false),
            withinCoverage: false,
            assessedAt: CarbonImmutable::now(),
        );
    }
}
