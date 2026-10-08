<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class ImportNoahFloodZonesCommand extends Command
{
    protected $signature = 'noah:import-flood-zones {path : Path to NOAH flood GeoJSON} {--return-period=5}';

    protected $description = 'Import Project NOAH flood hazard polygons into flood_hazard_zones';

    public function handle(): int
    {
        $path = (string) $this->argument('path');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $features = $this->readFeatures($path);

        if ($features === null) {
            $this->error("Invalid GeoJSON: {$path}");

            return self::FAILURE;
        }

        [$imported, $invalid, $unknown] = $this->importFeatures($features, (int) $this->option('return-period'));
        $this->info("Imported {$imported} zones (skipped_invalid: {$invalid}, skipped_unknown_class: {$unknown})");

        return self::SUCCESS;
    }

    /**
     * @return array<int, mixed>|null
     */
    private function readFeatures(string $path): ?array
    {
        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded) || ! isset($decoded['features']) || ! is_array($decoded['features'])) {
            return null;
        }

        return $decoded['features'];
    }

    /**
     * @param  array<int, mixed>  $features
     * @return array{int, int, int}
     */
    private function importFeatures(array $features, int $returnPeriod): array
    {
        $imported = 0;
        $invalid = 0;
        $unknown = 0;

        foreach ($features as $feature) {
            if (! is_array($feature)) {
                $invalid++;

                continue;
            }

            $class = $this->normalizeClass((array) ($feature['properties'] ?? []));

            if ($class === null) {
                $unknown++;

                continue;
            }

            $geometry = $feature['geometry'] ?? null;

            if (! is_array($geometry)) {
                $invalid++;

                continue;
            }

            $geojson = json_encode($geometry);

            if (! is_string($geojson) || ! $this->isValidGeometry($geojson)) {
                $invalid++;

                continue;
            }

            $this->insertZone($class, $geojson, $returnPeriod);
            $imported++;
        }

        return [$imported, $invalid, $unknown];
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function normalizeClass(array $properties): ?string
    {
        $hazard = $properties['hazard'] ?? null;

        if (is_string($hazard)) {
            $match = match (strtolower($hazard)) {
                'low' => 'low',
                'medium' => 'medium',
                'high' => 'high',
                default => null,
            };

            if ($match !== null) {
                return $match;
            }
        }

        return match ((int) ($properties['Var'] ?? 0)) {
            1 => 'low',
            2 => 'medium',
            3 => 'high',
            default => null,
        };
    }

    private function isValidGeometry(string $geojson): bool
    {
        $result = DB::selectOne(
            'SELECT ST_IsValid(ST_Multi(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326))) AS valid',
            [$geojson]
        );

        return (bool) ($result->valid ?? false);
    }

    private function insertZone(string $class, string $geojson, int $returnPeriod): void
    {
        DB::insert(
            'INSERT INTO flood_hazard_zones (hazard_class, return_period_years, polygon, created_at, updated_at) VALUES (?, ?, ST_Multi(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)), NOW(), NOW())',
            [$class, $returnPeriod, $geojson]
        );
    }
}
