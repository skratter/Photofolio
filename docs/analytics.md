# Auswertung / View-Tracking

Zählt Aufrufe von Alben, Fotos, Seiten und der Startseite – ohne Google Analytics oder ähnliche
Drittanbieter, rein serverseitig über [`cyrildewit/eloquent-viewable`](https://github.com/cyrildewit/eloquent-viewable).

## Was gezählt wird und was nicht

- **Bots werden automatisch ausgefiltert.** Das Paket bringt `jaybizzle/crawler-detect` mit
  (`ignore_bots => true` in `config/eloquent-viewable.php`, Standardeinstellung).
- **Eingeloggte Admin-Aufrufe zählen nicht.**
- **Do-Not-Track wird beachtet.** Besucher mit `DNT: 1`-Header werden nicht gezählt. Das Paket
  bringt dafür zwar eine eigene `honor_dnt`-Einstellung mit, sucht den Header aber unter dem
  falschen Schlüssel (`HTTP_DNT` statt `DNT`) und greift dadurch nie – die eigentliche Prüfung läuft
  deshalb direkt in `RecordViewAction`:
  ```php
  public function execute(Viewable $viewable): void
  {
      if (Auth::check()) {
          return;
      }

      if (request()->header('DNT') === '1') {
          return;
      }

      views($viewable)->record();
  }
  ```
  Jede Stelle, die einen View zählen soll, ruft diese Action auf statt direkt `views(...)->record()`
  – so bleiben „kein Admin-Traffic“ und „Do-Not-Track“ an einer einzigen Stelle geregelt.

## Woran das hängt

- `Album`, `Photo` und `Page` implementieren `Viewable` + `InteractsWithViews` (Eloquent-Trait des
  Pakets). `Page` deckt sowohl die Startseite (`Page::forSlug('welcome')`) als auch alle über das
  Seiten-CMS angelegten Seiten ab (Impressum, Datenschutz, freie Seiten – verwaltet über
  `App\Livewire\Admin\PageManager`, `/admin/pages`).
- `RecordViewAction` wird für alle drei Typen aufgerufen: `App\Livewire\PageShow`
  (`/seite/{page:slug}`), `App\Livewire\AlbumShow` (`/album/{album:slug}`) und
  `App\Livewire\PhotoShow` (`/album/{album:slug}/{photo}`) rufen es jeweils beim Anzeigen auf. Bei
  privaten Alben zählt der Aufruf erst, sobald das Album über das Passwort-Formular freigeschaltet
  wurde (`Album::isAccessible()`).

## Wo man die Zahlen sieht

- **Dashboard** (`/admin`): Alben-/Fotos-Anzahl, Startseiten-Aufrufe, sowie „Meistgesehenes Album“
  und „Meistgesehenes Foto“ als große Kacheln mit Bildvorschau.
- **Auswertung** (`/admin/analytics`): Top-20-Tabellen für Alben, Fotos und Seiten, jeweils mit
  Gesamt-Aufrufen und eindeutigen Besuchern (`orderByViews()` / `withViewsCount(unique: true)` –
  Query-Scopes des Pakets, kein N+1). Jede Zeile (und die Startseiten-Kachel) hat einen
  „Verlauf“-Button, der ein Detail-Modal mit zwei Abschnitten öffnet:
  - **Verlauf** (`BuildViewHistoryAction`): Aufrufe aufgeschlüsselt nach Tag (letzte 7 Tage), Monat
    (Rest des laufenden Jahres) und Jahr (Vorjahre) – überschneidungsfrei aufgeteilt, plus
    Gesamtsumme.
  - **Herkunft** (`BuildViewOriginsAction`): Top-10-Referrer (auf Host reduziert, z. B.
    „google.com“) und Top-10 User-Agent-Strings. Hilfreich, um Bot-/Scraper-Traffic zu erkennen, den
    CrawlerDetect nicht als solchen einstuft (z. B. auffällig viele Aufrufe ganz ohne User-Agent).
    Referrer, die unter **Admin → Einstellungen** als eigene Domain hinterlegt sind (z. B.
    alte/alias-Domains, die hierher weiterleiten), werden farblich markiert, damit sie nicht
    fälschlich als externe Verlinkung gewertet werden.

## Was gespeichert wird

Pro View: Zeitpunkt und Besucher-Cookie-Wert, sowie – über zusätzliche Spalten an der
`views`-Tabelle des Pakets – `referrer` und `user_agent`. Befüllt wird das über den Listener
`App\Listeners\RecordViewMetadata`, der auf das `ViewRecorded`-Event des Pakets reagiert (statt
diese Felder selbst beim Aufruf von `views(...)->record()` zu setzen, was die API des Pakets nicht
hergibt). Explizit **keine IP-Adresse**.

## Eindeutige Besucher

Das Paket unterscheidet Besucher über ein Cookie (`eloquent_viewable`, 5 Jahre gültig), nicht über
Accounts – es gibt ja keine Besucher-Accounts. „Eindeutig“ heißt also „gleicher Browser/Cookie“,
nicht zwingend „gleicher Mensch“.
