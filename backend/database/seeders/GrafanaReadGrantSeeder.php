<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class GrafanaReadGrantSeeder extends Seeder
{
    private const MONITOR_ROLE = 'grafana_ro';

    public function run(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        if (! $this->monitorRoleExists()) {
            return;
        }

        DB::statement('GRANT SELECT ON ALL TABLES IN SCHEMA public TO '.self::MONITOR_ROLE);
        DB::statement('ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON TABLES TO '.self::MONITOR_ROLE);
    }

    private function monitorRoleExists(): bool
    {
        return DB::selectOne('SELECT 1 FROM pg_roles WHERE rolname = ?', [self::MONITOR_ROLE]) !== null;
    }
}
