# REDAXO FOR NavigationArray
Navigation Array ist ein PHP Klasse für die einfache Erstellung einer Navigationsstruktur als Array.
## Features
- Offline Artikel und Ycom Rechte werden berücksichtigt
- Startkategorie kann frei gewählt werden
- Tiefe kann festgelegt werden
- Kategorien filtern und manipulieren (z.B. mit Meta Infos) über Callbacks
- Mitgelieferte Fragmente für die HTML Ausgabe der Navigation

## Array-Struktur
So sieht das generierte Array aus. Es enthält alle Kategorien und Unterkategorien bis zur angegebenen Tiefe. Offline Kategorien und Rechte aus Ycom werden vorher aus dem Array entfernt.
```php
array:7 [▼
    0 => array:11 [▶]
    1 => array:11 [▶]
    2 => array:11 [▶]
    3 => array:11 [▶]
    4 => array:11 [▼
        "catId" => 43
        "parentId" => 0
        "level" => 0
        "catName" => "Kontakt"
        "url" => "/kontakt/"
        "hasChildren" => true
        "children" => array:2 [▼
            0 => array:11 [▼
                "catId" => 178
                "parentId" => 43
                "level" => 1
                "catName" => "Kontaktformular"
                "url" => "/kontakt/kontaktformular/"
                "hasChildren" => false
                "children" => []
                "path" => array:1 [▶]
                "active" => false
                "current" => false
            ]
            1 => array:11 [▶]
        ]
        "path" => []
        "active" => true
        "current" => true
    ]
    5 => array:11 [▶]
    6 => array:11 [▶]
]
```


## Array generieren

### Aufruf mit Konstruktor
```php
// define namespace
use FriendsOfRedaxo\NavigationArray\BuildArray;
// create object
$navigationObject = new BuildArray(-1, 3);
// generate navigation array
$navigationArray = $navigationObject->generate();
```

### Aufruf mit Methoden

```php
// define namespace
use FriendsOfRedaxo\NavigationArray\BuildArray;
// create navigation array
$navigationArray = BuildArray::create()
    ->setStart(-1)
    ->setDepth(4)
    ->setIgnore(true)
    ->setCategoryFilterCallback(CategoryFilter())
    ->setCustomDataCallback(CustomData())
    ->generate();
```

## HTML Ausgabe
In erster Linie generiert Navigation Array ein Array. Die Ausgabe als HTML ist Aufgabe des Entwicklers. Hier sind einige Beispiele, wie das Array in HTML integriert werden kann.

### Fragmente
Mit Fragmenten wird die Logic sauber vom Code getrennt. Das Fragment 'navigation.php' kann kopiert, angepasst und in `project` oder `theme` Ordner abgelegt werden. 

```php
// Festlegen des Namespace
use FriendsOfRedaxo\NavigationArray\BuildArray;

// Navigation Array erstellen
$navigationObject = new BuildArray(-1, 3);
$navigationArray = $navigationObject->generate();

//Fragmente laden Navigation Array und Klasse für aktive Elemente übergeben
$fragment = new rex_fragment();
$fragment->setVar('navigationArray', $navigationArray);
$fragment->setVar('activeClass', 'active');

// Navigation ausgeben
<nav>
    <ul role="menubar">
        echo $fragment->parse('navigation_array/navigation.php');
    </ul>
</nav>
```


### PHP Funktion
Alternativ kann man das Array auch direkt in PHP Verarbeiten hier einige Beispiele
#### Beispiel einfache Navigation 

```php
// Function to generate the navigation list
function generateNavigationList($items) {
    if (empty($items)) {
        return '';
    }

    $output = '<ul>';
    foreach ($items as $item) {
        $output .= '<li>';
        $output .= '<a href="' . $item['url'] . '">' . $item['catName'] . '</a>';

        // Check if this item has children
        if ($item['hasChildren']) {
            $output .= generateNavigationList($item['children']);
        }

        $output .= '</li>';
    }
    $output .= '</ul>';

    return $output;
}

use FriendsOfRedaxo\NavigationArray\BuildArray;
$NavigationArray = BuildArray::create()->setDepth(3)->generate();

// Generate the navigation list
$navigationList = generateNavigationList($NavigationArray);

// Output the navigation list
echo $navigationList;
```

