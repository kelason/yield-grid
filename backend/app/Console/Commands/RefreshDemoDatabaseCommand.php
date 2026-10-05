<?php

declare(strict_types=1);

namespace App\Console\Commands;

use DateTimeInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

final class RefreshDemoDatabaseCommand extends Command
{
    protected $signature = 'demo:reset-database';

    protected $description = 'Back up, then wipe and reseed the demo database (nightly cron)';

    private const BACKUP_KEEP_DAYS = 7;

    private const SECONDS_PER_DAY = 86400;

    /** @var list<string> */
    private const GENERATED_DIRS = ['enrollment-packs', 'credit-reports'];

    public function handle(): int
    {
        if (! (bool) config('app.demo_reset_enabled')) {
            $this->error('Refusing to wipe: set DEMO_NIGHTLY_RESET=true to enable the demo reset.');

            return self::FAILURE;
        }

        $dir = storage_path('app/backups');

        try {
            $backup = $this->backupDatabase($dir);
        } catch (RuntimeException $e) {
            $this->error("Backup failed, aborting wipe: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Backup saved: {$backup}");
        $this->info("Pruned {$this->pruneBackups($dir, self::BACKUP_KEEP_DAYS)} old backup(s).");
        $this->clearGeneratedFiles();

        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $this->info('Demo database wiped and reseeded.');

        return self::SUCCESS;
    }

    public function backupFilename(DateTimeInterface $at): string
    {
        return 'yieldgrid-'.$at->format('Ymd-His').'.dump';
    }

    public function pruneBackups(string $dir, int $keepDays): int
    {
        if (! is_dir($dir)) {
            return 0;
        }

        $cutoff = time() - $keepDays * self::SECONDS_PER_DAY;
        $deleted = 0;

        foreach (glob($dir.'/*.dump') ?: [] as $file) {
            if (filemtime($file) < $cutoff && unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * @throws RuntimeException when pg_dump fails, so the wipe never runs unbacked.
     */
    private function backupDatabase(string $dir): string
    {
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir.'/'.$this->backupFilename(now());
        $pgsql = config('database.connections.pgsql');

        $process = new Process([
            'pg_dump',
            '--host='.(string) $pgsql['host'],
            '--port='.(string) $pgsql['port'],
            '--username='.(string) $pgsql['username'],
            '--dbname='.(string) $pgsql['database'],
            '--format=custom',
            '--file='.$path,
        ], null, ['PGPASSWORD' => (string) $pgsql['password']]);

        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput()) ?: 'pg_dump exited unsuccessfully');
        }

        return $path;
    }

    private function clearGeneratedFiles(): void
    {
        foreach (self::GENERATED_DIRS as $dir) {
            Storage::disk('local')->deleteDirectory($dir);
        }
    }
}
