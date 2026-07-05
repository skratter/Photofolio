# Deployment (Shared Hosting / all-inkl)

## Cron-per-HTTP

Viele Shared-Hosting-Panels (z. B. all-inkl KAS) erlauben nur **URL-Cronjobs** – es gibt keinen
`crontab`/SSH-Zugriff, um `php artisan schedule:run` direkt auszuführen. Dafür gibt es den
Endpunkt `GET /cron/schedule-run` (`App\Http\Controllers\ScheduleRunController`), der per
HTTP-Basic-Auth abgesichert ist und direkt `queue:work --stop-when-empty` aufruft (kein Umweg über
`schedule:run`/Subprozess-Spawning, da manche Hoster `proc_open`/`exec` für den Webserver-Prozess
deaktivieren).

### Einrichtung

1. In der `.env` auf dem Server `CRON_USER` und `CRON_PASSWORD` mit zufälligen, starken Werten
   setzen, z. B.:
   ```bash
   php artisan tinker --execute 'echo Str::random(32);'
   ```
   (zweimal ausführen, einmal für User, einmal für Passwort).
2. Im Hosting-Panel einen Cronjob anlegen:
   - **Protokoll/Pfad**: `https://` + `<domain>/cron/schedule-run`
   - **Intervall**: minütlich
   - **HTTP Benutzer / HTTP Passwort**: die Werte aus Schritt 1
3. Falls Config gecacht ist (`php artisan config:cache`), nach dem Setzen der Env-Variablen
   `php artisan config:clear` ausführen, damit die neuen Werte greifen.

Test von Hand:

```bash
curl -i -u "CRON_USER:CRON_PASSWORD" https://<domain>/cron/schedule-run
```

Erwartet: `HTTP/1.1 200` mit Body `OK`.

## PHP-Version

`composer.json` verlangt `^8.5` und `config.platform.php` ist auf `8.5.0` fixiert. Das ist bewusst
so eng gehalten: Wird `composer update` auf einer Maschine mit einer neueren PHP-Version als der
deklarierten Mindestanforderung ausgeführt, löst Composer Pakete gegen die *lokal installierte*
PHP-Version auf – nicht gegen `composer.json`. Ohne das Platform-Pinning kann das `composer.lock`
dadurch Pakete enthalten, die auf dem tatsächlichen Ziel-PHP (Server oder CI) gar nicht laufen.
Das ist genau so einmal passiert (Symfony-Pakete verlangten PHP ≥ 8.4.1, obwohl `composer.json`
noch `^8.3` deklarierte) und hat die CI zum Scheitern gebracht.
