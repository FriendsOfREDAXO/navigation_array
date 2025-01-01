# REDAXO Navigation Array

Navigation Array ist eine PHP-Klasse für die einfache Erstellung einer Navigationsstruktur als Array.

## Features

-   Offline-Artikel und YCom-Rechte werden berücksichtigt.
-   Startkategorie kann frei gewählt werden.
-   Tiefe kann festgelegt werden.
-   Kategorien filtern und manipulieren (z.B. mit Meta-Infos) über Callbacks.
-   Mitgelieferte Fragmente für die HTML-Ausgabe der Navigation.
-   NEU: `walk`-Methode für einfache, rekursive Navigationstraversierung.
-   `getCategory`-Methode zum Abrufen von Kategorieinformationen (inkl. Kindkategorien).

## Array-Struktur

So sieht das generierte Array aus. Es enthält alle Kategorien und Unterkategorien bis zur angegebenen Tiefe. Offline-Kategorien und Rechte aus YCom werden vorher aus dem Array entfernt.

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

## HTML-Ausgabe

In erster Linie generiert Navigation Array ein Array. Die Ausgabe als HTML ist Aufgabe des Entwicklers. Hier sind einige Beispiele, wie das Array in HTML integriert werden kann.

### Fragmente

Mit Fragmenten wird die Logik sauber vom Code getrennt. Das Fragment `navigation.php` kann kopiert, angepasst und in `project` oder `theme`-Ordner abgelegt werden.

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

### PHP-Funktion

Alternativ kann man das Array auch direkt in PHP verarbeiten. Hier einige Beispiele.

#### Beispiel einfache Navigation

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;
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


$NavigationArray = BuildArray::create()->setDepth(3)->generate();

// Generate the navigation list
$navigationList = generateNavigationList($NavigationArray);

// Output the navigation list
echo $navigationList;
```

#### Beispiel erweiterte Navigation mit Callback

```php
<?php
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
use FriendsOfRedaxo\NavigationArray\BuildArray;
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
use FriendsOfRedaxo\NavigationArray\BuildArray;
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
use FriendsOfRedaxo\NavigationArray\BuildArray;
// UIkit Breadcrumb uses navigation_array
function bc_uikit($data = array())
{   $liclass = '';
    $output = [];
    foreach ($data as $cat) {
        if ($cat['active'] == false)    
        {
            continue;
        }  
        $subnavi = '';
        if ($cat['hasChildren'] == true) {
            $sub = [];
            $sub[] = bc_uikit($cat['children']);
            $subnavi = join("\n", $sub);
        }       
        $catname = $cat['catName'];
        if ('REX_ARTICLE_ID' == $cat['catId']) {
            $liclass = ' class="uk-disabled"';
            $cat['url']='';
        }
        $catname = '<a href="'.$cat['url'].'">'.$catname.'</a>';     
        if ($cat['active'] == true)       
        {
	         $output[] = '<li'.$liclass.'>' . $catname .'</li>'.$subnavi;

        }
    }
    if(count($output)>0)
    {    
    return join("\n", $output);
    }    
}


$navarray = BuildArray::create()->setDepth(10)->generate();

echo '
    <ul class="uk-breadcrumb">
    <li><a title="Home" href="/"><span data-uk-icon="home"></span></a></li>'
    .bc_uikit($navarray).
    '</ul>
