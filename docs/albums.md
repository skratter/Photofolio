# Alben & Fotos

## Alben

Felder: Titel, Slug (eindeutig, URL-tauglich), Beschreibung, Sortierung, Sichtbarkeit.

Sichtbarkeit ist `public` oder `private`. Private Alben verlangen ein Passwort (gehasht in
`password_hash` gespeichert); das Passwort ist beim Anlegen eines privaten Albums Pflicht und
kann beim Bearbeiten leer gelassen werden, um das bestehende zu behalten.

## Foto-Upload & Verarbeitung

Fotos werden im Album direkt hochgeladen (JPG/PNG/WebP, bis 20 MB je Datei). Jeder Upload
dispatcht den Queue-Job `ProcessUploadedPhoto`, der zwei Actions ausführt:

- `ExtractExifDataAction` – liest Kamera, Objektiv, Blende, Belichtungszeit, ISO, Brennweite,
  Aufnahmedatum und GPS-Koordinaten aus der Originaldatei.
- `GeneratePhotoVariantsAction` – erzeugt `thumb.webp` und `display.webp` per Intervention Image.

Solange der Job noch nicht durchgelaufen ist (`processed_at` ist `null`), zeigt die
Admin-Oberfläche einen Lade-Spinner statt des Bildes.

Dateien liegen flach unter `storage/app/photos/{photo_id}/` (`original.*`, `thumb.webp`,
`display.webp`) – bewusst nicht nach Album verschachtelt: Die Album-Zuordnung steht ohnehin in der
DB, eine Ordnerstruktur nach Album würde bei jedem Album-Wechsel eines Fotos einen echten
Datei-Move erzwingen, ohne dass die Anwendung selbst je einen Nutzen daraus zöge.

## Titelbild (Cover-Foto)

Jedes Album kann ein Titelbild haben (`cover_photo_id`, nullable FK auf `photos`).

- **Setzen**: In der Album-Ansicht zeigt jedes Foto beim Hover einen Stern-Button oben rechts
  neben „Löschen“. Das aktuelle Titelbild ist dauerhaft (nicht nur bei Hover) mit gefülltem Stern
  markiert und bekommt einen Amber-Ring um die Kachel.
- **Fallback**: `Album::effectiveCoverPhoto()` liefert das explizit gesetzte Titelbild, oder –
  falls keins gesetzt ist – das erste Foto nach Sortierung (`sort_order`). Genutzt vom Dashboard,
  damit dort nie eine leere Bildfläche auftaucht, auch bevor jemand aktiv ein Titelbild gewählt hat.
- **Löschen**: Wird das aktuelle Titelbild gelöscht, setzt die DB-Constraint (`nullOnDelete` auf
  `cover_photo_id`) das Feld automatisch zurück auf `null` – kein Anwendungscode nötig.

## Fotoverwaltung im Album

Mehrfachauswahl per Checkbox, dazu:

- **Umbenennen** (Massenoperation): vergibt einen Basisnamen mit fortlaufender Nummerierung in
  aktueller Sortierreihenfolge („Urlaub 1“, „Urlaub 2“, …).
- **Löschen**: einzeln oder als Massenoperation, inklusive Storage-Verzeichnis.
- **Download**: ein einzelnes Foto als Original-Datei, mehrere als ZIP
  (`BuildPhotoZipAction`, disambiguiert gleichnamige Dateien automatisch).

## Öffentlicher Download

Jedes Album hat ein `downloads_enabled`-Flag (Standard: an), im Admin-Formular als Checkbox
„Download erlauben" einstellbar. Ist es aktiv, zeigen die öffentlichen Seiten:

- Einen „Album herunterladen"-Button auf der Album-Seite. Er startet den Auswahlmodus
  (`AlbumShow::$selecting`/`$selectedPhotoIds`), der die Masonry-Ansicht durch ein einfaches
  Checkbox-Grid ersetzt. Die Auswahl bleibt seitenübergreifend erhalten (liegt als PHP-Property auf
  der Livewire-Komponente, nicht im Alpine-State), „Diese Seite auswählen" ergänzt sie um die
  aktuelle Seite. Von dort aus lässt sich entweder die Auswahl oder das ganze Album laden
  (`AlbumDownloadController`, optionaler `?ids=`-Query-Parameter für eine Teilmenge).
- Einen Download-Link in der Lightbox und auf der Einzelfoto-Seite (`PhotoDownloadController`).

Der Wechsel zum Checkbox-Grid im Auswahlmodus (statt Checkboxen direkt in die Masonry-Kacheln zu
legen) ist bewusst: Die Masonry-Tiles werden von JS absolut positioniert (`layoutMasonry()` in
app.js) und lesen dafür Inline-Styles, die ein Livewire-Morph beim Umschalten einer Checkbox
überschreiben könnte, ohne dass danach etwas ein Re-Layout auslöst. Das separate Grid ist ein
normales, nicht positioniertes Raster wie im Admin-Bereich und hat dieses Problem nicht.

Beide Controller sind reine `GET`-Routen mit nativem `response()->download()` statt Livewire-Actions
– ein `wire:click`-Download hatte den Alpine-Masonry-State nach dem DOM-Morph zerstört, weil Livewire
Dateien base64-kodiert durch den normalen Request-Response-Zyklus schickt. Zugriff ist wie überall an
`Album::isAccessible()` gebunden, private Alben brauchen also weiterhin das Passwort.
