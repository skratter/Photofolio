# Backlog:
Themen die zu erledigen sind,

- Folderstruktur an Alben orientieren
-- Album -> Photos vs. Photos

- Cron einrichten, der JOBs auf der Webseite abarbeitet. (Laravel-seitig erledigt, siehe routes/console.php; auf dem Webspace muss noch ein Cronjob `* * * * * cd /pfad-zum-projekt && php artisan schedule:run >> /dev/null 2>&1` eingerichtet werden.)
- npm run prod nach Git Pull
