# Photofolio

Ein selbst gehosteter Foto-Album-/Portfolio-Manager auf Basis von Laravel und Livewire – universell einsetzbar für jede eigene Fotografie-Homepage, nicht an eine bestimmte Domain gebunden.

## Was ist das?

Photofolio ist die Grundlage für eine persönliche Fotografie-Homepage. Statt auf einen fertigen Foto-Hoster zu setzen, verwaltet die Anwendung Alben und Bilder selbst: Fotos werden hochgeladen, im Hintergrund verarbeitet (Thumbnails, Web-optimierte Anzeigebilder, EXIF-Daten) und lassen sich anschließend in Alben organisieren. Alben können öffentlich oder passwortgeschützt (privat) sein.

Name und Branding der eigenen Instanz kommen aus `APP_NAME` (`.env`) – im Admin-Bereich und auf der Startseite wird nichts hartkodiert.

Besucher sehen Alben-Übersicht, Album-Ansicht (Masonry-Grid mit Lightbox/Diashow) und Foto-Detailseiten (großes Bild, EXIF-Daten) unter `/alben`, `/album/{slug}` bzw. `/album/{slug}/{photo}`. Die Startseite (`/`) zeigt wahlweise ein als Startseite markiertes Album als Masonry-Grid oder – falls keins gesetzt ist – einen Platzhalter. Private Alben sind passwortgeschützt und tauchen nicht in der öffentlichen Übersicht auf. Frei angelegte und rechtliche Seiten (Impressum, Datenschutz, etc.) haben eine öffentliche Anzeige unter `/seite/{slug}`. Header/Footer bieten Navigation, Rechtliches und einen Hell/Dunkel/System-Umschalter. Ein Beispiel für eine laufende Instanz ist [skratter.com](https://skratter.com), die private Fotografie-Homepage des Autors.

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
  - **Seiten verwalten (CMS)**: Impressum & Datenschutz als geschützte, nicht löschbare „Legal“-Seiten, dazu beliebig viele freie Seiten (Titel, Slug, Meta-Description, Entwurf/Veröffentlicht, Navigations-Sichtbarkeit, Sortierung). WYSIWYG-Editor (Trix) mit eingebetteten Bildern und separaten Datei-Downloads; eingefügter Markdown-Text (z. B. KI-generierte Entwürfe) wird beim Einfügen automatisch in echte Formatierung umgewandelt. Verwaiste Entwürfe, Anhänge und aus dem Text entfernte Bilder werden automatisch aufgeräumt
  - **Auswertung**: Aufrufzahlen pro Album/Foto/Seite/Startseite, ohne Bot-, Admin- und Do-Not-Track-Traffic, dazu Verlauf (täglich/monatlich/jährlich/gesamt) und Herkunfts-Analyse (Referrer, User-Agent, Kennzeichnung eigener Domains) – Details in [docs/analytics.md](docs/analytics.md)
- **Öffentliche Album-/Foto-Galerie**: Album-Übersicht (`/alben`), Album-Ansicht mit Masonry-Grid und Diashow-Lightbox (`/album/{slug}`), Foto-Detailseite mit EXIF (`/album/{slug}/{photo}`); private Alben zeigen ein Passwort-Formular statt der Fotos
- **Startseite** (`/`): zeigt das als Startseite markierte Album, sonst einen Platzhalter
- **Öffentliche Seiten-Anzeige** (`/seite/{slug}`): rendert veröffentlichte CMS-Seiten inkl. Downloads-Liste; automatisch in der Sitemap enthalten
- **Navigation & Footer**: Header mit Alben-Link und frei konfigurierbaren CMS-Seiten, Footer mit Impressum/Datenschutz und Hell/Dunkel/System-Umschalter (folgt automatisch der Browser-Präferenz)
- **Bildauslieferung**: eigene Routen für Thumbnail- und Anzeigevarianten (Admin- und öffentlich, mit Zugriffsprüfung für private Alben), nur für verarbeitete Fotos, mit Caching-Header
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
- **Trix** als WYSIWYG-Editor für Seiteninhalte, **marked** wandelt eingefügten Markdown-Text in Trix in echtes HTML um
- **cyrildewit/eloquent-viewable** für die eigene, Drittanbieter-freie Aufruf-Statistik
- **Tailwind CSS 4** + **Vite** für das Frontend-Build
- **Pest** für Tests, **Pint** für Code-Formatierung, **Larastan** für statische Analyse

## Installation

Voraussetzungen: PHP 8.5, Composer, Node.js, npm.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
npm install
npm run build
```

`storage:link` legt den Symlink an, über den hochgeladene Branding-Assets (Favicon, Logo) und
Seiten-Anhänge öffentlich unter `/storage/...` erreichbar sind. Ohne ihn werden Dateien zwar
weiterhin korrekt gespeichert, aber im Browser als 404 angezeigt. Auf Shared-Hosting ohne
Symlink-Unterstützung ersatzweise direkt per Shell: `ln -s ../storage/app/public public/storage`
(im `public/`-Verzeichnis ausgeführt).

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

Das Projekt befindet sich in aktiver Entwicklung. Admin-Bereich sowie öffentlicher Auftritt (Alben, Fotos, Startseite, Seiten, Navigation/Footer) sind funktionsfähig. Noch offen: Profilseite (lässt sich bereits jetzt als normale CMS-Seite anlegen) und ein Blog.
