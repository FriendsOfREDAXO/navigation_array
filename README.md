# Navigation Array für REDAXO 6

Die Navigation als Baum typisierter Einträge: Kategorien (auf Wunsch mit ihren Artikeln), der aktuelle Pfad und eigene
Daten – die Ausgabe bleibt ganz bei dir. Dazu Brotkrumen, strukturierte Daten (Schema.org `BreadcrumbList`) und zwei
Komponenten für Menü und Brotkrumen.

> [!NOTE]
> Dies ist der Branch **6.x** für REDAXO 6, das noch in Entwicklung ist (Branch 6.x von redaxo/core). Er ist für PHP 8.5
> und die API von REDAXO 6 neu gebaut – ohne Rücksicht auf die Version für REDAXO 5. Die gibt es unverändert im Branch
> [`master`](https://github.com/FriendsOfREDAXO/navigation_array/tree/master) und im Installer.

## Installation

REDAXO 6 installiert AddOns mit Composer. Solange das Paket nicht auf Packagist steht, holt Composer es direkt von GitHub:

```bash
composer config repositories.navigation_array '{"type": "vcs", "url": "https://github.com/FriendsOfREDAXO/navigation_array", "no-api": true}'
composer require friendsofredaxo/navigation_array:6.x-dev
php bin/console migrate
```

Voraussetzungen: PHP 8.5, REDAXO 6. Keine Einstellungen, keine eigenen Tabellen.

## Schnellstart: `walk()`

`walk()` ist der empfohlene Weg zur Ausgabe. Der Callback rendert einen Eintrag und bekommt eine Closure, die dessen
Kinder genauso rendert – verschachteltes Markup ohne Buchhaltung über Ebenen:

```php
use FriendsOfRedaxo\NavigationArray\Navigation;
use FriendsOfRedaxo\NavigationArray\NavigationItem;

use function Redaxo\Core\View\escape;

$nav = new Navigation(depth: 1, exclude: [12]);   // erste Ebene und eine darunter, ohne Kategorie 12

echo '<nav><ul>' . $nav->walk(static fn (NavigationItem $item, Closure $children): string => '<li' . ($item->active ? ' class="active"' : '') . '>'
    . '<a href="' . escape($item->url) . '"' . ($item->current ? ' aria-current="page"' : '') . '>' . escape($item->name) . '</a>'
    . ($item->hasChildren() ? '<ul>' . $children() . '</ul>' : '')
    . '</li>') . '</ul></nav>';
```

Wer gar kein eigenes Markup braucht, nimmt die [Komponente `NavigationMenu`](#komponenten).

## Optionen

Alle Optionen sind benannte Argumente des Konstruktors (`Navigation` ist `readonly`):

| Option | Standard | Wirkung |
|---|---|---|
| `start` | `null` | `null` = Start der aktuellen Website (Extension Point [`NavigationStart`](#navigationstart), sonst oberste Ebene), `0` = oberste Ebene, eine ID = deren Unterkategorien, `[1, 5, 12]` = genau diese Kategorien |
| `depth` | `4` | Ebenen unterhalb der ersten (`0` = nur die erste) |
| `ignoreOffline` | `true` | Offline-Kategorien und -Artikel weglassen |
| `exclude` | `[]` | Kategorien samt Unterkategorien weglassen |
| `filter` | `null` | `fn (Category $cat): bool` – `false` lässt die Kategorie samt Unterkategorien weg |
| `data` | `null` | `fn (Category $cat): array` – eigene Daten je Eintrag (`$item->data`) |
| `articles` | `false` | Artikel jeder Kategorie (ohne Startartikel) als `$item->articles` |
| `languageId` | aktuelle | Sprache der Navigation |
| `hidden` | `false` | auch Kategorien mit „Nicht in der Navigation zeigen“ (Metafeld, siehe unten) |

Kategorien ohne Recht fehlen immer: `Category::isPermitted()` des Cores – dort hängen sich Login-AddOns ein.

### „Nicht in der Navigation zeigen“

Das AddOn bringt für Kategorien das Metafeld **„Nicht in der Navigation zeigen“** mit (`NavigationMeta`, Spalte
`meta_nav_hidden`, für alle Sprachen gleich – im Backend unter Kategorie bearbeiten). Eine so markierte Kategorie bleibt
online, an ihrer Stelle und mit ihren Unterseiten; nur die Navigation lässt sie samt Unterseiten weg. Brotkrumen enthalten
sie weiter, die Seite bleibt erreichbar (z. B. eine Aktionsseite oder ein Bereich nur per Link).

```php
use FriendsOfRedaxo\NavigationArray\NavigationMeta;
use Redaxo\Core\Content\CategoryHandler;

NavigationMeta::of($category)->navHidden;                                // lesen
CategoryHandler::edit($id, $languageId, [], ['nav_hidden' => true]);     // setzen
new Navigation(hidden: true);                                            // markierte trotzdem zeigen
```

## Methoden

| Methode | Ergebnis |
|---|---|
| `walk(Closure $callback): string` | Ausgabe, verschachtelt (siehe oben) |
| `items(): list<NavigationItem>` | die Einträge der ersten Ebene mit ihren Kindern |
| `find(Category\|int\|null $category = null): ?NavigationItem` | Eintrag der aktuellen oder angegebenen Kategorie mit Kindern – auch außerhalb des Starts |
| `breadcrumb(Category\|int\|null $category = null, BreadcrumbItem ...$append): list<BreadcrumbItem>` | Pfad von oben bis zur Kategorie – für die aktuelle Seite mit dem aktuellen Artikel am Ende (außer dem Startartikel) –, eigene Einträge danach |
| `jsonLd(Category\|int\|null $category = null, BreadcrumbItem ...$append): string` | Brotkrumen als `<script type="application/ld+json">` mit absoluten Adressen (auf der Konsole mit `REX_BASE_URL`) |

### `NavigationItem`

| Eigenschaft / Methode | Inhalt |
|---|---|
| `id`, `parentId`, `name`, `url` | wie die Kategorie (`parentId` 0 auf oberster Ebene) |
| `level` | 0 = erste Ebene |
| `path` | IDs der Elternkategorien |
| `active` | im Pfad der aktuellen Kategorie |
| `current` | die aktuelle Kategorie |
| `children`, `hasChildren()` | Einträge der nächsten Ebene |
| `articles`, `isArticle()` | Artikel der Kategorie (mit `articles: true`) – ebenfalls `NavigationItem` |
| `data` | eigene Daten (Option `data`, Extension Point) |
| `element` | die `Category` (bzw. der `Article`) selbst, z. B. für `getValue()` |
| `withData(array $data)` | Kopie mit ergänzten Daten |

`json_encode()` funktioniert direkt (`JsonSerializable`), z. B. für eine API oder ein JavaScript-Menü.

## Beispiele

### Eigene Daten und Filter

```php
use Redaxo\Core\Content\Category;

$nav = new Navigation(
    depth: 2,
    filter: static fn (Category $cat): bool => 'hidden' !== $cat->getValue('cat_menu'),
    data: static fn (Category $cat): array => ['icon' => (string) $cat->getValue('cat_icon')],
);

echo $nav->walk(static fn (NavigationItem $item, Closure $children): string
    => '<li><a href="' . escape($item->url) . '">' . escape($item->data['icon'] . ' ' . $item->name) . '</a>'
    . ($item->hasChildren() ? '<ul>' . $children() . '</ul>' : '') . '</li>');
```

### Unternavigation der aktuellen Hauptkategorie

```php
use Redaxo\Core\Content\Category;

$current = Category::getCurrent();
$main = $current?->path[0] ?? $current?->id;   // Hauptkategorie des aktuellen Zweigs

if (null !== $main) {
    echo '<ul>' . new Navigation(start: $main, depth: 1)->walk(static fn (NavigationItem $item, Closure $children): string
        => '<li><a href="' . escape($item->url) . '">' . escape($item->name) . '</a>'
        . ($item->hasChildren() ? '<ul>' . $children() . '</ul>' : '') . '</li>') . '</ul>';
}
```

### Artikel einbeziehen

```php
foreach (new Navigation(depth: 0, articles: true)->items() as $category) {
    foreach ($category->articles as $article) {
        // $article->name, $article->url, $article->current …
    }
}
```

### Flache Liste, z. B. eine Sitemap

Wer nichts ausgibt, gibt im Callback `''` zurück und ruft `$children()` auf, um tiefer zu gehen:

```php
$urls = [];
new Navigation(depth: 10)->walk(static function (NavigationItem $item, Closure $children) use (&$urls): string {
    $urls[] = $item->url;

    return $children();
});
```

### Brotkrumen und strukturierte Daten

```php
use FriendsOfRedaxo\NavigationArray\BreadcrumbItem;

$nav = new Navigation();

foreach ($nav->breadcrumb() as $crumb) {
    echo $crumb->current
        ? '<span aria-current="page">' . escape($crumb->name) . '</span>'
        : '<a href="' . escape($crumb->url) . '">' . escape($crumb->name) . '</a> › ';
}

// mit eigenem letzten Eintrag, z. B. dem Datensatz einer Detailseite
echo $nav->jsonLd(null, new BreadcrumbItem($record->get('title'), $detailUrl));
```

## Komponenten

Für die häufigsten Fälle bringt das AddOn zwei Komponenten nach dem Muster des Cores mit (`Renderable`, View-Datei
daneben):

```php
use FriendsOfRedaxo\NavigationArray\Component\NavigationBreadcrumb;
use FriendsOfRedaxo\NavigationArray\Component\NavigationMenu;

echo new NavigationMenu(new Navigation(depth: 1), label: 'Hauptmenü', activeClass: 'is-active')->render();

echo new NavigationBreadcrumb(new Navigation(), label: 'Sie sind hier', jsonLd: true)->render();
```

`NavigationMenu` gibt `<nav><ul>…</ul></nav>` aus, die aktuelle Seite mit `aria-current="page"`. `NavigationBreadcrumb`
gibt `<nav><ol>…</ol></nav>` aus, auf Wunsch samt JSON-LD; eigene Einträge am Ende über `append: [new BreadcrumbItem(…)]`.

Eigenes Markup: Die View-Dateien lassen sich im Projekt ersetzen, ohne die Klassen anzufassen:

```php
use Redaxo\Core\View\ViewResolver;

// z. B. in Project::boot()
ViewResolver::override(NavigationMenu::class, __DIR__ . '/views/navigation-menu.php');
```

## Extension Points

Extension Points sind wie im Core von REDAXO 6 Klassen – registriert wird mit dem Klassennamen.

### `NavigationItemGenerated`

Jeder Eintrag einer Kategorie, nachdem er gebaut ist. Andere AddOns ergänzen so eigene Daten:

```php
use FriendsOfRedaxo\NavigationArray\ExtensionPoint\NavigationItemGenerated;
use Redaxo\Core\ExtensionPoint\Extension;

Extension::register(NavigationItemGenerated::class, static fn (NavigationItemGenerated $ep): NavigationItem
    => $ep->subject->withData(['badge' => MyShop::countIn($ep->subject->id)]));
```

### `NavigationStart`

Start einer Navigation ohne `start`: die Kategorie der aktuellen Website, z. B. der Seitenbaum einer Domain. In REDAXO 5
kam er aus yrewrite; in REDAXO 6 liefert ihn das AddOn, das Domains verwaltet (fluid tut das für Landing-Domains).
Ohne Extension ist es die oberste Ebene.

```php
use FriendsOfRedaxo\NavigationArray\ExtensionPoint\NavigationStart;

Extension::register(NavigationStart::class, static fn (NavigationStart $ep): int|array => MyDomains::mountId($ep->articleId) ?? $ep->subject);
```

Eigenschaften: `subject` (der Start, wie die Option `start`), `articleId`, `languageId`.

## Umstieg von REDAXO 5

Die Version für REDAXO 6 ist neu gebaut. Die Ideen sind dieselben, die API ist eine andere:

| REDAXO 5 | REDAXO 6 |
|---|---|
| `BuildArray::create()->setDepth(2)->setExcludedCategories([12])` | `new Navigation(depth: 2, exclude: [12])` |
| `setStart(-1)` (yrewrite) | `start: null` + Extension Point `NavigationStart` |
| `setIgnore(1)`, `setIncludeArticles()` | `ignoreOffline: true`, `articles: true` |
| `setCategoryFilterCallback()`, `setCustomDataCallback()` | `filter:`, `data:` (Closures mit `Category`) |
| `generate()` – Arrays mit `catId`, `catName`, `url` … | `items()` – `NavigationItem` mit `id`, `name`, `url` … |
| `walk(fn ($item, $level) => …)` | `walk(fn (NavigationItem $item, Closure $children): string => …)` |
| `getCategory()` | `find()` |
| `getBreadcrumb()`, `toJsonLd()` | `breadcrumb()`, `jsonLd()` mit `BreadcrumbItem` |
| `toJson()` | `json_encode($nav->items())` |
| Rechte über YCom | `Category::isPermitted()` des Cores |
| `NAVIGATION_ARRAY_GENERATE_ITEM` | `NavigationItemGenerated` (`$ep->subject->withData()`) |
| Fragment `navigation_array/navigation.php` | Komponente `NavigationMenu` (Fragmente entfallen in REDAXO 6) |

## Lizenz

MIT – siehe [LICENSE](LICENSE). Ein Projekt von [Friends Of REDAXO](https://friendsofredaxo.github.io).
