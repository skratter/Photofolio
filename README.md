# skratter.com

Eine private Fotoportfolio-/Homepage-Anwendung auf Basis von Laravel und Livewire. Das Projekt verwaltet Fotoalben inklusive Upload, automatischer Bildverarbeitung und EXIF-Auswertung über ein passwortgeschütztes Admin-Backend – die öffentliche Startseite folgt später.

## Was ist das?

skratter.com ist die Grundlage für eine persönliche Fotografie-Homepage. Statt auf einen fertigen Foto-Hoster zu setzen, verwaltet die Anwendung Alben und Bilder selbst: Fotos werden hochgeladen, im Hintergrund verarbeitet (Thumbnails, Web-optimierte Anzeigebilder, EXIF-Daten) und lassen sich anschließend in Alben organisieren. Alben können öffentlich oder passwortgeschützt (privat) sein.

Die öffentliche Startseite (`/`) ist aktuell ein Platzhalter ("soon™") mit Links zu den Social-Media-Profilen – die eigentliche Portfolio-Ansicht für Besucher ist noch nicht umgesetzt.

## Funktionsumfang

- **Admin-Bereich** (`/admin`, loginpflichtig via Laravel Fortify)
  - **Dashboard**: KPI-Kacheln (Alben, Fotos, Startseiten-Aufrufe, meistgesehenes Album/Foto mit Bildvorschau) als Einstiegspunkt
  - **Alben verwalten**: Anlegen, Bearbeiten, Löschen; Titel, Slug, Beschreibung, Sortierung, Titelbild
  - **Sichtbarkeit pro Album**: `public` oder `private` (passwortgeschützt, Passwort wird gehasht gespeichert)
  - **Foto-Upload**: Mehrfach-Upload direkt im Album (JPG/PNG/WebP, bis 20 MB je Datei)
  - **Asynchrone Bildverarbeitung** (Queue-Job `ProcessUploadedPhoto`):
    - Extraktion von EXIF-Daten (Kamera, Objektiv, Blende, Belichtungszeit, ISO, Brennweite, Aufnahmedatum, GPS-Koordinaten)
    - Generierung von Bildvarianten (Thumbnail & Anzeigegröße als WebP) via Intervention Image
  - **Fotoverwaltung im Album**: Mehrfachauswahl, Massen-Umbenennung, Einzel- und Massen-Löschung
  - **Download**: einzelnes Originalbild oder mehrere Fotos gesammelt als ZIP
  - **Auswertung**: Aufrufzahlen pro Album/Foto/Startseite, ohne Bot- und Admin-Traffic – Details in [docs/analytics.md](docs/analytics.md)
- **Bildauslieferung**: eigene Routen für Thumbnail- und Anzeigevarianten, nur für verarbeitete Fotos, mit Caching-Header
- **Cron-per-HTTP**: Endpunkt (`/cron/schedule-run`, HTTP-Basic-Auth) stößt `queue:work` an – gedacht für Hosting-Umgebungen ohne dauerhaft laufenden Worker-Prozess, Details in [docs/deployment.md](docs/deployment.md)

## Dokumentation

Ausführlichere Beschreibungen einzelner Themenbereiche liegen unter [`docs/`](docs/):

- [Alben & Fotos](docs/albums.md) – Alben, Upload/Verarbeitung, Titelbild-Auswahl, Fotoverwaltung
- [Auswertung / View-Tracking](docs/analytics.md) – wie Aufrufe gezählt werden, was ausgeschlossen ist, wo die Zahlen landen
- [Deployment](docs/deployment.md) – Cron-per-HTTP-Einrichtung, PHP-Versionsanforderung

Für Beiträge/Konventionen siehe [CONTRIBUTING.md](CONTRIBUTING.md).

## Tech-Stack

- **PHP 8.5** / **Laravel 13**
- **Livewire 4** mit **Flux UI** (Komponentenbibliothek) für reaktive Admin-Oberflächen
- **Laravel Fortify** für Authentifizierung
- **Intervention Image** für Bildverarbeitung
- **Tailwind CSS 4** + **Vite** für das Frontend-Build
- **Pest** für Tests, **Pint** für Code-Formatierung, **Larastan** für statische Analyse

## Installation

Voraussetzungen: PHP 8.5, Composer, Node.js, npm.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

Für die lokale Entwicklung mit Hot-Reload und laufendem Queue-Worker:

```bash
composer run dev
```

### Wichtige Umgebungsvariablen

| Variable | Zweck |
|---|---|
| `ADMIN_EMAIL`, `ADMIN_NAME`, `ADMIN_PASSWORD` | Admin-Zugang (Seeder/Setup) |
| `CRON_USER`, `CRON_PASSWORD` | HTTP-Basic-Auth für den `/cron/schedule-run`-Endpunkt |
| `FILESYSTEM_DISK` | Standard-Dateisystem-Disk |

## Tests

```bash
php artisan test --compact
```

## Status

Das Projekt befindet sich in aktiver Entwicklung. Der Admin-Bereich zur Album- und Fotoverwaltung ist funktionsfähig, die öffentliche Portfolio-Ansicht steht noch aus.
