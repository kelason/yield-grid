# Database snapshots — LOCAL ONLY, NEVER COMMIT

Real database dumps must never be committed to this repository. A committed
dump exposed demo-user password hashes, email addresses, and GPS coordinates
in this public repo; the files were removed and git history was purged.

Rules:

- Keep any `pg_dump` output outside git (it is gitignored: `database/dumps/*.sql`,
  `database/seeders/backup.sql`).
- Seed demo data through seeders only (`DemoUsersSeeder`, `DatabaseSeeder`).
  Demo accounts: farmer / buyer / admin demo users — never real accounts.
- If you need a local snapshot, dump it locally, restore locally, delete it.
  Exclude credential-adjacent tables (`personal_access_tokens`, `sessions`,
  `cache`, `jobs`) even locally.
- `database/seeders/price_snapshot.sql` (crop price data only, no user data)
  is the one committed snapshot that may stay.

Restore from scratch (schema from migrations, demo data from seeders):

```bash
docker compose up -d postgres
cd backend && php artisan migrate:fresh --seed
```
