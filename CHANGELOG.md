# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [5.4.1] – 2026-05-06

### Fixed

- **`toJsonLd()`** – Gibt nun absolute URLs aus, wie von Google für JSON-LD BreadcrumbList gefordert. Bisher wurden relative URLs ausgegeben, die von der Google Search Console abgelehnt werden. Scheme und Host werden via `rex_request::isHttps()` und `rex_request::server('HTTP_HOST')` ermittelt; bereits absolute URLs bleiben unverändert.

## [5.4.0] – 2026-04-17

### Added

- **`getBreadcrumb()`** – Gibt den Breadcrumb-Pfad von Root bis zur aktuellen (oder angegebenen) Kategorie zurück. Über den Parameter `$append` können eigene Einträge am Ende ergänzt werden, z. B. Detailseiten aus dem URL-AddOn, YForm-Datensätze oder Shop-Produkte.
- **`toJsonLd()`** – Erzeugt direkt einen `<script type="application/ld+json">`-Tag mit einer Schema.org `BreadcrumbList`. Akzeptiert dieselben Parameter wie `getBreadcrumb()`.
- **`setIncludeArticles(bool $include = true)`** – Bezieht Nicht-Start-Artikel jeder Kategorie unter dem Key `articles` in die Navigationsstruktur ein.
- **`setStart(int|array $start)`** – Der Typ wurde von `int` auf `int|array<int>` erweitert. Es können jetzt mehrere Start-Kategorien als Array übergeben werden (z. B. `setStart([1, 5, 12])`). Bestehende Aufrufe mit einer einzelnen ID sind weiterhin unverändert gültig.
- **Extension Point `NAVIGATION_ARRAY_GENERATE_ITEM`** – Feuert nach dem `customDataCallback` für jedes generierte Item. Andere AddOns können so eigene Daten hinzufügen, ohne den Callback zu überschreiben.

### Fixed

- Alle Properties in `BuildArray` haben jetzt explizite Typdeklarationen (behebt 18 Rexstan-Fehler).
- Ungenutzter Konstruktor-Parameter `$depthSaved` entfernt.
- `toJson()` gibt jetzt garantiert einen `string` zurück (`(string) json_encode(...)`).
- Redundante `is_array()`-Prüfung nach bereits durchgeführtem `is_int()`-Guard entfernt.
- Nicht verwendete Imports (`rex_exception`, `rex_logger`) entfernt.
- PHPDoc-Typen präzisiert: `array` → `array<int, array<string, mixed>>` bzw. `array<string, mixed>` in allen relevanten Methoden.

## [5.3.3] – 2025-04-01

### Fixed

- `setStart()` wurde immer durch den yrewrite-Domain-Startpunkt überschrieben, auch wenn eine eigene ID gesetzt wurde.
- Verbesserte Prüfung des ycom-Plugins gegen nicht vorhandene AddOns abgesichert.
- `initializeStartCategory()` optimiert für bessere Lesbarkeit und Wartbarkeit.
- `setIgnore()`-Methode: Dokumentation verbessert und explizite Typumwandlung eingeführt.

## [5.3.2] – 2025-01-20

### Fixed

