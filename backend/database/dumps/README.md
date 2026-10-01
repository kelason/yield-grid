# Database snapshots

Two snapshot files preserve the local dev database. Migrations stay the
source of truth for schema; these files preserve DATA.

- `dumps/yieldgrid_current_state.sql` — full dump (schema + data). Exact
  restore with `psql`. This is the primary save.
- `seeders/backup.sql` — data-only `INSERT` rows, loaded automatically by
  `DatabaseSeeder`. Keeps `php artisan migrate:fresh --seed` working.

## 1. Refresh the snapshots

Run from the repo root with docker running:

```bash
# Full dump (primary save — restores everything exactly)
docker compose exec -T -e PGPASSWORD=secret postgres pg_dump -U yieldgrid -d yieldgrid \
  --no-owner \
  --exclude-table-data=public.cache \
  --exclude-table-data=public.cache_locks \
  --exclude-table-data=public.sessions \
  --exclude-table-data=public.jobs \
  --exclude-table-data=public.job_batches \
  --exclude-table-data=public.failed_jobs \
  --exclude-table-data=public.personal_access_tokens \
  > backend/database/dumps/yieldgrid_current_state.sql

# Seed dump (data for the migrate:fresh --seed flow)
docker compose exec -T -e PGPASSWORD=secret postgres pg_dump -U yieldgrid -d yieldgrid \
  --data-only --inserts --schema=public \
  --exclude-table=public.cache \
  --exclude-table=public.cache_locks \
  --exclude-table=public.sessions \
  --exclude-table=public.jobs \
  --exclude-table=public.job_batches \
  --exclude-table=public.failed_jobs \
  --exclude-table=public.personal_access_tokens \
  --exclude-table=public.migrations \
  > backend/database/seeders/backup.sql
```

Notes:

- `--inserts` is required for `backup.sql`: `DatabaseSeeder` runs it via
  `DB::unprepared()`, which cannot execute `COPY ... FROM stdin`.
- `public.migrations` must stay excluded from `backup.sql`: migrations
  already record themselves when they run, so restoring those rows would
  cause primary-key conflicts during seeding.
- The seed dump prints a circular-foreign-key warning for
  `forum_threads` / `forum_replies`. It restores fine as long as no reply
  is referenced before it is inserted (true today); the full dump is
  immune to this since it applies constraints after loading data.
- Volatile tables (cache, sessions, jobs, access tokens) are excluded: they
  regenerate on their own, and tokens/sessions are credential-adjacent, so
  they stay out of git. After a restore, log in again with the same
  passwords (the `users` table is preserved).
- Commit the refreshed files so the snapshot survives with the repo.

## 2. Restore from scratch (database deleted)

```bash
# From the repo root
docker compose up -d postgres
docker compose exec -T -e PGPASSWORD=secret postgres psql -U yieldgrid -d postgres \
  -c "DROP DATABASE IF EXISTS yieldgrid WITH (FORCE);" -c "CREATE DATABASE yieldgrid;"
docker compose exec -T -e PGPASSWORD=secret postgres psql -U yieldgrid -d yieldgrid \
  < backend/database/dumps/yieldgrid_current_state.sql

# Apply any migrations newer than the snapshot
cd backend && php artisan migrate
```

Alternative — rebuild the schema purely from migrations, then seed the data:

```bash
cd backend && php artisan migrate:fresh --seed
```

Both paths need the database container running and `backend/.env`
pointing at it (`DB_HOST=127.0.0.1`, `DB_DATABASE=yieldgrid`).
