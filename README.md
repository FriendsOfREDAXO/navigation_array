# REDAXO Navigation Array

Navigation Array ist eine PHP-Klasse für die einfache Erstellung einer Navigationsstruktur als Array. Diese Klasse bietet flexible Möglichkeiten, Navigationsdaten auszulesen, zu filtern und zu verarbeiten, insbesondere durch die Nutzung der `walk()`-Methode.

## Erklärung der Klasse

Die `FriendsOfRedaxo\NavigationArray\BuildArray` Klasse bietet folgende Hauptfunktionalitäten:

*   **Erstellung eines Navigationsarrays:** Generiert ein verschachteltes Array, das die Kategorien und ihre Hierarchie darstellt.
*   **Filterung von Kategorien:** Ermöglicht das Herausfiltern von Kategorien basierend auf benutzerdefinierten Kriterien.
*   **Hinzufügen benutzerdefinierter Daten:** Fügt zusätzliche Informationen zu jedem Kategorie-Array hinzu.
*   **Rekursive Navigationstraversierung:** Die `walk()`-Methode erlaubt es, die Navigationsstruktur rekursiv zu durchlaufen und dabei individuelle Operationen auszuführen.
*   **Abrufen von Kategorieinformationen:** Mit der `getCategory()`-Methode können detaillierte Informationen zu einzelnen Kategorien abgerufen werden.

### Kernfunktionen

-   Berücksichtigung von Offline-Artikeln und YCom-Rechten.
-   Frei wählbare Startkategorie.
-   Festlegbare Tiefe der Navigation.
-   Filterung und Manipulation von Kategorien über Callbacks.

### Features

-   `getCategory`-Methode zum Abrufen von Kategorieinformationen (inkl. Kindkategorien).
-   `walk()`-Methode für einfache, rekursive Navigationstraversierung.
-   Mitgelieferte Fragmente für die HTML-Ausgabe der Navigation (siehe Beispiele unten).

## Auslesen der Daten

### Array-Struktur

Das generierte Array enthält alle Kategorien und Unterkategorien bis zur angegebenen Tiefe. Offline-Kategorien und Rechte aus YCom werden vorher entfernt.

```php
array:7 [
    0 => array:11 [▶]
    1 => array:11 [▶]
    2 => array:11 [▶]
    3 => array:11 [▶]
    4 => array:11 [
        "catId" => 43
        "parentId" => 0
        "level" => 0
        "catName" => "Kontakt"
        "url" => "/kontakt/"
        "hasChildren" => true
        "children" => array:2 [
            0 => array:11 [
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

### Array generieren

#### Aufruf mit Konstruktor

```php
// define namespace
use FriendsOfRedaxo\NavigationArray\BuildArray;
// create object
$navigationObject = new BuildArray(-1, 3);
// generate navigation array
$navigationArray = $navigationObject->generate();
```

#### Aufruf mit Methoden

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

## Erstellen von Navigationen

### Verwendung der `walk()`-Methode

Die `walk()`-Methode ermöglicht es, die Navigation rekursiv zu durchlaufen und dabei eine Callback-Funktion auf jedes Element anzuwenden. Dies ist ideal für benutzerdefinierte Ausgaben oder Manipulationen der Navigationsdaten.

#### Verwendung

```php
public function walk(callable $callback): void
```

#### Parameter

-   `$callback`: Eine `callable`-Funktion, die für jedes Navigationselement aufgerufen wird. Die Funktion erhält zwei Parameter:
    -   `$item`: Das aktuelle Navigationselement (als Array).
    -   `$level`: Die aktuelle Ebene der Navigation (als Integer).

#### Beispiel: Verschachtelte HTML-Liste mit `walk()`

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;

$htmlNavigation = '';
$currentLevel = 0;

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

$navigation->walk(function ($item, $level) use (&$htmlNavigation, &$currentLevel) {
    
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

echo $htmlNavigation;
```

#### Beispiel: Logausgabe der Navigation mit `walk()`

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;

$navigation = BuildArray::create()->setDepth(2);