- Depth-Berechnung korrigiert (PR [#43](https://github.com/FriendsOfREDAXO/navigation_array/pull/43)).
- README aktualisiert.

### Contributors

[@skerbis](https://github.com/skerbis), [@dpf-dd](https://github.com/dpf-dd)

## [5.3.1] – 2025-01-15

### Fixed

- Whoops-Fehler bei ausgelagertem Funktionsaufruf behoben (PR [#42](https://github.com/FriendsOfREDAXO/navigation_array/pull/42)).

### Contributors

[@dpf-dd](https://github.com/dpf-dd)

## [5.3.0] – 2025-01-01

### Added

- Neue `walk(callable $callback)`-Methode für einfache, rekursive Navigationstraversierung.
- README umfassend überarbeitet: `walk()`, `getCategory()`, Vergleich mit eigener Iteration, Copy-Paste-fertige Beispiele.

### Contributors

[@skerbis](https://github.com/skerbis)

## [5.2.0] – 2024-11-18

### Fixed

- Bugfix-Release für ycom-Rechte.

### Added

- Neue interne Methode `isPermitted()` (PR [#40](https://github.com/FriendsOfREDAXO/navigation_array/pull/40)).

### Contributors

[@skerbis](https://github.com/skerbis)

## [5.1.0] – 2024-10-25

### Added

- Neue `getCategory(?int $categoryId = null)`-Methode: Gibt alle Infos zu einer Kategorie als Array zurück, inkl. ycom-Berechtigung, Filter-Status, Kindkategorien und Kategorie-Objekt.

### Contributors

[@skerbis](https://github.com/skerbis), [@marcohanke](https://github.com/marcohanke)

## [5.0.0] – 2024-10-23

### Added

- Neue `toJson()`-Methode.
- Neue `setExcludedCategories()`-Methode.
- Fragment-Integration (PR [#34](https://github.com/FriendsOfREDAXO/navigation_array/pull/34)).

### Changed

- Ungenutzter Parameter `$depthSaved` entfernt.
- PHPDoc-Kommentare ergänzt, Code reorganisiert.

### Contributors

[@skerbis](https://github.com/skerbis), [@marcohanke](https://github.com/marcohanke)

## [4.0.0] – 2024-04-19

### Removed

- Deprecated-Class `\FriendsOfRedaxo\navigationArray` entfernt.
- Deprecated-Funktion `navArray()` entfernt.

> **Migration:** Vorhandener Code muss auf `FriendsOfRedaxo\NavigationArray\BuildArray` umgestellt werden (siehe README).

## [3.1.1] – 2024-04-05

### Added

- Vollständige Verkettung der Factory inkl. `generate()` möglich: `BuildArray::create()->setDepth(3)->generate()`.

### Fixed

- README-Korrekturen ([@erraiva](https://github.com/erraiva)).

## [3.1.0] – 2024-04-04

### Added

- Vollständige Verkettung der Factory möglich.

### Fixed

- README-Fixes ([@erraiva](https://github.com/erraiva)).

## [3.0.0] – 2024-02-02

### Added

- Neuer Namespace `FriendsOfRedaxo\NavigationArray\BuildArray`.
- `setCategoryFilterCallback()` für benutzerdefinierte Kategorie-Filter.
- `setCustomDataCallback()` für benutzerdefinierte Daten pro Item.
- Mehrere Startkategorien als Array übergebbar.
- Automatische yrewrite-Start-Kategorieerkennung.
- ycom-Integration (Rechteprüfung).
- Level wird ab den übergebenen Kategorien gezählt.
- Ausführliche README mit Anwendungsbeispielen.

### Changed

- Kategorie-Objekt nicht mehr direkt in der Übergabe enthalten.
- Die alte `navArray()`-Funktion nutzt intern die neue Klasse (rückwärtskompatibel bis 4.0.0).

### Contributors

[@skerbis](https://github.com/skerbis), [@aeberhard](https://github.com/aeberhard)

## [2.0.2] – 2023-12-16

### Fixed

- Bugfixes, fehlende Variablen behoben.

## [2.0.1]

### Fixed

- PHP-Version-Anforderung korrigiert.

## [1.2.1] – 2022-09-30

### Changed

- `package.yml` aktualisiert.

## [1.2.0] – 2021-12-11

### Added

- Selbst zusammengestellte Startkategorie-Objekte übergebbar.
- Bootstrap 5 Dropdown-Menü-Beispiel in README.

## [1.1.1] – 2021-02-06

### Fixed

- PHP 8 Installationsfehler behoben.

## [1.1.0] – 2021-01-14

### Fixed

- ycom 4.0.3 Kompatibilität hergestellt.
- README-Fehler behoben.

## [1.0.3] – 2020-04-21

### Changed

- Minimale PHP-Version festgelegt.
- README erweitert.

## [1.0.1] – 2019-10-29

### Fixed

- Bugfix ([@olien](https://github.com/olien)).

## [1.0] – 2019-10-16

Erstes Release.
