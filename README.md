# Backlog:
Themen die zu erledigen sind,

- Folderstruktur an Alben orientieren
-- Album -> Photos vs. Photos

- Cron einrichten, der JOBs auf der Webseite abarbeitet. (Laravel-seitig erledigt, siehe routes/console.php + app/Http/Controllers/ScheduleRunController.php. all-inkl KAS erlaubt nur URL-Cronjobs, kein Shell-Kommando: im KAS-Panel unter "Cronjob anlegen" minütlich `https://skratter.com/cron/schedule-run` aufrufen lassen, mit HTTP-Benutzer/Passwort = CRON_USER/CRON_PASSWORD aus der .env.)
- npm run prod nach Git Pull
