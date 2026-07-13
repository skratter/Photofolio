# Photofolio

Ein selbst gehosteter Foto-Album-/Portfolio-Manager auf Basis von Laravel und Livewire – universell einsetzbar für jede eigene Fotografie-Homepage, nicht an eine bestimmte Domain gebunden.

## Was ist das?

Photofolio ist die Grundlage für eine persönliche Fotografie-Homepage. Statt auf einen fertigen Foto-Hoster zu setzen, verwaltet die Anwendung Alben und Bilder selbst: Fotos werden hochgeladen, im Hintergrund verarbeitet (Thumbnails, Web-optimierte Anzeigebilder, EXIF-Daten) und lassen sich anschließend in Alben organisieren. Alben können öffentlich oder passwortgeschützt (privat) sein.

Name, Favicon und Logo (getrennt für helles/dunkles Farbschema) der eigenen Instanz lassen sich komplett über **Admin → Einstellungen** setzen – im Admin-Bereich und auf der Startseite wird nichts hartkodiert. Details in [docs/branding.md](docs/branding.md).

Besucher sehen Alben-Übersicht, Album-Ansicht (Masonry-Grid mit Lightbox/Diashow) und Foto-Detailseiten (großes Bild, EXIF-Daten) unter `/alben`, `/album/{slug}` bzw. `/album/{slug}/{photo}`. Die Startseite (`/`) zeigt wahlweise ein als Startseite markiertes Album als Masonry-Grid oder – falls keins gesetzt ist – einen Platzhalter. Private Alben sind passwortgeschützt und tauchen nicht in der öffentlichen Übersicht auf. Frei angelegte und rechtliche Seiten (Impressum, Datenschutz, etc.) haben eine öffentliche Anzeige unter `/seite/{slug}`. Header/Footer bieten Navigation, Rechtliches und einen Hell/Dunkel/System-Umschalter. Ein Beispiel für eine laufende Instanz ist [skratter.com](https://skratter.com), die private Fotografie-Homepage des Autors.

## Funktionsumfang

- **Admin-Bereich** (`/admin`, loginpflichtig via Laravel Fortify)
  - **Dashboard**: KPI-Kacheln (Alben, Fotos, Startseiten-Aufrufe, meistgesehenes Album/Foto mit Bildvorschau) als Einstiegspunkt
  - **Alben verwalten**: Anlegen, Bearbeiten, Löschen; Titel, Slug, Beschreibung (Rich Text via Trix, siehe [docs/albums.md](docs/albums.md)), Sortierung, Titelbild
  - **Sichtbarkeit pro Album**: `public` oder `private` (passwortgeschützt, Passwort wird gehasht gespeichert)
  - **Foto-Upload**: Mehrfach-Upload direkt im Album (JPG/PNG/WebP, bis 20 MB je Datei)
  - **Asynchrone Bildverarbeitung** (Queue-Job `ProcessUploadedPhoto`):
    - Extraktion von EXIF-Daten (Kamera, Objektiv, Blende, Belichtungszeit, ISO, Brennweite, Aufnahmedatum, GPS-Koordinaten)
    - Generierung von Bildvarianten (Thumbnail & Anzeigegröße als WebP) via Intervention Image
  - **Fotoverwaltung im Album**: Mehrfachauswahl, Massen-Umbenennung, Einzel- und Massen-Löschung
  - **Download**: einzelnes Originalbild oder mehrere Fotos gesammelt als ZIP
  - **Seiten verwalten (CMS)**: Impressum & Datenschutz als geschützte, nicht löschbare „Legal“-Seiten, dazu beliebig viele freie Seiten (Titel, Slug, Meta-Description, Entwurf/Veröffentlicht, Navigations-Sichtbarkeit, Sortierung). WYSIWYG-Editor (Trix) mit eingebetteten Bildern und separaten Datei-Downloads; eingefügter Markdown-Text (z. B. KI-generierte Entwürfe) wird beim Einfügen automatisch in echte Formatierung umgewandelt. Verwaiste Entwürfe, Anhänge und aus dem Text entfernte Bilder werden automatisch aufgeräumt
  - **Einstellungen**: Seitenname, Startseiten-Meta-Description, Branding (Favicon, Logo hell/dunkel), Startseiten-/Diashow-Verhalten, Social-Media-Links, eigene Domains für die Auswertung – Details in [docs/branding.md](docs/branding.md)
  - **Auswertung**: Aufrufzahlen pro Album/Foto/Seite/Startseite, ohne Bot-, Admin- und Do-Not-Track-Traffic, dazu Verlauf (täglich/monatlich/jährlich/gesamt) und Herkunfts-Analyse (Referrer, User-Agent, Kennzeichnung eigener Domains) – Details in [docs/analytics.md](docs/analytics.md)
- **Öffentliche Album-/Foto-Galerie**: Album-Übersicht (`/alben`), Album-Ansicht mit Masonry-Grid und Diashow-Lightbox (`/album/{slug}`), Foto-Detailseite mit EXIF (`/album/{slug}/{photo}`); private Alben zeigen ein Passwort-Formular statt der Fotos
- **Öffentlicher Download**: optional pro Album aktivierbar (`downloads_enabled`) – Download einzelner Fotos oder des ganzen Albums als ZIP, mit seitenübergreifendem Auswahlmodus für eine Teilmenge; Details in [docs/albums.md](docs/albums.md)
- **Startseite** (`/`): zeigt das als Startseite markierte Album, sonst einen Platzhalter
- **Öffentliche Seiten-Anzeige** (`/seite/{slug}`): rendert veröffentlichte CMS-Seiten inkl. Downloads-Liste; automatisch in der Sitemap enthalten
- **Navigation & Footer**: Header mit Alben-Link, konfigurierbarem Logo und frei konfigurierbaren CMS-Seiten, Footer mit Impressum/Datenschutz und Hell/Dunkel/System-Umschalter (folgt automatisch der Browser-Präferenz)
- **SEO**: Meta-Description (Seiten manuell, Alben/Fotos automatisch abgeleitet, Startseite über die Einstellungen), Open-Graph-/Twitter-Card-Tags, Bild-Sitemap-Erweiterung, robots.txt – Details in [docs/seo.md](docs/seo.md)
- **Bildauslieferung**: eigene Routen für Thumbnail- und Anzeigevarianten (Admin- und öffentlich, mit Zugriffsprüfung für private Alben), nur für verarbeitete Fotos, mit Caching-Header
- **Cron-per-HTTP**: Endpunkt (`/cron/schedule-run`, HTTP-Basic-Auth) stößt `queue:work` an – gedacht für Hosting-Umgebungen ohne dauerhaft laufenden Worker-Prozess, Details in [docs/deployment.md](docs/deployment.md)

## Dokumentation

Ausführlichere Beschreibungen einzelner Themenbereiche liegen unter [`docs/`](docs/):

- [Alben & Fotos](docs/albums.md) – Alben, Upload/Verarbeitung, Titelbild-Auswahl, Fotoverwaltung, Rich-Text-Beschreibung, öffentlicher Download
- [Branding](docs/branding.md) – Favicon, Logo (hell/dunkel), Speicherung, Design-Entscheidungen
- [SEO](docs/seo.md) – Meta-Description, Open Graph/Twitter Cards, Bild-Sitemap, robots.txt
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

## Voraussetzungen

Kein reines Entpacken-und-loslegen – vorausgesetzt wird eine funktionsfähige Laravel-Umgebung:

- **PHP 8.5** mit den Standard-Extensions, die Laravel selbst ohnehin verlangt (`mbstring`, `pdo`,
  `openssl`, `tokenizer`, `xml`, `ctype`, `curl`, `fileinfo` – bei jeder halbwegs aktuellen
  PHP-Installation normalerweise schon dabei), plus drei, die konkret von Photofolio genutzt werden
  und nicht immer standardmäßig aktiv sind:
  - **`gd`** – Bildverarbeitung (Thumbnails/Anzeigegrößen) läuft über Intervention Image mit dem
    GD-Treiber (`app/Actions/GeneratePhotoVariantsAction.php`), nicht Imagick.
  - **`exif`** – EXIF-Auslese (Kamera, Blende, Aufnahmedatum, GPS, …) nutzt PHPs eigenes
    `exif_read_data()` (`app/Actions/ExtractExifDataAction.php`).
  - **`zip`** – ZIP-Downloads mehrerer Fotos laufen über `ZipArchive`
    (`app/Actions/BuildPhotoZipAction.php`).
- **Composer**, **Node.js** + **npm** für Abhängigkeiten und den Frontend-Build.
- **Eine Datenbank** (MySQL oder SQLite; `DB_*` in der `.env`) – ein `database.sqlite` reicht für
  kleine Instanzen völlig aus, es muss aber vorhanden sein, bevor migriert wird.
- **Document Root des Webservers auf `public/`**, nicht auf das Projekt-Root – wie bei jeder
  Laravel-Anwendung.
- **Schreibrechte** für `storage/` und `bootstrap/cache/` für den Webserver-Prozess.
- Ein Weg, wiederkehrende Jobs (Queue-Verarbeitung der Fotos, EXIF etc.) auszuführen – entweder ein
  dauerhaft laufender Queue-Worker oder, auf Shared-Hosting ohne Prozess-Dauerbetrieb, der
  Cron-per-HTTP-Endpunkt aus [docs/deployment.md](docs/deployment.md).

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

In der `.env` `ADMIN_EMAIL`, `ADMIN_NAME` und `ADMIN_PASSWORD` setzen, bevor der nächste Schritt
läuft – daraus legt `AdminUserSeeder` den ersten Admin-Zugang an. Bleiben sie leer, überspringt der
Seeder das Anlegen kommentarlos (kein Nutzer mit leerem Passwort), er lässt sich aber jederzeit
nachträglich erneut ausführen (`php artisan db:seed --class=AdminUserSeeder`), sobald die Werte
gesetzt sind.

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
npm install
npm run build
```

`db:seed` legt neben dem Admin-Zugang auch die rechtlich erforderlichen Seiten (Impressum,
Datenschutzerklärung) mit Platzhaltertext an – beides idempotent, also gefahrlos erneut ausführbar.

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
| `ADMIN_EMAIL`, `ADMIN_NAME`, `ADMIN_PASSWORD` | Admin-Zugang, angelegt von `AdminUserSeeder` (`php artisan db:seed`) |
| `CRON_USER`, `CRON_PASSWORD` | HTTP-Basic-Auth für den `/cron/schedule-run`-Endpunkt |
| `FILESYSTEM_DISK` | Standard-Dateisystem-Disk |

## Tests

```bash
php artisan test --compact
```

## Status

Das Projekt befindet sich in aktiver Entwicklung. Admin-Bereich sowie öffentlicher Auftritt (Alben, Fotos, Startseite, Seiten, Navigation/Footer) sind funktionsfähig. Noch offen: Profilseite (lässt sich bereits jetzt als normale CMS-Seite anlegen) und ein Blog.