';
?>
```

## `walk()`-Methode

Die `walk()`-Methode ermöglicht es, die Navigation rekursiv zu durchlaufen und dabei eine Callback-Funktion auf jedes Element anzuwenden. Dies ist nützlich für benutzerdefinierte Ausgaben oder Manipulationen der Navigationsdaten.

### Verwendung

```php
public function walk(callable $callback): void
```

#### Parameter

-   `$callback`: Eine `callable`-Funktion, die für jedes Navigationselement aufgerufen wird. Die Funktion erhält zwei Parameter:
    -   `$item`: Das aktuelle Navigationselement (als Array).
    -   `$level`: Die aktuelle Ebene der Navigation (als Integer).

#### Beispiel: Verschachtelte HTML-Liste

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;

// HTML-String zum Aufbau der Navigation
$htmlNavigation = '';
$currentLevel = 0;
$first = true;

$navigation = BuildArray::create()
    ->setDepth(3)
    ->setExcludedCategories([32,34])
    ->setCategoryFilterCallback(function(rex_category $cat){
        // Hier kannst du deine eigenen Filter setzen
        return true;
    })
    ->setCustomDataCallback(function(rex_category $cat){
      return [
        'css_id' => 'cat-'.$cat->getId(),
        'description' => $cat->getValue('description')
      ];
  });

$navigation->walk(function ($item, $level) use (&$htmlNavigation, &$currentLevel, &$first) {
    
    // Level check um unnötige Tags zu vermeiden
    if($level > $currentLevel) {
       $htmlNavigation .= '<ul>';
    }
    if($level < $currentLevel){
         $diff = $currentLevel - $level;
         $htmlNavigation .= str_repeat('</ul>', $diff);
     }
     $currentLevel = $level;
   
    $activeClass = $item['active'] ? ' class="active"' : '';

    $htmlNavigation .= '<li'.$activeClass.'>';
    $htmlNavigation .= '<a href="' . $item['url'] . '" id="'.$item['css_id'].'">';
    $htmlNavigation .=  $item['catName'] ;
    $htmlNavigation .= '</a>';
    $htmlNavigation .= '</li>';
});
// Abschließen der Tags wenn auf der höchsten Ebene
if($currentLevel > 0){
 $htmlNavigation .= str_repeat('</ul>', $currentLevel);
}

// Ausgabe der gesamten Navigation
echo $htmlNavigation;

```

#### Beispiel: Logausgabe der Navigation

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;

$navigation = BuildArray::create()->setDepth(2);

$navigation->walk(function ($item, $level) {
    echo str_repeat("  ", $level) . "Kategorie: " . $item['catName'] . ", Level: " . $level . ", URL: " . $item['url'] . "<br>";
});
```

#### Beispiel: Zugriff auf Custom Data

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;

$navigation = BuildArray::create()
    ->setCustomDataCallback(function($cat) {
        return ['color' => $cat->getValue('cat_color')];
    });

$navigation->walk(function ($item, $level) {
   if (isset($item['color'])){
     echo str_repeat("  ", $level) . "Kategorie: " . $item['catName'] . ", Farbe: " . $item['color'] . "<br>";
   }
});
```

### Vergleich: `walk`-Methode vs. Eigene Iteration

Um die Vorteile der `walk`-Methode zu verdeutlichen, hier ein Beispiel, das dieselbe Aufgabe (Ausgabe einer verschachtelten Liste von Kategorienamen) einmal mit der `walk`-Methode und einmal mit einer eigenen Iterationsfunktion realisiert:

#### Mit `walk`-Methode

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;

$navigation = BuildArray::create()->setDepth(3);
$htmlOutput = '';

$navigation->walk(function($item, $level) use (&$htmlOutput) {
    $htmlOutput .= str_repeat("&nbsp;&nbsp;", $level) . $item['catName'] . "<br>";
});

echo $htmlOutput;
```

#### Mit eigener Iterationsfunktion

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;

function myCustomNavigation($items, $level = 0) {
    $output = '';
    foreach ($items as $item) {
        $output .= str_repeat("&nbsp;&nbsp;", $level) . $item['catName'] . "<br>";
        if ($item['hasChildren']) {
            $output .= myCustomNavigation($item['children'], $level + 1);
        }
    }
    return $output;
}

$navigation = BuildArray::create()->setDepth(3)->generate();
echo myCustomNavigation($navigation);
```

**Analyse:**

*   Die `walk`-Methode erfordert weniger Code, ist klarer und kapselt die Rekursionslogik.
*   Die eigene Iterationsfunktion ist komplexer, benötigt mehr Zeilen Code und wiederholt die Rekursionslogik, die bereits in `walk` implementiert ist.
*   Die `walk`-Methode bietet eine höhere Konsistenz und Flexibilität.

Dieses Beispiel verdeutlicht, warum die `walk`-Methode die empfohlene Vorgehensweise für das Durchlaufen der Navigationsdaten ist.

## Callback-Filter

### `setCategoryFilterCallback()`

#### Beschreibung

Die `setCategoryFilterCallback`-Methode ermöglicht es, einen benutzerdefinierten Filter für die Kategorien zu definieren, die in der Navigation angezeigt werden sollen. Dieser Filter ist ein Callback, der für jede Kategorie aufgerufen wird. Wenn der Callback `true` zurückgibt, wird die Kategorie in die generierte Navigationsstruktur aufgenommen. Andernfalls wird sie übersprungen.

#### Verwendung

```php
setCategoryFilterCallback(callable $callback): self
```

#### Parameter