#### Beispiel erweiterte Navigation mit Callback 

```php
use FriendsOfRedaxo\NavigationArray\BuildArray;

$mainnavi_array = (new BuildArray())
    ->setCustomDataCallback(function($cat) {
        return ['navtype' => $cat->getValue('cat_navigationstyp')];
    })
    ->generate();

$mainnavigation_items = [];

function createNavigationItems($navi) {
    $items = [];
    $class_active = $navi['active'] ? 'active' : '';
    $class_current = $navi['current'] ? ' current' : '';
    $class_has_child = $navi['children'] ? ' has-child' : '';

    $items[] = "<li class=\"{$class_active}{$class_current}{$class_has_child}\"><a href=\"{$navi['url']}\">{$navi['catName']}</a>";

    if ($navi['children']) {
        $items[] = '<button class="child-toggle">X</button>'; 
        $items[] = '<ul>';

        foreach ($navi['children'] as $nav_child) {
            $items[] = createNavigationItems($nav_child);
        }

        $items[] = '</ul>';
    }

    $items[] = '</li>';

    return implode($items);
}

foreach ($mainnavi_array as $navi) {
    $navtype_arr = explode('|', $navi['navtype']);

    if (in_array('main', $navtype_arr)) {
        $mainnavigation_items[] = createNavigationItems($navi);
    }
}

$mainnavigation = '<ul id="mainnavigation">' . implode($mainnavigation_items) . '</ul>';
```

##### Erklärung

Zu Beginn wird die Klasse `BuildArray` aus dem `FriendsOfRedaxo\NavigationArray`-Namespace verwendet, um ein Array für die Hauptnavigation zu generieren. Dabei wird eine benutzerdefinierte Callback-Funktion verwendet, um zusätzliche Daten für jeden Eintrag in der Navigation festzulegen. In diesem Fall wird das Feld `navtype` mit dem Wert des Feldes `cat_navigationstyp` aus der Kategorie des Eintrags befüllt.

Anschließend wird eine leere Array-Variable `$mainnavigation_items` definiert. Diese wird später mit den generierten Navigationselementen befüllt.

Die Funktion `createNavigationItems` wird definiert, um rekursiv die HTML-Struktur für jedes Navigationselement zu erstellen. Dabei werden verschiedene CSS-Klassen basierend auf den Eigenschaften des Navigationselements gesetzt, wie z.B. `active`, `current` und `has-child`. Die Funktion gibt den HTML-Code für das Navigationselement als String zurück.

In der Schleife `foreach ($mainnavi_array as $navi)` wird über jedes Element in der generierten Hauptnavigation iteriert. Das Feld `navtype` wird in ein Array `$navtype_arr` aufgeteilt, indem der Wert anhand des Trennzeichens `|` gesplittet wird. Wenn der Wert `main` in `$navtype_arr` enthalten ist, wird die Funktion `createNavigationItems` aufgerufen und das generierte Navigationselement wird dem Array `$mainnavigation_items` hinzugefügt.

Schließlich wird die Hauptnavigation als HTML-Code in der Variable `$mainnavigation` gespeichert, indem die einzelnen Navigationselemente mit `implode` zu einem String zusammengefügt werden.

Das Feld `cat_navigationstyp` hat derzeit die Werte "meta, main, footer". Es wird verwendet, um zu bestimmen, welche Navigationselemente in der Hauptnavigation angezeigt werden sollen. In diesem Fall werden nur die Elemente angezeigt, deren `navtype` den Wert 'main' enthält.

#### Beispiel: Bootstrap 5 Navigation 

