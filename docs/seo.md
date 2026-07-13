# SEO

## Meta-Description

- **Seiten**: eigenes Feld `Page::$meta_description`, im Seiten-Formular direkt editierbar.
- **Alben**: kein eigenes Feld – wird automatisch aus der (HTML-)Beschreibung abgeleitet
  (`Album::metaDescription()`: Tags entfernt, auf 160 Zeichen gekürzt). Eine Dopplung zum ohnehin
  vorhandenen Beschreibungstext hätte keinen Mehrwert.
- **Fotos**: analog aus den Notizen (`Photo::metaDescription()`).
- **Startseite**: eigenes Einstellungsfeld „Meta-Beschreibung (Startseite)“
  (`Setting::$homepage_meta_description`), da es dort keinen natürlichen Beschreibungstext gibt, aus
  dem sich etwas ableiten ließe.

Ausgegeben wird `<meta name="description">` jeweils über den `<x-slot:head>`-Mechanismus des
Layouts (siehe `resources/views/components/layouts/public.blade.php` /
`resources/views/components/layouts/app.blade.php`).

## Open Graph / Twitter Cards

Wiederverwendbare Komponente `resources/views/components/open-graph.blade.php`
(`<x-open-graph :title="..." :description="..." :image="..." />`), eingebunden auf Startseite,
Alben, Seiten und Einzelfoto-Seiten. Erzeugt `og:title`, `og:description` (falls vorhanden),
`og:url`, `og:image` (falls vorhanden) sowie die passenden `twitter:*`-Pendants
(`summary_large_image`, wenn ein Bild vorhanden ist, sonst `summary`).

Als Bild dient jeweils das Titelbild des Albums (`Album::effectiveCoverPhoto()`) bzw. das Foto
selbst – nur wenn es bereits verarbeitet ist (`Photo::isProcessed()`), da unverarbeitete Fotos noch
keine `display`-Variante haben.

**Private, gesperrte Alben liefern bewusst keine Meta-/OG-Tags aus.** Der Passwortschutz ist nur
eine UI-Sperre auf derselben Seite (die Seite antwortet mit HTTP 200 und einem Passwort-Formular);
ohne diese Ausnahme würden Titel, Beschreibung und Titelbild eines privaten Albums schon über
Link-Vorschauen (WhatsApp, Slack, Facebook, …) nach außen dringen, bevor überhaupt jemand das
Passwort eingegeben hat. Siehe die Bedingung `@if ($album->isAccessible())` um den Head-Slot in
`resources/views/livewire/album-show.blade.php`.

## Sitemap & robots.txt

- `GET /sitemap.xml` (`App\Http\Controllers\SitemapController`): Startseite, öffentliche Alben,
  Fotos öffentlicher, verarbeiteter Alben (`Photo::processed()`), veröffentlichte Seiten. Private
  Alben und deren Fotos sind ausgeschlossen.
- **Bild-Sitemap-Erweiterung**: jede Album-URL listet zusätzlich alle ihre verarbeiteten Fotos als
  `<image:image>`-Kindelemente (Googles `xmlns:image`-Namespace), jede Einzelfoto-Seite ihr eigenes
  Bild. Für eine Foto-Portfolio-Seite ist die Google-Bildersuche oft relevanter als die normale
  Websuche – das hebt die Sichtbarkeit dort gezielt.
- `GET /robots.txt` (`App\Http\Controllers\RobotsTxtController`): sperrt `/admin` und `/cron`,
  verweist auf die Sitemap.

## Bewusst nicht umgesetzt

- **Bild-`alt`-Texte**: fallen auf leer zurück, wenn ein Foto keinen Titel hat. Kein
  Fallback-Mechanismus vorgesehen – der Weg ist, Fotos konsequent zu betiteln, nicht eine
  Ersatzlösung um fehlende Titel herumzubauen.
- **Strukturierte Daten (JSON-LD, z. B. `ImageGallery`-Schema)**: als nächster möglicher Schritt
  identifiziert, aber noch nicht umgesetzt.
