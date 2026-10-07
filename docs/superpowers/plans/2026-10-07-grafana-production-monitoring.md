# Grafana Production Monitoring Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Run same-box Grafana + Prometheus on the prod Micro with 3 dashboards and 8 email alerts, behind Cloudflare Access.

**Architecture:** All new processes on the prod OCI Micro, bound to localhost; nginx proxies only Grafana to `grafana.yieldgridph.com`; Cloudflare Access gates the hostname with email OTP; Prometheus scrapes node/redis/blackbox exporters on a 30s interval with 7-day capped retention; Grafana reads business data via a SELECT-only Postgres user.

**Tech Stack:** Grafana OSS, Prometheus, node_exporter, redis_exporter, blackbox_exporter, nginx, Cloudflare Access, Laravel 12 scheduler heartbeat.

**Spec:** `docs/superpowers/specs/2026-10-07-grafana-production-monitoring-design.md`

## Global Constraints

- All monitoring on the prod Micro; no new instance.
- Localhost-only ports: Grafana 3000, Prometheus 9090, node 9100, redis 9121, blackbox 9115.
- Prometheus scrape interval 30s; retention `--storage.tsdb.retention.time=7d --storage.tsdb.retention.size=2GB`.
- systemd `MemoryMax`: Grafana 350M, Prometheus 250M; 1GB swap file.
- Pre-flight gate: `free -h` shows >= 350MB available or STOP.
- Hostname `grafana.yieldgridph.com` via nginx proxy with websocket upgrade headers; Cloudflare proxied + Access email-OTP allow for owner email(s).
- Postgres user `grafana_ro`, SELECT-only, `statement_timeout = '10s'`.
- Dashboard refresh >= 1m; SQL time-bounded on indexed columns.
- 8 launch alerts with the exact thresholds in Task 6; live test alert mandatory.
- No new secrets in the repo; versions recorded at install time.

## Review Focus

- Prod traffic peak plus monitoring load OOMs the box: expect the monitors to die first (MemoryMax) while php-fpm survives; burn-in RAM stays < 85% and `dmesg` shows no php-fpm kills (Task 6 adds the check).
- An ad-hoc dashboard query runs away on prod Postgres: expect `grafana_ro` queries to cancel after 10s (Task 4 adds the `pg_sleep` test).
- Alert emails land in spam or never arrive: expect a test alert in the inbox within 5 minutes (Task 6 adds the check).
- A bad Access policy locks the owner out of Grafana: expect break-glass access via `ssh -L 3000:127.0.0.1:3000` tunnel documented and tested (Task 3 adds the check).
- TSDB/WAL growth exceeds the cap on a small disk: expect `du` on the data dir <= 2.5GB after burn-in (Task 6 adds the check).

---

### Task 1: Pre-flight and Cloudflare wiring

**Files:**
- Repo: none (OCI console + Cloudflare dashboard + SSH only)

**Interfaces:**
- Consumes: prod SSH access, Cloudflare account for `yieldgridph.com`.
- Produces: `BASE_RAM_MB` + `BASE_DISK_PCT` baselines (recorded in task notes); `grafana.yieldgridph.com` resolving to the server IP with an Access policy attached.

- [ ] **Step 1: Take an OCI boot-volume backup of the prod instance**

  In OCI console: prod instance → Boot volume → Create backup. Expected: backup state `Available`.

- [ ] **Step 2: Record baselines and enforce the gate**

  Run: `free -m; df -h /; uptime`
  Expected: numbers recorded; `available` column >= 350MB or STOP the whole plan here.

- [ ] **Step 3: Create Cloudflare DNS record**

  DNS → Add: `A grafana → <prod server IP>`, proxied ON.
  Run: `dig +short grafana.yieldgridph.com`
  Expected: returns the server IP.

- [ ] **Step 4: Create Cloudflare Access application**

  Zero Trust → Access → Applications → Add: hostname `grafana.yieldgridph.com`, policy Allow for owner email(s), login method one-time PIN.
  Expected: visiting the hostname shows the OTP screen (origin not yet live, so accept the OTP screen itself as pass).

### Task 2: Swap, exporters, Prometheus

**Files:**
- Server create: `/swapfile`, `/usr/local/bin/{node_exporter,redis_exporter,blackbox_exporter,prometheus}`, `/etc/systemd/system/{node_exporter,redis_exporter,blackbox_exporter,prometheus}.service`, `/etc/prometheus/prometheus.yml`, `/etc/prometheus/blackbox.yml`