```php
<?php
function bsnavi5($data = array())
{
    $output = array();
    foreach ($data as $cat) {
        $subnavi = $catname = $li = $active =

 $ul = $link_extra = "";
        $aclass = 'nav-link';
        $li = ' class="nav-item"';

        if ($cat['level'] == 0 && $cat['hasChildren'] == true) {
            $li = ' class="nav-item dropdown"';
            $ul = ' class="dropdown-menu"';
            $link_extra = 'id="ddm' . $cat['catId'] . '" data-bs-toggle="dropdown"';
            $aclass = 'nav-link dropdown-toggle';
        }

        if ($cat['level'] == 1 && $cat['hasChildren'] == false) {
            $li = ' class="nav-item dropdown"';
            $aclass = 'dropdown-item';
        }

        if ($cat['hasChildren'] == true) {
            $sub = [];
            $sub[] = '<ul' . $ul . '>';
            $sub[] = bsnavi5($cat['children']);
            $sub[] = '</ul>';
            $subnavi = join("\n", $sub);
        }

        $catname = $cat['catName'];
        if ($cat['active'] == true) {
            $active = ' active';
        }

        $catname = '<a class="' . $aclass . $active . '" href="' . $cat['url'] . '"' . $link_extra . '>' . $catname . '</a>';
        $output[] = '<li ' . $li . '>' . $catname . $subnavi . '</li>';
    }
    return implode("\n", $output);
}

use FriendsOfRedaxo\NavigationArray\BuildArray;
$NavigationArray = BuildArray::create()->setDepth(4)->generate();
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <ul class="navbar-nav ms-md-auto mb-0">
            <?= bsnavi5($NavigationArray) ?>
        </ul>
    </div>
</nav>
</body>
```

#### Beispiel UIkit-Drop-Down

**Navigations-Array auslesen**

```php
<?php
// UIkit NAVIGATION uses navigation_array AddOn
function myNavi_demo($data = array())
{
    $output = [];
    foreach ($data as $cat) {
        $subnavi = $liclass = $ul = "";
        $subclass = 'uk-default';
        if ($cat['level'] == 0 && $cat['hasChildren'] == true) {
            $liclass = 'uk-parent ';
            $ul = ' class="uk-nav uk-navbar-dropdown-nav"';
            $subclass = 'uk-navbar-dropdown uk-margin-remove uk-padding-small';
        }
        if ($cat['hasChildren'] == true) {
            $sub = [];
            $sub[] = '<div class="' . $subclass . '">';
            $sub[] = '<ul' . $ul . '>';
            $sub[] = myNavi_demo($cat['children']);
            $sub[] = '</ul>';
            $sub[] = '</div>';
            $subnavi = join("\n", $sub);
        }
        $catname = $cat['catName'];
        if ($cat['active'] == true) {
            $catname = '' . $catname . '';
            $liclass .= 'uk-active';
        }
        if ($liclass != '') {
            $liclass = ' class="' . $liclass . '"';
        }
        $catname = '<a href="' . $cat['url'] . '">' . $catname . '</a>';
        $output[] = '<li' . $liclass . '>' . $catname . $subnavi . '</li>';
    }

    if (!empty($output)) {
        return implode("\n", $output);
    }
}
// Navigation erzeugen
use FriendsOfRedaxo\NavigationArray\BuildArray;
$NavigationArray = BuildArray::create()->setDepth(3)->generate();
$navigation = '
    <ul class="uk-navbar-nav">'
    . myNavi_demo($NavigationArray) .
    '</ul>
';
?>
<!--- NAVI-BAR --->
<div class="nav">
    <nav class="uk-box-shadow-small uk-background-secondary uk-light " uk-navbar>
        <div class="uk-navbar-left">
            <a class="uk-navbar-item uk-logo" title="Logo: <?=rex::getServerName()?>" href="/">
                <img class="logo" name="Logo" src="<?=rex_url::media()?>logo.svg" alt="">
            </a>
        </div>
        <div class="uk-visible@m uk-navbar-center uk-text-large">
            <?= $navigation; ?>
        </div>
        <div class="uk-navbar-right">
            <a class="uk-icon-button uk-margin-right" href="https://github.com/FriendsOfREDAXO" uk-icon="icon: github"></a>
        </div>
    </nav>
</div>
<!--- ENDE NAVI-BAR --->
```

#### Beispiel: Breadcrumb 