-   `$callback` - Ein `callable`, das als Filter-Callback dient. Dieser Callback nimmt ein Kategorie-Objekt als Parameter und gibt einen booleschen Wert zurück (`true` für die Aufnahme der Kategorie, `false` für deren Ausschluss).

#### Rückgabewert

Die Methode gibt das `NavigationArray`-Objekt zurück, was das Methoden-Chainen ermöglicht.

#### Beispiel

Das folgende Beispiel zeigt, wie man einen Filter definieren kann, der alle Kategorien mit der Bezeichnung `irgendwas` herausfiltert:

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;
$navigation = new BuildArray();
$navigation->setCategoryFilterCallback(function($cat) {
    return $cat->getName() !== 'irgendwas';
});
```

#### Tipp

Der Filter-Callback sollte so effizient wie möglich gestaltet werden, um die Leistung nicht negativ zu beeinflussen, besonders bei großen Kategoriestrukturen.

### `setCustomDataCallback()`

#### Beschreibung

Die Methode `setCustomDataCallback` ermöglicht das Hinzufügen benutzerdefinierter Daten zu jedem Kategorie-Array in der Navigationsstruktur. Durch die Bereitstellung eines Callbacks können zusätzliche Informationen oder Attribute für jede Kategorie definiert werden.

#### Verwendung

```php
setCustomDataCallback(callable $callback): self
```

#### Parameter

-   `$callback`: Ein `callable`, das als Callback für benutzerdefinierte Daten dient. Dieser Callback nimmt ein Kategorie-Objekt als Parameter und gibt ein Array zurück, das die zusätzlichen Daten enthält, die in das Kategorie-Array aufgenommen werden sollen.

#### Rückgabewert

Die Methode gibt das `NavigationArray`-Objekt zurück, was das Methoden-Chainen ermöglicht.

#### Beispiel

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;
$navigation = new BuildArray();
$navigation->setCustomDataCallback(function($cat) {
    return ['extraColor' => $cat->getValue('cat_color')];
});
```

In diesem Beispiel wird die `setCustomDataCallback`-Methode verwendet, um benutzerdefinierte Daten hinzuzufügen, die für jede Kategorie zusätzliche Informationen enthalten.

#### Hinweis

Der Callback sollte effizient gestaltet werden, um die Leistung nicht zu beeinträchtigen.

## Methoden

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

#### `create()`

```php
public static function create(): self
```

Factory-Methode zum einfachen Erstellen einer neuen Instanz.

### Hauptmethoden

#### `generate()`

```php
public function generate(): array
```

Generiert das Navigations-Array mit allen Kategorien und Unterkategorien basierend auf den konfigurierten Einstellungen.

#### `toJson()`

```php
public function toJson(): string
```

Generiert das Navigations-Array und gibt es als JSON-formatierte Zeichenkette zurück.

### Setter-Methoden

#### `setExcludedCategories()`

```php
public function setExcludedCategories(int|array $excludedCategories): self
```

Legt fest, welche Kategorien von der Navigation ausgeschlossen werden sollen.

-   Parameter kann eine einzelne Kategorie-ID oder ein Array von IDs sein.

#### `setStart()`

```php
public function setStart(int $start): self
```

Setzt die Start-Kategorie für die Navigation.

-   `-1`: Verwendet YRewrite Mount-ID oder Root-Kategorie.
-   `0`: Startet von der Root-Kategorie.
-   `>0`: Startet von der angegebenen Kategorie-ID.

#### `setDepth()`

```php
public function setDepth(int $depth): self
```

Legt die maximale Tiefe der Navigation fest (Standard: 4).

#### `setIgnore()`

```php
public function setIgnore(int $ignore): self
```

Legt fest, ob Offline-Kategorien ignoriert werden sollen (Standard: true).

#### `setLevel()`

```php
public function setLevel(int $lvl): self
```

Setzt das aktuelle Level in der Navigation.

### Callback-Methoden

#### `setCategoryFilterCallback()`

```php
public function setCategoryFilterCallback(callable $callback): self
```

Setzt eine Callback-Funktion zum Filtern von Kategorien.

-   Callback erhält Kategorie-Objekt als Parameter.
-   Muss true/false zurückgeben, ob Kategorie angezeigt werden soll.

#### `setCustomDataCallback()`

```php
public function setCustomDataCallback(callable $callback): self
```

Setzt eine Callback-Funktion zum Hinzufügen benutzerdefinierter Daten.

