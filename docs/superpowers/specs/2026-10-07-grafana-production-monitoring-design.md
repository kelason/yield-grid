# Grafana Production Monitoring — Design

Date: 2026-10-07
Status: approved design (implementation plan not yet written)
Approach: A — same-box minimal Grafana + Prometheus

## 1. Intent

Give YieldGrid production visibility it lacks today: server health,
queue/scheduler job health, and business metrics on one Grafana at
`grafana.yieldgridph.com`, with email alerts before users notice problems.

Success criteria:

- Grafana reachable only via Cloudflare Access (email OTP); no public login.
- Three dashboards populated: Server, Jobs & App, Business.
- Eight launch alert rules firing to email, verified with a live test alert
  (payment-spike rule follows after one week of baseline data).
- 48h burn-in with prod RAM staying under 85% and no OOM kills.

## 2. Constraints

- Budget ~$0 (OCI Always Free only); no paid services.
- Prod is one OCI `VM.Standard.E2.1.Micro` (1 OCPU x86, 1GB RAM) in
  ap-singapore-1, running php-fpm, Postgres/PostGIS, Redis, Reverb,
  queue worker, and scheduler. New instances are capacity-blocked.
- DNS on Cloudflare (`yieldgridph.com`); subdomains free with Universal SSL.
- Monitoring RAM budget: ~250–350MB total for all new processes.

## 3. Decisions

| # | Decision | Choice |
|---|----------|--------|
| 1 | Host | Same prod box (no new instance) |
| 2 | V1 scope | Server + jobs + business metrics (no log aggregation) |
| 3 | Alert channel | Email via Grafana contact point reusing prod SMTP settings |
| 4 | Access | Cloudflare Access, allow = owner email(s), one-time PIN |
| 5 | Stack | Grafana OSS + Prometheus + node/redis/blackbox exporters |

Rejected: Grafana Cloud remote (no custom subdomain on free tier, data
leaves VPS); Laravel Pulse now (no OS metrics, which is the biggest risk).

## 4. Architecture

```
Cloudflare: A grafana -> <server IP>, proxied + Access (email OTP)
  -> nginx:443 grafana.yieldgridph.com -> 127.0.0.1:3000 (Grafana)
  -> existing yieldgridph.com vhost untouched

On-box, all localhost-only (reachable externally only via nginx):
  Grafana :3000 --> Prometheus :9090, Postgres (grafana_ro, SELECT-only)
  Prometheus :9090 scrapes (30s interval):
    node_exporter :9100, redis_exporter :9121, blackbox_exporter :9115
  blackbox probes: https://yieldgridph.com/ and /api/v1/up + SSL expiry
```

Pre-flight gate: `free -h` must show >= 350MB available or stop before
installing anything.

Guardrails: 1GB swap file; systemd `MemoryMax` on Grafana (350M) and
Prometheus (250M) so monitors fail closed instead of starving php-fpm;
Prometheus `--storage.tsdb.retention.time=7d
--storage.tsdb.retention.size=2GB`.

## 5. Components

- Grafana OSS (apt repo, version-pinned): `:3000` localhost-only,
  generated admin password, anonymous access off, registration off,
  SMTP contact point mirroring the app's prod mail settings.
- Prometheus (binary + systemd): `:9090` localhost-only, 30s scrape,
  retention caps from section 4.
- Exporters (binaries + systemd, ~15MB each): node (CPU/RAM/disk/load,
  systemd unit states for php-fpm and supervisor jobs), redis
  (`REDIS_PASSWORD` from backend `.env` when set; queue depth), blackbox
  (site/API probes, SSL days-remaining).
- Postgres datasource: `grafana_ro` user, `SELECT` grants only on the
  tables dashboards query; localhost TCP.
- Nginx: new `grafana.yieldgridph.com` server block proxying to
  `127.0.0.1:3000` with websocket upgrade headers (Grafana Live); the
  existing site block is not modified.