```php
<?php 
function bc_uikit($data = array())
{
    $output = []; // Initialisierung der Output-Variable
    foreach ($data as $cat) {
        $subnavi = ''; // Initialisierung der Subnavigation
        if ($cat['hasChildren']) {
            // Rekursiver Aufruf für Unterkategorien
            $sub = bc_uikit($cat['children']);
            $subnavi = join("\n", $sub);
        }
        $url = ' href="'.$cat['url'].'"';
        $liclass = '';
        if ('REX_ARTICLE_ID' == $cat['catId']) {
            $liclass = ' class="uk-disabled" aria-current="page"';
            $url = '';
        }
        $catname = '<a'.$url.'>'.$cat['catName'].'</a>';
        
        $output[] = '<li'.$liclass.'>' . $catname .'</li>'.$subnavi;
    }
    return implode("\n", $output);
}

// Breadcrumb erzeugen
use FriendsOfRedaxo\NavigationArray\BuildArray;
$NavigationArray = new BuildArray(0, 4, 20);
$result = $NavigationArray->setCategoryFilterCallback(function ($cat) {
        // Nur aktive Kategorien auswählen
        return $cat['active'];
})->generate();
echo '
    <nav aria-label="Breadcrumb">
    <ul class="uk-breadcrumb">'
    . bc_uikit($result) .
    '</ul>
    </nav>
';
?>
```

## CallbackFilter

### `setCategoryFilterCallback`

#### Beschreibung
Die `setCategoryFilterCallback` Methode ermöglicht es, einen benutzerdefinierten Filter für die Kategorien zu definieren, die in der Navigation angezeigt werden sollen. Dieser Filter ist ein Callback, der für jede Kategorie aufgerufen wird. Wenn der Callback `true` zurückgibt, wird die Kategorie in die generierte Navigationsstruktur aufgenommen. Andernfalls wird sie übersprungen.

#### Verwendung
```php
setCategoryFilterCallback(callable $callback): self
```

#### Parameter
- `$callback` - Ein `callable`, das als Filter-Callback dient. Dieser Callback nimmt ein Kategorie-Objekt als Parameter und gibt einen booleschen Wert zurück (`true` für die Aufnahme der Kategorie, `false` für deren Ausschluss).

#### Rückgabewert
Die Methode gibt das `NavigationArray`-Objekt zurück, was das Methoden-Chainen ermöglicht.

#### Beispiel
Das folgende Beispiel zeigt, wie man einen Filter definieren kann, der alle Kategorien mit der Bezeichnung `irgendwas` herausfiltert:

```php
$navigation = new BuildArray();
$navigation->setCategoryFilterCallback(function($cat) {
    return $cat->getName() !== 'irgendwas';
});
```

#### Tipp
Der Filter-Callback sollte so effizient wie möglich gestaltet werden, um die Leistung nicht negativ zu beeinflussen, besonders bei großen Kategoriestrukturen.

### `setCustomDataCallback`

#### Beschreibung
Die Methode `setCustomDataCallback` ermöglicht das Hinzufügen benutzerdefinierter Daten zu jedem Kategorie-Array in der Navigationsstruktur. Durch die Bereitstellung eines Callbacks können zusätzliche Informationen oder Attribute für jede Kategorie definiert werden.

#### Verwendung
```php
setCustomDataCallback(callable $callback): self
```

#### Parameter
- `$callback`: Ein `callable`, das als Callback für benutzerdefinierte Daten dient. Dieser Callback nimmt ein Kategorie-Objekt als Parameter und gibt ein Array zurück, das die zusätzlichen Daten enthält, die in das Kategorie-Array aufgenommen werden sollen.

#### Rückgabewert
Die Methode gibt das `NavigationArray`-Objekt zurück, was das Methoden-Chainen ermöglicht.

#### Beispiel
```php
$navigation = new BuildArray();
$navigation->setCustomDataCallback(function($cat) {
    return ['extraColor' => $cat->getValue('cat_color')];
});
```
In diesem Beispiel wird die `setCustomDataCallback` Methode verwendet, um benutzerdefinierte Daten hinzuzufügen, die für jede Kategorie zusätzliche Informationen enthalten.

#### Hinweis
Der Callback sollte effizient gestaltet werden, um die Leistung nicht zu beeinträchtigen.

##  Methoden

### Konstruktor

```php
public function __construct(
    int $start = -1,          // Start-Kategorie ID (-1 für Root)
    int $depth = 4,           // Maximale Tiefe
    bool $ignoreOfflines = true, // Offline-Kategorien ignorieren
    $depthSaved = 0,          // Gespeicherte Tiefe
    int $level = 0            // Aktuelles Level
)
```

