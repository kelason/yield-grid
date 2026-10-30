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
        // Real database dumps must never be committed (they contained user
        // password hashes, emails, and GPS coordinates). Seed demo data only.
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

        // Restore the synced price snapshot so fresh+seed keeps DA/AI prices.
        // Regenerate after a good sync with (the grep strips psql-only
        // meta-commands like \restrict that PDO cannot execute):
        // PGPASSWORD=secret pg_dump -h 127.0.0.1 -U yieldgrid -d yieldgrid \
        //   --data-only --inserts -t public.crop_reference_prices \
        //   -t public.crop_price_sync_runs -t public.crop_price_aliases \
        //   | grep -v '^\\' > database/seeders/price_snapshot.sql
        $snapshotPath = database_path('seeders/price_snapshot.sql');

        if (file_exists($snapshotPath)) {
            DB::unprepared(file_get_contents($snapshotPath));
            DB::statement('SET search_path TO public');
        }

        $this->call(CropPriceAliasSeeder::class);
        $this->call(InsuranceOfficeSeeder::class);
        $this->call(DemoUsersSeeder::class);
        $this->call(ForumThreadSeeder::class);

        // migrate:fresh drops tables, wiping GRANTs — restore the monitor role last.
        $this->call(GrafanaReadGrantSeeder::class);
    }
}