- Cloudflare: `A grafana` record (proxied) + Access application with
  allow-policy for owner email(s).
- Secrets rule: no new secrets in the repo. Passwords/keys live in
  server-side files and Grafana's own database only.

## 6. Dashboards and alerts

Dashboard 1 "Server" (Prometheus): CPU, load, RAM used/available, disk
used, network, php-fpm + supervisor unit states, wesley-thompson uptime.

Dashboard 2 "Jobs & App" (Prometheus + Postgres): Redis queue depth,
`failed_jobs` counts (1h/24h), scheduler heartbeat freshness, site/API
probe status and latency, SSL days-remaining.

Dashboard 3 "Business" (Postgres read-only): active/expiring contracts,
open demands, purchases by status, payment success rate, new users/week,
credit score distribution. Queries time-bounded on indexed columns.

Alert rules (all -> email contact point, each with a one-line "check this
first" annotation):

1. RAM > 90% for 5m. 2. Disk > 80%. 3. Watched service down > 2m.
4. Queue depth > 100 for 10m. 5. New failed job within 1h.
6. Scheduler heartbeat stale > 15m. 7. Site/API probe down > 2m.
8. SSL expiry < 14 days. Rule 9 (payment-failure-spike) is added after one
   week of baseline data, threshold tuned from observed data.

## 7. Repo change (one)

Scheduler heartbeat so "scheduler silently died" is detectable: a small
migration creating a single-row `scheduler_heartbeats` table plus a
5-minute scheduled closure in `backend/routes/console.php` that upserts
the current timestamp. Grafana alerts when `MAX(beat_at)` is older than
15 minutes. Inert if Grafana is ever removed. Ships through the normal
CI/deploy pipeline with a Pest test on the closure.

## 8. Rollout order

1. Pre-flight: snapshot/backup; record `free -h` / `df -h` baselines.
2. Cloudflare: `A grafana` record + Access app/policy; verify resolution.
3. Swap + exporters + Prometheus; verify all targets `up` (via SSH tunnel).
4. Grafana + nginx block + reload; verify OTP login works and direct
   `:3000` from outside refuses.
5. `grafana_ro` user; both datasources green in Grafana.
6. Heartbeat change merged and deployed; heartbeat timestamp fresh.
7. Import dashboards, create alert rules, fire a test alert, confirm email.
8. 48h burn-in: RAM < 85%, no OOM kills in `dmesg`, panels populated.

## 9. Verification per step

- Step 3: `curl` each exporter endpoint and Prometheus `/api/v1/targets`
  shows all `up`.
- Step 4: login through the public hostname succeeds; `curl` to
  `<server-ip>:3000` from outside times out or refuses.
- Step 5: datasource "Save & test" green; a sample panel returns rows.
- Step 6: heartbeat row age under 6 minutes.
- Step 7: test alert email received within 5 minutes.
- Step 8: RAM/disk graphs reviewed; no `Out of memory` in `dmesg`.

## 10. Rollback

Stop and disable the new systemd units, remove the nginx block and
reload, delete the Cloudflare DNS record and Access app. App code is
untouched except the inert heartbeat closure. No data migration to undo.

## 11. Risks

- OOM on 1GB: mitigated by pre-flight gate, swap, MemoryMax, 30s scrape,
  7-day retention caps. Abort criterion: burn-in RAM persistently > 85%.
- Dashboard queries loading prod DB: mitigated by read-only user and
  time-bounded indexed queries; keep dashboard refresh >= 1m.
- Email alerts misconfigured: mitigated by mandatory live test alert in
  step 7 before sign-off.
- Scope creep into logs/traces: explicitly out of scope for v1.

## 12. Out of scope

Loki/log aggregation, distributed tracing, multi-host monitoring,
Grafana OnCall/IRSM workflows, business-alert thresholds beyond the
payment rule noted in section 6.
