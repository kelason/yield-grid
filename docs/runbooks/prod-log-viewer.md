# Prod Log Viewer runbook (OCI)

Serves `/log-viewer` on production behind nginx HTTP basic auth. The app
verifies the authenticated user via `REMOTE_USER`, so both layers must be
configured. One-time setup, ~5 minutes on the OCI box.

## 1. Create the htpasswd credentials

Pick a username (e.g. `deployer`) and generate the password file:

```bash
sudo apt-get install -y apache2-utils   # provides htpasswd (once)
sudo htpasswd -c /etc/nginx/.htpasswd-logviewer deployer
sudo chmod 640 /etc/nginx/.htpasswd-logviewer
```

## 2. Gate the route in nginx

Inside the prod `server { ... }` block, **above** the generic `location /`
block, add:

```nginx
location ^~ /log-viewer {
    auth_basic "YieldGrid logs";
    auth_basic_user_file /etc/nginx/.htpasswd-logviewer;

    # ... keep your existing php handling here (same as location /) ...

    fastcgi_param REMOTE_USER $remote_user;
}
```

Variant: if nginx reverse-proxies to php/Docker instead of fastcgi, use
`proxy_set_header REMOTE_USER $remote_user;` instead of the `fastcgi_param`
line. Then:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

## 3. Match the username in Laravel

On the server's `backend/.env` (never commit the value):

```bash
LOG_VIEWER_BASIC_AUTH_USER=deployer
LOG_VIEWER_PHP_FPM_LOG=/var/log/php8.3-fpm.log
```

```bash
php artisan config:clear
```

## 4. Grant the PHP user read access to system logs

Log Viewer reads files as the php-fpm user (`www-data`). On Ubuntu,
nginx logs are group-readable by `adm`:

```bash
sudo usermod -aG adm www-data
sudo -u www-data head -1 /var/log/nginx/error.log   # must print a line
sudo -u www-data head -1 /var/log/php8.3-fpm.log    # must print a line
```

If the php-fpm log is not readable, grant it without opening permissions:

```bash
sudo setfacl -m u:www-data:r /var/log/php8.3-fpm.log
```

Then restart php-fpm so the new group applies: `sudo systemctl restart
php8.3-fpm`.

## 5. Verify

```bash
curl -sI https://<prod-host>/log-viewer | head -1
# expect: HTTP/2 401

curl -su deployer:'<password>' -o /dev/null -w '%{http_code}\n' https://<prod-host>/log-viewer
# expect: 200
```

In a browser, opening `https://<prod-host>/log-viewer` prompts for the
password, then shows the same UI as local with `root` (Laravel), `Nginx`,
and `PHP` folders. Unauthenticated API calls under `/log-viewer/api`
return 401 via the same location block.
