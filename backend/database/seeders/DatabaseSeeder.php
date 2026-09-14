<?php

namespace Database\Seeders;

use Domain\Users\Models\User;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
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

        $wkt = "POLYGON((-120.5 36.5, -120.48 36.5, -120.48 36.52, -120.5 36.52, -120.5 36.5))";

        Plot::firstOrCreate(
            ['farm_id' => $farm->id, 'name' => 'North Field'],
            [
                'polygon' => \Illuminate\Support\Facades\DB::raw("ST_GeomFromText('{$wkt}', 4326)"),
                'soil_type' => 'loamy',
                'calculated_area' => 12.5,
            ]
        );
    }
}
