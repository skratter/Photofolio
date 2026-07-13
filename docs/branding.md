# Branding

Da Photofolio als wiederverwendbare Grundlage für beliebige Fotografie-Homepages gedacht ist (nicht
an eine bestimmte Domain/Marke gebunden), lässt sich das visuelle Branding komplett über
**Admin → Einstellungen** setzen, ohne Code oder Dateien im Repo anzufassen.

## Felder

Alle drei sind optional und unabhängig voneinander:

- **Favicon** (SVG, PNG oder ICO, quadratisch empfohlen) – ersetzt die mitgelieferten
  Standard-Favicons (`public/favicons/*`, `public/favicon.ico`) im `<head>` jeder Seite.
- **Logo (helles Farbschema)** – ersetzt den Seitennamen-Text oben links im Header, solange das
  helle Farbschema aktiv ist.
- **Logo (dunkles Farbschema)** – wird im dunklen Farbschema anstelle des hellen Logos gezeigt.
  Wirkt nur, wenn zusätzlich ein Logo für das helle Farbschema hinterlegt ist (siehe unten).

Ohne eigenen Upload gelten die Standard-Favicons, und der Header zeigt weiterhin den Seitennamen als
Text – das Fehlen jedes einzelnen Felds ist ein vollständig unterstützter Zustand, kein Provisorium.

## Speicherung

Uploads landen auf dem `public`-Filesystem-Disk unter `storage/app/public/branding/` und werden
über den `public/storage`-Symlink ausgeliefert (siehe Installation in der Haupt-`README.md` –
`php artisan storage:link`). Die Settings-Tabelle speichert nur den relativen Pfad
(`favicon_path`, `logo_light_path`, `logo_dark_path`); `Setting::faviconUrl()` /
`::logoLightUrl()` / `::logoDarkUrl()` lösen daraus die öffentliche URL auf (`null`, wenn nichts
hochgeladen wurde). Beim Ersetzen oder Entfernen eines Uploads löscht `SettingForm` bzw.
`SettingsManager` die vorherige Datei automatisch.

## Warum zwei getrennte Logo-Dateien statt einer

Die naheliegende Alternative wäre ein einziges Logo mit `fill="currentColor"` im SVG, das sich per
CSS automatisch einfärbt. Das würde aber nur funktionieren, wenn das SVG **inline** im HTML steht –
eingebunden über `<img src="...">` (wie hier, da es ein hochgeladener, im Storage liegender
Dateipfad ist) verliert `currentColor` den Bezug zur umgebenden Textfarbe, da Browser SVGs in
`<img>`-Tags wie ein eigenständiges Dokument ohne Zugriff auf die Seiten-Stylesheets behandeln. Zwei
fertige Rasterungen/SVGs (hell/dunkel) mit Tailwinds `dark:`-Klassen umzuschalten
(`resources/views/components/layouts/public.blade.php`) ist robuster und funktioniert unabhängig
vom Dateiformat.
