# Auswertung / View-Tracking

Zählt Aufrufe von Alben, Fotos und der Startseite – ohne Google Analytics oder ähnliche
Drittanbieter, rein serverseitig über [`cyrildewit/eloquent-viewable`](https://github.com/cyrildewit/eloquent-viewable).

## Was gezählt wird und was nicht

- **Bots werden automatisch ausgefiltert.** Das Paket bringt `jaybizzle/crawler-detect` mit
  (`ignore_bots => true` in `config/eloquent-viewable.php`, Standardeinstellung).
- **Eingeloggte Admin-Aufrufe zählen nicht.** Das übernimmt `App\Actions\RecordViewAction`:
  ```php
  public function execute(Viewable $viewable): void
  {
      if (Auth::check()) {
          return;
      }

      views($viewable)->record();
  }
  ```
  Jede Stelle, die einen View zählen soll, ruft diese Action auf statt direkt `views(...)->record()`
  – so bleibt „kein Admin-Traffic in der Statistik“ an einer einzigen Stelle geregelt.

## Woran das hängt

- `Album` und `Photo` implementieren `Viewable` + `InteractsWithViews` (Eloquent-Trait des Pakets).
- Für die Startseite gibt es kein Album/Foto zum Anhängen – dafür existiert `App\Models\Page`, ein
  minimales Model mit nur einem `key`-Feld. `Page::forKey('welcome')` holt/erstellt die Zeile für
  die Startseite. `WelcomeController` ruft darauf `RecordViewAction` auf.
- **Wichtig:** Es gibt aktuell noch keine öffentliche Album-/Foto-Ansicht für Besucher (die
  Startseite ist ein Platzhalter). `RecordViewAction` wird also für Alben/Fotos noch nirgends
  aufgerufen. Sobald die öffentliche Galerie gebaut wird, muss dort beim Anzeigen eines Albums/Fotos
  nur `(new RecordViewAction)->execute($album)` bzw. `...->execute($photo)` aufgerufen werden.

## Wo man die Zahlen sieht

- **Dashboard** (`/admin`): Alben-/Fotos-Anzahl, Startseiten-Aufrufe, sowie „Meistgesehenes Album“
  und „Meistgesehenes Foto“ als große Kacheln mit Bildvorschau (nutzt bei Alben
  `Album::effectiveCoverPhoto()`, siehe [albums.md](albums.md)).
- **Auswertung** (`/admin/analytics`): Top-20-Tabellen für Alben und Fotos, jeweils mit
  Gesamt-Aufrufen und eindeutigen Besuchern (`orderByViews()` / `withViewsCount(unique: true)` –
  Query-Scopes des Pakets, kein N+1).

## Eindeutige Besucher

Das Paket unterscheidet Besucher über ein Cookie (`eloquent_viewable`, 5 Jahre gültig), nicht über
Accounts – es gibt ja keine Besucher-Accounts. „Eindeutig“ heißt also „gleicher Browser/Cookie“,
nicht zwingend „gleicher Mensch“.
