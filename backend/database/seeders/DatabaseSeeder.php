<?php

namespace Database\Seeders;

use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Farming\Models\RestrictedZone;
use Domain\Users\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $path = database_path('seeders/backup.sql');
        if (file_exists($path)) {
            DB::unprepared(file_get_contents($path));
        } else {
            // Fallback to original seeder logic if backup is missing
            $user = User::firstOrCreate(
                ['email' => 'farmer@example.com'],
                [
                    'name' => 'Demo Farmer',
                    'password' => Hash::make('password'),
                    'role' => 'farmer',
                ]
            );

            $farm = Farm::firstOrCreate(
                ['user_id' => $user->id, 'name' => 'Green Valley Farm'],
                [
                    'address' => 'Central Valley',
                ]
            );

            $wkt = 'POLYGON((-120.5 36.5, -120.48 36.5, -120.48 36.52, -120.5 36.52, -120.5 36.5))';

            Plot::firstOrCreate(
                ['farm_id' => $farm->id, 'name' => 'North Field'],
                [
                    'polygon' => DB::raw("ST_GeomFromText('{$wkt}', 4326)"),
                    'soil_type' => 'loamy',
                    'calculated_area' => 12.5,
                ]
            );

            $houseWkt = 'POLYGON((-120.485 36.505, -120.483 36.505, -120.483 36.507, -120.485 36.507, -120.485 36.505))';
            $riverWkt = 'POLYGON((-120.495 36.495, -120.490 36.490, -120.488 36.492, -120.493 36.497, -120.495 36.495))';

            RestrictedZone::firstOrCreate(
                ['name' => 'Farm House'],
                [
                    'type' => 'house',
                    'polygon' => DB::raw("ST_GeomFromText('{$houseWkt}', 4326)"),
                ]
            );

            RestrictedZone::firstOrCreate(
                ['name' => 'Local River'],
                [
                    'type' => 'river',
                    'polygon' => DB::raw("ST_GeomFromText('{$riverWkt}', 4326)"),
                ]
            );
        }
    }
}