$navigation->walk(function ($item, $level) {
    echo str_repeat("  ", $level) . "Kategorie: " . $item['catName'] . ", Level: " . $level . ", URL: " . $item['url'] . "<br>";
});
```

#### Beispiel: Einfache Navigation mit `walk()` und REDAXO Fragment
Hier ein Beispiel, wie du die `walk()`-Methode in Verbindung mit einem REDAXO-Fragment für eine einfache Navigation verwenden kannst.

**1. Fragment (`simple_navigation.php`)**:
Erstelle eine Fragmentdatei im Verzeichnis `fragments/navigation_array` mit folgendem Inhalt:

```php
<?php
    $items = $this->getVar('items');
    $level = $this->getVar('level', 0);
    $activeClass = $this->getVar('activeClass', 'active');
    
    if (empty($items)) {
        return '';
    }
    $output = '<ul>';
    foreach ($items as $item) {
         $active = $item['active'] ? ' class="'.$activeClass.'"' : '';
        $output .= '<li' . $active . '><a href="'.$item['url'].'">'.$item['catName'].'</a>';
         if(isset($item['children']) && !empty($item['children'])){
           
            $output .= $this->subfragment('navigation_array/simple_navigation.php', [
                'items' => $item['children'],
                'level' => $level +1,
                'activeClass' => $activeClass
            ]);
         }
      $output .='</li>';
    }
    $output .= '</ul>';
    echo $output;
?>
```

**2. Aufruf im Template:**
In deinem REDAXO-Template kannst du die Navigation wie folgt erstellen:
```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;

$navigation = BuildArray::create()->setDepth(3);
$htmlNavigation = '';
$items = [];
$navigation->walk(function ($item, $level) use (&$items) {
   $items[] = $item;
});


$fragment = new rex_fragment();
$fragment->setVar('items', $items);
$fragment->setVar('activeClass', 'active');
echo $fragment->parse('navigation_array/simple_navigation.php');
```

Dieses Beispiel zeigt, wie du mit der `walk()`-Methode ein Array der Navigation erzeugen kannst und dieses dann mit einem REDAXO-Fragment in eine einfache Navigation umwandeln kannst. Die Rekursion wird nun über die Fragments realisiert, um einen wiederverwendbaren Code zu erhalten.

### Der klassische Weg (Eigene Iteration)

Die traditionelle Methode zur Erstellung einer Navigation verwendet eine eigene rekursive Funktion, um das Array zu durchlaufen.

#### Beispiel: Verschachtelte HTML-Liste mit eigener Iteration

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;

function myCustomNavigation($items, $level = 0) {
    $output = '<ul>';
    foreach ($items as $item) {
        $output .= '<li>';
        $output .= '<a href="' . $item['url'] . '">' . $item['catName'] . '</a>';
        if ($item['hasChildren']) {
            $output .= myCustomNavigation($item['children'], $level + 1);
        }
        $output .= '</li>';
    }
    $output .= '</ul>';
    return $output;
}

$navigation = BuildArray::create()->setDepth(3)->generate();
echo myCustomNavigation($navigation);
```

### Vergleich

| Feature              | `walk()`-Methode                        | Eigene Iteration                |
|----------------------|-----------------------------------------|---------------------------------|
| **Code-Komplexität**  | Geringer, klarer, kapselt die Rekursion | Höher, mehr Code               |
| **Lesbarkeit**       | Besser                                 | Schlechter                     |
| **Wartbarkeit**      | Einfacher                                | Komplexer                      |
| **Flexibilität**     | Hoch, mit Callback                     | Eingeschränkt, weniger flexibel |
| **Logik**            | Zentral implementiert                   | Wiederholte Rekursionslogik     |

Die `walk()`-Methode bietet eine sauberere und effizientere Lösung zur Traversierung der Navigationsdaten, während der klassische Weg mehr Code erfordert und weniger wartbar ist.

## Weitere Beispiele

Neben der Nutzung von `walk()`, können auch die generierten Arrays direkt für die Ausgabe genutzt werden.

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

### Weitere PHP-Funktionen (Auswahl)

Hier sind weitere Beispiele für spezifische HTML-Ausgaben

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

## Autor

**Friends Of REDAXO**

-   <http://www.redaxo.org>
-   <https://github.com/FriendsOfREDAXO>

**Projektleitung**

[Thomas Skerbis](https://github.com/skerbis)
```