-   Callback erhält Kategorie-Objekt als Parameter.
-   Muss Array mit zusätzlichen Daten zurückgeben.

### Verwendung von `toJson()`

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;
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

## `getCategory()`

Liefert ein Array mit allen Informationen zu einer Kategorie. Funktioniert sowohl für die aktuelle Kategorie als auch für eine spezifische Kategorie-ID.

### Basis-Verwendung

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;
// Aktuelle Kategorie
$category = BuildArray::create()->getCategory();

// Spezifische Kategorie
$category = BuildArray::create()->getCategory(5);
```

### Rückgabe-Array

```php
[
    // Hauptkategorie-Informationen
    'catId' => 5,                  // ID der Kategorie
    'parentId' => 2,               // ID der Elternkategorie
    'catName' => 'News',           // Name der Kategorie
    'url' => '/news/',             // URL der Kategorie
    'hasChildren' => true,         // Hat Unterkategorien
    'path' => [0,2,5],            // Pfad von Root zur Kategorie
    'pathCount' => 3,              // Anzahl der Ebenen von Root
    'active' => true,              // Ist im aktiven Pfad
    'current' => true,             // Ist aktuelle Kategorie
    'cat' => Object,               // REX Category Objekt
    'ycom_permitted' => true,      // YCom-Berechtigung
    'filter_permitted' => true,    // Filter-Erlaubnis
    'is_permitted' => true,        // Gesamtstatus der Berechtigungen
    
    // Kinder-Array (aus processCategory)
    'children' => [
        [
            'catId' => 15,         // ID der Kindkategorie
            'parentId' => 5,       // ID der Elternkategorie (unsere Hauptkategorie)
            'level' => 3,          // Level aus processCategory
            'catName' => 'Events', // Name der Kindkategorie
            'url' => '/news/events/', // URL der Kindkategorie
            'hasChildren' => false, // Hat diese Kindkategorie weitere Unterkategorien
            'children' => [],       // Eventuelle Kinder der Kindkategorie
            'path' => [0,2,5,15],  // Pfad dieser Kindkategorie
            'active' => false,      // Ist diese Kindkategorie aktiv
            'current' => false      // Ist diese Kindkategorie die aktuelle Kategorie
        ],
        [
            'catId' => 16,
            'parentId' => 5,
            'level' => 3,
            'catName' => 'Blog',
            // ... weitere Eigenschaften wie oben
        ],
        // ... weitere Kindkategorien
    ],

    // Zusätzliche Custom-Daten (wenn definiert)
    'custom_field' => 'Wert',
    'another_field' => 'Wert'
]
```

### Beispiele

#### 1. Kategorie-Vergleich

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;
$currentCat = BuildArray::create()->getCategory();
$parentCat = BuildArray::create()->getCategory($currentCat['parentId']);

echo '<div class="category-nav">';
echo '<h2>' . $parentCat['catName'] . '</h2>';
echo '<h3>Sie befinden sich hier: ' . $currentCat['catName'] . '</h3>';
echo '</div>';
```

#### 2. Mit Custom-Daten

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;
$category = BuildArray::create()
    ->setCustomDataCallback(function($cat) {
        return [
            'image' => $cat->getValue('cat_image'),
            'description' => $cat->getValue('cat_description')
        ];
    })
    ->getCategory(5);

if ($category['is_permitted']) {
    echo '<div class="category-info">';
    echo '<h1>' . $category['catName'] . '</h1>';
    if ($category['image']) {
        echo '<img src="' . $category['image'] . '">';
    }
    echo '</div>';
}
```

#### 3. Mehrere Kategorien verarbeiten

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;
$builder = BuildArray::create()
    ->setCategoryFilterCallback(function($cat) {
        return $cat->getValue('show_in_nav') == 1;
    });

$categories = [
    $builder->getCategory(3),  // Kategorie mit ID 3
    $builder->getCategory(4),  // Kategorie mit ID 4
    $builder->getCategory()    // Aktuelle Kategorie
];

foreach ($categories as $category) {
    if ($category['is_permitted']) {
        echo '<div class="cat-box' . 
             ($category['current'] ? ' current' : '') . 
             ($category['active'] ? ' active' : '') . 
             '">';
        echo $category['catName'];
        echo '</div>';
    }
}
```

## Autor

**Friends Of REDAXO**

-   <http://www.redaxo.org>
-   <https://github.com/FriendsOfREDAXO>

**Projektleitung**

[Thomas Skerbis](https://github.com/skerbis)
