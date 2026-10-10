# API Subdomain Cutover runbook (OCI)

Moves the Laravel API from `https://yieldgridph.com/api/...` to
`https://api.yieldgridph.com/api/...` on the same OCI box (same app, same
php-fpm pool, zero new services), then publishes the public API docs. DNS +
nginx + TLS first (~30 minutes), then one normal `main` deploy, then a
bake period before the old paths redirect. Keep this window reversible at
every step: both hosts serve the same live API until the final redirect.

## 0. Record the memory baseline

On the server, before anything changes:

```bash
free -m
# expect: note the Mem: available column; step 8 compares against it
```

## 1. Add the DNS record

Add one `A` record for `api` with the same value as the apex `A` record
and TTL 300:

```bash
dig +short yieldgridph.com
# expect: the VPS IPv4, e.g. 152.67.12.34

dig +short api.yieldgridph.com
# expect: the same IPv4 within a few minutes of adding the record
```

Keep TTL 300 until step 9 is done, then restore the normal TTL.

## 2. Add the nginx server block

Duplicate the existing apex `server { ... }` block into a new one for the
API host. Keep the php-fpm handling identical (same `fastcgi_pass` socket,
same `fastcgi_param`s); only these lines differ:

```nginx
server {
    listen 443 ssl;
    server_name api.yieldgridph.com;

    root /var/www/yieldgrid/backend/public;
    index index.php;

    # ... copy the location / php-fallback block VERBATIM from the apex
    # block's /api/ handling (try_files, fastcgi_pass, fastcgi_param
    # SCRIPT_FILENAME, include fastcgi_params) ...
}
```

Variant: if the apex block reverse-proxies `/api/` to a php
upstream/container instead of fastcgi, copy that `location` block
verbatim and change only its path prefix to `/`.

Then:

```bash
sudo nginx -t && sudo systemctl reload nginx
# expect: syntax is ok, test is successful
```

## 3. Extend TLS to the new host

```bash
sudo certbot --expand -d api.yieldgridph.com
# expect: congratulations / successfully received certificate

curl -s -o /dev/null -w '%{http_code}\n' https://api.yieldgridph.com/
# expect: 200
```

## 4. Run the parity check (old code, both hosts)

The current deploy already serves the API on both hosts (same app). From
any machine with curl:

```bash
bash scripts/api-parity-check.sh https://yieldgridph.com https://api.yieldgridph.com
# expect: four PASS lines, then: RESULT: all checks identical (exit 0)
```

A single-canary DIFF with no deploy in flight is usually a marketplace
write landing between the two fetches: re-run once. A repeatable DIFF or
any FETCH-FAIL stops the cutover — fix nginx/TLS before merging.

## 5. Set the server env, then deploy

On the server's `backend/.env` (never commit these values):

```bash
APP_URL=https://api.yieldgridph.com
DOCS_HOST=api.yieldgridph.com
# FRONTEND_URL stays https://yieldgridph.com (password resets, verify links)
```

`DOCS_HOST` feeds both the docs host-gate and the exported spec's server
URL; without it the spec falls back to the app URL. Then merge the
feature branch to `main` — CI builds the SPA against the new
`VITE_API_URL`, runs the suites, and the deploy step exports the spec
(`mkdir -p storage/app/api-docs` + `scramble:export`) after `migrate`
and before `config:cache`.

## 6. Verify the deploy

Spec freshness first (a silent export failure would ship a stale spec):

```bash
cd /var/www/yieldgrid/backend
ls -l --time-style=full-iso storage/app/api-docs/openapi.json
# expect: mtime within the deploy window

grep -c '/api/v1/market/prices/guide' storage/app/api-docs/openapi.json
# expect: 1 or more
```

Docs on the api host, absent on the apex host:

```bash
curl -s -o /dev/null -w '%{http_code}\n' https://api.yieldgridph.com/docs
# expect: 200

curl -s https://api.yieldgridph.com/docs/openapi.json | head -c 60
# expect: {"openapi":"3.1.0", ...

curl -s -o /dev/null -w '%{http_code}\n' https://yieldgridph.com/docs
# expect: 404

curl -s -o /dev/null -w '%{http_code} -> %{redirect_url}\n' https://api.yieldgridph.com/docs/
# expect: 301 -> https://api.yieldgridph.com/docs
```

Apex API still dual-serves during the bake:

```bash
bash scripts/api-parity-check.sh https://yieldgridph.com https://api.yieldgridph.com
# expect: RESULT: all checks identical (exit 0)
```

## 7. Smoke the SPA and services

In a browser on `https://yieldgridph.com`: log in, browse the
marketplace, perform one authed call (e.g. open your offers), and confirm
a live update arrives without refresh (no `wss` errors in devtools).
Reverb still attaches to the apex host — no websocket config changed.

```bash
sudo supervisorctl status yieldgrid-scheduler yieldgrid-reverb
# expect: both RUNNING

cd /var/www/yieldgrid/backend && php artisan queue:failed --json | head -c 200
# expect: [] (no new failures from the deploy)
```

## 8. Confirm the memory floor did not move

```bash
free -m
# expect: Mem: available within ~5% of the step 0 reading (no new services)
```

## 9. Bake, then redirect the old paths (one release later)

After ~1 release with no API errors attributable to the split, redirect
the apex API paths. Inside the apex `server { ... }` block, **above** the
existing `/api/` handling, add:

```nginx
location ^~ /api/ {
    return 308 https://api.yieldgridph.com$request_uri;
}
```

`308` (not `301`) preserves the method and body of POST/PUT/PATCH
clients; `$request_uri` preserves query strings. Then:

```bash
sudo nginx -t && sudo systemctl reload nginx
# expect: syntax is ok, test is successful

curl -s -o /dev/null -w '%{http_code} %{redirect_url}\n' -X POST https://yieldgridph.com/api/v1/register -H 'Content-Type: application/json' -d '{}'
# expect: 308 https://api.yieldgridph.com/api/v1/register

curl -s -o /dev/null -w '%{http_code}\n' -X POST https://api.yieldgridph.com/api/v1/register -H 'Content-Type: application/json' -d '{}'
# expect: 422 (validation ran: method and body survived the hop)
```

Restore the DNS TTL from step 1.

## 10. Watch and rollback

Watch error rate + latency in the existing prod monitoring for 24h after
each of steps 5 and 9. Rollback trigger: any 5xx spike or auth-failure
wave.

- After step 5: redeploy the previous frontend `dist/` (old
  `VITE_API_URL`). Backend, DNS, and nginx stay as-is.
- After step 9: comment out the `location ^~ /api/` block, `nginx -t &&
  reload`, then the frontend redeploy above if the SPA itself is bad.

```bash
# rollback check: apex serves the API directly again
curl -s -o /dev/null -w '%{http_code}\n' -X POST https://yieldgridph.com/api/v1/register -H 'Content-Type: application/json' -d '{}'
# expect: 422 (no redirect in the way)
```