**Interfaces:**
- Consumes: `REDIS_PASSWORD` from `/var/www/yieldgrid/backend/.env` when the server actually requires auth (prod Redis is nopass — verify with bare `redis-cli PING` first; a `.env` password the server doesn't know breaks the exporter); Task 1 baselines.
- Produces: `http://127.0.0.1:9090/api/v1/targets` shows all 3 targets `up`; installed versions recorded.

- [ ] **Step 1: Create and enable 1GB swap**

  Run: `fallocate -l 1G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile && echo '/swapfile none swap sw 0 0' >> /etc/fstab`
  Run: `free -h`
  Expected: swap row shows 1.0G.

- [ ] **Step 2: Install the 3 exporters (latest stable; record exact versions)**

  Download linux-amd64 binaries to `/usr/local/bin`, create one systemd unit each. `redis_exporter` gets `--check-keys=laravel-database-queues:default` (key name verified on prod via MONITOR) and `REDIS_PASSWORD` from the backend `.env` only when the server requires auth (prod is nopass — omit the env file in that case). `blackbox_exporter` gets a `blackbox.yml` with an `http_2xx` module.
  Run: `systemctl enable --now node_exporter redis_exporter blackbox_exporter`
  Expected: all three `active (running)`.

- [ ] **Step 3: Confirm the exact Redis queue key name**

  Run: `redis-cli LLEN laravel-database-queues:default` (add `-a "$REDIS_PASSWORD"` only when the server requires auth; note bare `LLEN` of a missing key returns 0, so confirm the key exists via MONITOR or `KEYS *queues:default*` first)
  Expected: integer reply — confirms `laravel-database-queues:default` before Task 6 relies on it. Record the real name when different.

- [ ] **Step 4: Install Prometheus (latest stable; record version) with retention caps**

  `prometheus.yml` scrapes `127.0.0.1:9100`, `:9121`, `:9115` every 30s, plus blackbox probes of `https://yieldgridph.com/` and `https://yieldgridph.com/up`. Unit flags: `--storage.tsdb.retention.time=7d --storage.tsdb.retention.size=2GB`, `MemoryMax=250M`.
  Run: `systemctl enable --now prometheus && sleep 5 && curl -s http://127.0.0.1:9090/api/v1/targets | grep -c '"health":"up"'`
  Expected: `3`.
  Run: `systemctl show prometheus -p MemoryMax`
  Expected: `MemoryMax=262144000`.

### Task 3: Grafana and nginx

**Files:**
- Server create: `/etc/grafana/grafana.ini` (`http_addr = 127.0.0.1`, `http_port = 3000`), `/etc/nginx/sites-available/grafana` + `sites-enabled` symlink

**Interfaces:**
- Consumes: Task 1 hostname + Access policy; Task 2 Prometheus on `:9090`.
- Produces: `https://grafana.yieldgridph.com` login reachable only after OTP; Grafana admin password (generated, server-side only).

- [ ] **Step 1: Install Grafana OSS (apt repo, latest stable; record version)**

  Configure `http_addr = 127.0.0.1`, anonymous access off, registration off; systemd override `MemoryMax=350M`; generate and store the admin password server-side only.
  Run: `systemctl enable --now grafana-server && sleep 5 && curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:3000/login`
  Expected: `200`.
  Run: `systemctl show grafana-server -p MemoryMax`
  Expected: `MemoryMax=367001600`.

- [ ] **Step 2: Add the nginx server block and reload**

  Block proxies `grafana.yieldgridph.com` to `http://127.0.0.1:3000` with `proxy_set_header Upgrade $http_upgrade;` + `proxy_set_header Connection "upgrade";` (Grafana Live). Existing site block untouched.
  Run: `nginx -t && systemctl reload nginx`
  Expected: `syntax is ok`, `test is successful`.

- [ ] **Step 3: Verify OTP login and localhost-only binding**

  Manual: log in through `https://grafana.yieldgridph.com` after OTP. Expected: Grafana home loads.
  Run (from outside the server): `curl --max-time 5 -o /dev/null -w '%{http_code}' http://<server-ip>:3000/login`
  Expected: timeout or `000` (refused) — never `200`.

- [ ] **Step 4: Document and test break-glass tunnel**

  Run locally: `ssh -L 3000:127.0.0.1:3000 <prod>`, then open `http://localhost:3000/login`. Expected: login page loads. Record the command in the task notes.

### Task 4: Read-only DB user and datasources

**Files:**
- Repo: none (one-time SQL; record statements in task notes)

**Interfaces:**
- Consumes: prod `DB_DATABASE` name from `/var/www/yieldgrid/backend/.env`; Tasks 2–3 services.
- Produces: `grafana_ro` user (SELECT-only, 10s statement timeout); Grafana Prometheus + Postgres datasources green.

- [ ] **Step 1: Confirm the prod database name**

  Run: `grep -E '^DB_(DATABASE|USERNAME)=' /var/www/yieldgrid/backend/.env`
  Expected: values recorded; substitute for `yieldgrid` below when different.

- [ ] **Step 2: Create `grafana_ro` with SELECT-only grants**

  ```sql
  CREATE USER grafana_ro WITH PASSWORD '<generated, server-side only>';
  GRANT CONNECT ON DATABASE yieldgrid TO grafana_ro;
  GRANT USAGE ON SCHEMA public TO grafana_ro;
  GRANT SELECT ON forward_contracts, crop_demands, crop_demand_offers,
    purchases, users, credit_score_snapshots, failed_jobs, jobs TO grafana_ro;
  ALTER USER grafana_ro SET statement_timeout = '10s';
  ```
  (`scheduler_heartbeats` grant lands in Task 5 after its migration ships.)
  Run as `grafana_ro`: `SELECT COUNT(*) FROM purchases;`
  Expected: a count. Run: `CREATE TABLE grafana_ro_probe(id int);`
  Expected: `permission denied`.

- [ ] **Step 3: Verify the 10s statement timeout**

  Run as `grafana_ro`: `SELECT pg_sleep(11);`
  Expected: `canceling statement due to statement timeout` — never a completed `11`.

- [ ] **Step 4: Add both Grafana datasources**

  Prometheus URL `http://127.0.0.1:9090`; Postgres host `127.0.0.1:5432`, database from Step 1, user `grafana_ro`, SSL mode `disable`.
  Expected: both "Save & test" return green.

### Task 5: Scheduler heartbeat (repo change, TDD)

**Files:**
- Create: `backend/database/migrations/*_create_scheduler_heartbeats_table.php` (via `php artisan make:migration`), `backend/tests/Feature/SchedulerHeartbeatTest.php` (same style as `backend/tests/Feature/ContactTest.php`)
- Modify: `backend/routes/console.php` (append after line 15)

**Interfaces:**
- Consumes: existing `Schedule` calls in `backend/routes/console.php`.
- Produces: `scheduler_heartbeats(id=1, beat_at)` upserted every 5 minutes; Task 6 queries `MAX(beat_at)`.

- [ ] **Step 1: Write the failing Pest test**

  ```php
  it('registers a five-minute scheduler heartbeat', function () {
      $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events());
      expect($events->contains(fn ($e) => $e->expression === '*/5 * * * *'))->toBeTrue();
  });

  it('upserts the heartbeat row', function () {
      DB::table('scheduler_heartbeats')->updateOrInsert(['id' => 1], ['beat_at' => now()]);
      expect(DB::table('scheduler_heartbeats')->find(1)->beat_at)->not->toBeNull();
  });
  ```

- [ ] **Step 2: Run it to verify it fails**

  Run: `php artisan test --filter=SchedulerHeartbeatTest` (from `backend/`)
  Expected: FAIL — `scheduler_heartbeats` table missing.

- [ ] **Step 3: Add the migration and the scheduled closure**

  Migration `Schema::create('scheduler_heartbeats')` with `$table->id(); $table->timestamp('beat_at');` plus a `down()` dropping the table. Append to `backend/routes/console.php`, matching existing style:
  `Schedule::call(fn () => DB::table('scheduler_heartbeats')->updateOrInsert(['id' => 1], ['beat_at' => now()]))->everyFiveMinutes();`
  (Closure <= 30 lines per project rules; no new Action for a one-line upsert.)

- [ ] **Step 4: Run the tests and the repo gates**

  Run: `php artisan test --filter=SchedulerHeartbeatTest`, then `./vendor/bin/pint --test`, `./vendor/bin/phpstan analyse --level=6` (from `backend/`)
  Expected: all PASS.

- [ ] **Step 5: Commit**

  ```bash
  git add backend/database/migrations/*_create_scheduler_heartbeats_table.php backend/routes/console.php backend/tests/Feature/SchedulerHeartbeatTest.php
  git commit -m "feat: add scheduler heartbeat for monitoring"
  ```

- [ ] **Step 6: Deploy via the normal pipeline, grant, verify freshness**

  Merge through CI; after deploy run: `GRANT SELECT ON scheduler_heartbeats TO grafana_ro;`
  Run: `SELECT EXTRACT(EPOCH FROM (now() - MAX(beat_at))) FROM scheduler_heartbeats;`
  Expected: value < 360 (under 6 minutes).

### Task 6: Dashboards, alerts, burn-in

**Files:**
- Server: dashboard JSON exports to `/var/lib/grafana/dashboards/{server,jobs,business}.json` (build in UI, then export as backup)

**Interfaces:**
- Consumes: Task 4 datasources; Task 5 heartbeat table.
- Produces: 3 dashboards (refresh >= 1m); 8 alert rules firing to email; 48h burn-in sign-off.

- [ ] **Step 1: Build the "Server" dashboard (Prometheus)**

  Panels: CPU `1 - avg(rate(node_cpu_seconds_total{mode="idle"}[5m]))`; RAM `1 - node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes`; disk `1 - node_filesystem_avail_bytes{mountpoint="/"} / node_filesystem_size_bytes{mountpoint="/"}`; load `node_load1`; service `node_systemd_unit_state{name=~"php8.3-fpm.service|supervisor.service",state="active"}` (enable the `systemd` collector on node_exporter if the metric is absent; unit verified on prod — it is `supervisor.service`, not `supervisord.service`).
  Expected: all panels render current data.

- [ ] **Step 2: Build the "Jobs & App" dashboard**

  Queue depth: `redis_key_size{key="laravel-database-queues:default"}` (confirm metric name against `/api/v1/query?query={__name__=~"redis_key.*"}` output first). Probes: `probe_success{job="probe-site"}`, `probe_duration_seconds`, `(probe_ssl_earliest_cert_expiry - time()) / 86400`. Postgres panels: `SELECT COUNT(*) FROM failed_jobs WHERE failed_at > now() - INTERVAL '1 hour'` and `SELECT EXTRACT(EPOCH FROM (now() - MAX(beat_at))) FROM scheduler_heartbeats`.
  Expected: all panels render; heartbeat age < 900.

- [ ] **Step 3: Build the "Business" dashboard (Postgres, refresh >= 5m)**

  ```sql
  -- contracts by status (active pulse)
  SELECT status, COUNT(*) FROM forward_contracts
  WHERE expiry_date >= CURRENT_DATE - INTERVAL '30 days' GROUP BY status;
  -- expiring within 7 days
  SELECT COUNT(*) FROM forward_contracts WHERE expiry_date BETWEEN CURRENT_DATE AND CURRENT_DATE + 7
  AND status IN ('available','reserved','partially_paid');
  -- open demands
  SELECT COUNT(*) FROM crop_demands WHERE status = 'open';
  -- payment success, trailing 7 days
  SELECT payment_status, COUNT(*), SUM(amount_paid) FROM purchases
  WHERE purchased_at > now() - INTERVAL '7 days' GROUP BY payment_status;
  -- new users per week, trailing 8 weeks
  SELECT date_trunc('week', created_at) wk, COUNT(*) FROM users
  WHERE created_at > now() - INTERVAL '8 weeks' GROUP BY 1 ORDER BY 1;
  -- score tiers
  SELECT tier, COUNT(*) FROM credit_score_snapshots GROUP BY tier;
  ```
  Expected: every panel returns rows without errors.

- [ ] **Step 4: Create the 8 alert rules (all to the email contact point)**

  1. RAM: `1 - avg_over_time(node_memory_MemAvailable_bytes[5m]) / node_memory_MemTotal_bytes > 0.9` for 5m. 2. Disk: `1 - node_filesystem_avail_bytes{mountpoint="/"} / node_filesystem_size_bytes{mountpoint="/"} > 0.8`. 3. Service: `node_systemd_unit_state{name=~"php8.3-fpm.service|supervisor.service",state="active"} == 0` on either watched unit for 2m (scheduler/queue deaths are also caught by rules 4 and 6). 4. Queue: `redis_key_size{key="laravel-database-queues:default"} > 100` for 10m. 5. Failed jobs: Postgres count query from Step 2 `> 0` over a 1h window. 6. Heartbeat: Postgres age query from Step 2 `> 900`. 7. Probe: `probe_success == 0` for 2m; SSL: `(probe_ssl_earliest_cert_expiry - time()) / 86400 < 14`. Each rule carries a one-line "check this first" annotation.
  Expected: all 8 listed `Normal` (or `Pending`) in Grafana alerting.

- [ ] **Step 5: Fire a test alert and confirm inbox delivery within 5 minutes**

  Temporarily set rule 1 threshold to `> 0.01` (or use Grafana "Test rule"), wait for the email, then restore `> 0.9`.
  Expected: email received <= 5 min; threshold restored (re-open rule 1 and read `0.9`).

- [ ] **Step 6: 48h burn-in and sign-off**

  Run after 48h: review RAM panel peak < 85%; `dmesg | grep -i 'out of memory'` shows no php-fpm/postgres kills; `du -sh /var/lib/prometheus` <= 2.5GB; all dashboards still populated.
  Expected: all four hold. If RAM is persistently > 85%, stop: disable Grafana/Prometheus units and revisit Approach B from the spec.