Erstellt eine neue Instanz der NavigationArray-Klasse.

### Statische Methoden

#### create()
```php
public static function create(): self
```
Factory-Methode zum einfachen Erstellen einer neuen Instanz.

### Hauptmethoden

#### generate()
```php
public function generate(): array
```
Generiert das Navigations-Array mit allen Kategorien und Unterkategorien basierend auf den konfigurierten Einstellungen.

#### toJson()
```php
public function toJson(): string
```
Generiert das Navigations-Array und gibt es als JSON-formatierte Zeichenkette zurück.

### Setter-Methoden

#### setExcludedCategories()
```php
public function setExcludedCategories(int|array $excludedCategories): self
```
Legt fest, welche Kategorien von der Navigation ausgeschlossen werden sollen.
- Parameter kann eine einzelne Kategorie-ID oder ein Array von IDs sein

#### setStart()
```php
public function setStart(int $start): self
```
Setzt die Start-Kategorie für die Navigation.
- `-1`: Verwendet YRewrite Mount-ID oder Root-Kategorie
- `0`: Startet von der Root-Kategorie
- `>0`: Startet von der angegebenen Kategorie-ID

#### setDepth()
```php
public function setDepth(int $depth): self
```
Legt die maximale Tiefe der Navigation fest (Standard: 4).

#### setIgnore()
```php
public function setIgnore(int $ignore): self
```
Legt fest, ob Offline-Kategorien ignoriert werden sollen (Standard: true).

#### setLevel()
```php
public function setLevel(int $lvl): self
```
Setzt das aktuelle Level in der Navigation.

### Callback-Methoden

#### setCategoryFilterCallback()
```php
public function setCategoryFilterCallback(callable $callback): self
```
Setzt eine Callback-Funktion zum Filtern von Kategorien.
- Callback erhält Kategorie-Objekt als Parameter
- Muss true/false zurückgeben, ob Kategorie angezeigt werden soll

#### setCustomDataCallback()
```php
public function setCustomDataCallback(callable $callback): self
```
Setzt eine Callback-Funktion zum Hinzufügen benutzerdefinierter Daten.
- Callback erhält Kategorie-Objekt als Parameter
- Muss Array mit zusätzlichen Daten zurückgeben

### Verwendung von toJson()

```php
// Grundlegende Verwendung
$nav = BuildArray::create()->setDepth(3);
$jsonString = $nav->toJson();

// Mit allen Optionen
$nav = BuildArray::create()
    ->setStart(5)             // Startet bei Kategorie ID 5
    ->setDepth(2)            // Zwei Ebenen tief
    ->setIgnore(true)        // Ignoriert Offline-Kategorien
    ->setExcludedCategories([10, 15]) // Schließt Kategorien aus
    ->toJson();              // Gibt JSON zurück

// JSON in Variable speichern und ausgeben
$jsonNavigation = $nav->toJson();
echo $jsonNavigation;

// Direkt als AJAX Response verwenden
header('Content-Type: application/json');
echo BuildArray::create()
    ->setDepth(3)
    ->toJson();

// JSON decodieren für weitere Verarbeitung
$navigationArray = json_decode($nav->toJson(), true);
```

Das generierte JSON sieht etwa so aus:

```json
[
    {
        "catId": 1,
        "parentId": 0,
        "level": 0,
        "catName": "Home",
        "url": "/",
        "hasChildren": true,
        "children": [
            {
                "catId": 5,
                "parentId": 1,
                "level": 1,
                "catName": "Über uns",
                "url": "/ueber-uns/",
                "hasChildren": false,
                "children": [],
                "path": [1, 5],
                "active": false,
                "current": false
            }
        ],
        "path": [1],
        "active": true,
        "current": false
    }
]
```

### Allgemeine Verwendungsbeispiele

```php
// Mit Konstruktor
$nav = new BuildArray(-1, 3);
$array = $nav->generate();

// Mit Methoden-Chaining
$array = BuildArray::create()
    ->setStart(-1)
    ->setDepth(3)
    ->setIgnore(true)
    ->generate();
```


